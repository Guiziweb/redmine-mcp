<?php

namespace App\MCP\Handlers;

use App\MCP\Tools\RedmineToolsExposer;
use App\Service\RedmineService;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Message\Request;
use Symfony\AI\McpSdk\Message\Response;
use Symfony\AI\McpSdk\Server\RequestHandler\BaseRequestHandler;

/**
 * Handler qui expose directement tous les tools Redmine comme des tools MCP individuels.
 */
class RedmineToolsHandler extends BaseRequestHandler
{
    private RedmineToolsExposer $exposer;

    public function __construct(RedmineService $redmineService, LoggerInterface $logger)
    {
        $this->exposer = new RedmineToolsExposer($redmineService, $logger);
    }

    public function createResponse(Request $message): Response
    {
        // Générer tous les tools Redmine
        $tools = $this->exposer->generateTools();

        // Convertir les tools en format MCP pour tools/list
        $mcpTools = [];
        foreach ($tools as $tool) {
            $mcpTools[] = [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
            ];
        }

        $result = [
            'tools' => $mcpTools,
        ];

        return new Response($message->id, $result);
    }

    protected function supportedMethod(): string
    {
        return 'tools/list';
    }
}
