<?php

namespace App\MCP\Handlers;

use App\MCP\Tools\RedmineToolsExposer;
use App\Service\RedmineService;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Message\Request;
use Symfony\AI\McpSdk\Message\Response;
use Symfony\AI\McpSdk\Server\RequestHandler\BaseRequestHandler;

/**
 * Handler pour l'exécution des tools Redmine individuels.
 */
class RedmineToolsCallHandler extends BaseRequestHandler
{
    private RedmineToolsExposer $exposer;

    public function __construct(RedmineService $redmineService, LoggerInterface $logger)
    {
        $this->exposer = new RedmineToolsExposer($redmineService, $logger);
    }

    public function createResponse(Request $message): Response
    {
        $params = $message->params ?? [];
        $name = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];

        try {
            // Trouver le tool correspondant
            $tools = $this->exposer->generateTools();
            $targetTool = null;

            foreach ($tools as $tool) {
                if ($tool->getName() === $name) {
                    $targetTool = $tool;
                    break;
                }
            }

            if (!$targetTool) {
                return new Response($message->id, [
                    'error' => [
                        'code' => 'tool_not_found',
                        'message' => "Tool '{$name}' not found",
                    ],
                ]);
            }

            // Créer un objet ToolCall simulé
            $toolCall = new class($arguments) {
                public $arguments;

                public function __construct($arguments)
                {
                    $this->arguments = $arguments;
                }
            };

            // Exécuter le tool
            $result = $targetTool->call($toolCall);

            return new Response($message->id, [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $result->result,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return new Response($message->id, [
                'error' => [
                    'code' => 'tool_execution_error',
                    'message' => $e->getMessage(),
                ],
            ]);
        }
    }

    protected function supportedMethod(): string
    {
        return 'tools/call';
    }
}
