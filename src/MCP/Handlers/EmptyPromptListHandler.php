<?php

namespace App\MCP\Handlers;

use Symfony\AI\McpSdk\Message\Request;
use Symfony\AI\McpSdk\Message\Response;
use Symfony\AI\McpSdk\Server\RequestHandler\BaseRequestHandler;

/**
 * Handler pour la liste des prompts (vide pour notre cas).
 */
class EmptyPromptListHandler extends BaseRequestHandler
{
    public function createResponse(Request $message): Response
    {
        // Retourner une liste vide de prompts
        $result = [
            'prompts' => [],
        ];

        return new Response($message->id, $result);
    }

    protected function supportedMethod(): string
    {
        return 'prompts/list';
    }
}
