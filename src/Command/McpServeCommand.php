<?php

declare(strict_types=1);

namespace App\Command;

use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StdioTransport;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mcp:serve',
    description: 'Start MCP server in stdio mode',
)]
class McpServeCommand extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $serviceContainer,
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create MCP server with stdio transport
        $server = Server::builder()
            ->setServerInfo('redmine-mcp', '1.0.0')
            ->setDiscovery($this->projectDir.'/src', ['.'])
            ->setContainer($this->serviceContainer)
            ->setLogger($this->logger)
            ->setSession(new FileSessionStore($this->projectDir.'/var/mcp-sessions'))
            ->build();

        $transport = new StdioTransport(logger: $this->logger);

        $server->connect($transport);

        // Start listening (blocks until connection closes)
        $transport->listen();

        return Command::SUCCESS;
    }
}