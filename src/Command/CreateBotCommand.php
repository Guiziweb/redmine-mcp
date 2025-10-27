<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Model\UserCredential;
use App\Infrastructure\Security\JwtTokenValidator;
use App\Infrastructure\Security\UserCredentialRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-bot',
    description: 'Create an admin bot user with long-lived JWT token for n8n integration'
)]
class CreateBotCommand extends Command
{
    public function __construct(
        private readonly UserCredentialRepository $credentialRepository,
        private readonly JwtTokenValidator $tokenValidator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Bot user email (e.g., bot@admin.com)')
            ->addOption('redmine-url', null, InputOption::VALUE_REQUIRED, 'Redmine instance URL')
            ->addOption('redmine-api-key', null, InputOption::VALUE_REQUIRED, 'Redmine admin API key')
            ->addOption('jwt-expiry', null, InputOption::VALUE_OPTIONAL, 'JWT token expiry (e.g., "+1 year", "+30 days")', '+1 year')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get options
        $email = $input->getOption('email');
        $redmineUrl = $input->getOption('redmine-url');
        $redmineApiKey = $input->getOption('redmine-api-key');
        $jwtExpiry = $input->getOption('jwt-expiry');

        // Validate required options
        if (!$email || !$redmineUrl || !$redmineApiKey) {
            $io->error('All options are required: --email, --redmine-url, --redmine-api-key');

            return Command::FAILURE;
        }

        // Check if user already exists
        if ($this->credentialRepository->exists($email)) {
            $io->warning(sprintf('User "%s" already exists. Updating credentials and generating new token.', $email));

            // Load existing credential
            $credential = $this->credentialRepository->findByUserId($email);
            $credential->redmineUrl = rtrim($redmineUrl, '/');
            $credential->redmineApiKey = $redmineApiKey;
            $credential->role = 'admin';
            $credential->isBot = true;
        } else {
            // Create new bot user
            $credential = new UserCredential(
                userId: $email,
                redmineUrl: rtrim($redmineUrl, '/'),
                redmineApiKey: $redmineApiKey,
                createdAt: new \DateTimeImmutable(),
                role: 'admin',
                isBot: true,
            );

            $io->info(sprintf('Creating new bot user: %s', $email));
        }

        // Save to database (credentials will be encrypted automatically)
        $this->credentialRepository->save($credential);

        $io->success('Bot user created/updated successfully!');

        // Calculate expiry in seconds
        $expiresIn = (new \DateTimeImmutable($jwtExpiry))->getTimestamp() - time();

        // Generate long-lived JWT token
        $token = $this->tokenValidator->createToken(
            userId: $email,
            expiresIn: $expiresIn,
            extraClaims: [
                'role' => 'admin',
                'is_bot' => true,
            ]
        );

        $io->section('Bot Details');
        $io->table(
            ['Property', 'Value'],
            [
                ['Email', $email],
                ['Role', 'admin'],
                ['Is Bot', 'true'],
                ['Redmine URL', $redmineUrl],
                ['JWT Expiry', $jwtExpiry],
                ['Expires At', (new \DateTimeImmutable($jwtExpiry))->format('Y-m-d H:i:s')],
            ]
        );

        $io->section('JWT Token (copy this for n8n)');
        $io->writeln($token);

        $io->note([
            'This token grants admin access to query any user\'s data.',
            'Store it securely in your n8n environment variables.',
            'Use it in the Authorization header: Bearer <token>',
        ]);

        return Command::SUCCESS;
    }
}
