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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class McpController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $serviceContainer,
    ) {
    }

    #[Route('/mcp', name: 'mcp_endpoint', methods: ['GET', 'POST', 'DELETE', 'OPTIONS'])]
    public function mcp(Request $request): StreamedResponse
    {

        // Convert Symfony Request to PSR-7
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        // Create MCP server with HTTP transport
        $projectRoot = dirname(__DIR__, 2);
        $srcPath = $projectRoot . '/src';
        $sessionPath = $projectRoot . '/var/mcp-sessions';

        $server = Server::builder()
            ->setServerInfo('redmine-mcp', '1.0.0')
            ->setDiscovery($srcPath, ['.'])
            ->setContainer($this->serviceContainer)
            ->setLogger($this->logger)
            ->setSession(new FileSessionStore($sessionPath))
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