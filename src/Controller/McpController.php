<?php

declare(strict_types=1);

namespace App\Controller;

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Mcp\Server;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\StreamableHttpTransport;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
class McpController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $serviceContainer,
        private readonly string $keycloakUrl,
        private readonly string $keycloakRealm,
        private readonly string $keycloakAudience,
        private readonly string $projectDir,
    ) {
    }

    #[Route('/.well-known/oauth-protected-resource', name: 'oauth_protected_resource', methods: ['GET'])]
    public function oauthProtectedResource(): JsonResponse
    {
        return new JsonResponse([
            'resource' => $this->keycloakAudience,
            'authorization_servers' => ["{$this->keycloakUrl}/realms/{$this->keycloakRealm}"],
            'bearer_methods_supported' => ['header'],
        ]);
    }

    #[Route('/mcp', name: 'mcp_endpoint', methods: ['GET', 'POST', 'DELETE', 'OPTIONS'])]
    #[IsGranted('ROLE_USER')]
    public function mcp(Request $request): StreamedResponse|JsonResponse
    {
        // Convert Symfony Request to PSR-7
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        // Create MCP server with HTTP transport
        $server = Server::builder()
            ->setServerInfo('redmine-mcp', '1.0.0')
            ->setDiscovery($this->projectDir.'/src', ['.'])
            ->setContainer($this->serviceContainer)
            ->setLogger($this->logger)
            ->setSession(new FileSessionStore($this->projectDir.'/var/mcp-sessions'))
            ->build();

        $transport = new StreamableHttpTransport(
            $psrRequest,
            $psr17Factory,
            $psr17Factory,
            $this->logger
        );

        $server->connect($transport);

        // listen() returns a PSR-7 response that we emit
        $psrResponse = $transport->listen();

        // Create a StreamedResponse that emits the PSR-7 response
        return new StreamedResponse(
            function () use ($psrResponse) {
                $emitter = new SapiStreamEmitter();
                $emitter->emit($psrResponse);
            },
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}
