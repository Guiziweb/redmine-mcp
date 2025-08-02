<?php

namespace App\MCP\Tools;

use App\Service\RedmineService;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;

/**
 * Expose directement tous les tools Redmine comme des tools MCP individuels.
 */
class RedmineToolsExposer
{
    private LoggerInterface $logger;
    private DynamicToolFactory $factory;

    public function __construct(RedmineService $redmineService, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->factory = new DynamicToolFactory($redmineService, $logger);
    }

    /**
     * Génère tous les tools MCP individuels.
     */
    public function generateTools(): array
    {
        $getTools = $this->factory->generateGetTools();
        $mcpTools = [];

        foreach ($getTools as $tool) {
            $mcpTools[] = new class($tool, $this->logger) implements MetadataInterface, ToolExecutorInterface {
                private $dynamicTool;
                private $logger;

                public function __construct($dynamicTool, $logger)
                {
                    $this->dynamicTool = $dynamicTool;
                    $this->logger = $logger;
                }

                public function call($input): ToolCallResult
                {
                    try {
                        $result = $this->dynamicTool->call($input);

                        return new ToolCallResult(
                            result: $result->result,
                            type: 'text',
                            mimeType: 'text/plain',
                            isError: false,
                        );
                    } catch (\Exception $e) {
                        $this->logger->error("Erreur dans tool {$this->dynamicTool->getName()}: ".$e->getMessage());

                        return new ToolCallResult(
                            result: json_encode(['error' => $e->getMessage()]),
                            type: 'text',
                            mimeType: 'text/plain',
                            isError: true,
                        );
                    }
                }

                public function getName(): string
                {
                    return $this->dynamicTool->getName();
                }

                public function getDescription(): string
                {
                    return $this->dynamicTool->getDescription();
                }

                public function getTitle(): ?string
                {
                    return $this->dynamicTool->getTitle();
                }

                public function getInputSchema(): array
                {
                    return $this->dynamicTool->getInputSchema();
                }

                public function getOutputSchema(): ?array
                {
                    return null; // Pas de schéma de sortie pour éviter les erreurs
                }

                public function getAnnotations(): ?ToolAnnotationsInterface
                {
                    return null;
                }
            };
        }

        return $mcpTools;
    }
}
