<?php

declare(strict_types=1);

namespace App\Tools;

use App\SchemaGenerator;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Abstract base class for MCP tools.
 */
#[AsTaggedItem('mcp.tool')]
abstract class AbstractMcpTool implements MetadataInterface, ToolExecutorInterface
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
        protected readonly SchemaGenerator $schemaGenerator,
    ) {
    }

    public function getOutputSchema(): ?array
    {
        return null;
    }

    public function getAnnotations(): ?ToolAnnotationsInterface
    {
        return null;
    }

    /**
     * Validate request DTO and return violations as error message.
     *
     * @throws \InvalidArgumentException if validation fails
     */
    protected function validateRequest(object $request): void
    {
        $violations = $this->validator->validate($request);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }

            throw new \InvalidArgumentException('Validation failed: '.implode(', ', $errors));
        }
    }

    /**
     * Create a success response.
     */
    /** @param array<string, mixed> $data */
    protected function createSuccessResponse(array $data): ToolCallResult
    {
        return new ToolCallResult(
            result: json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}',
            type: 'text',
            mimeType: 'application/json',
            isError: false
        );
    }

    /**
     * Create an error response.
     */
    protected function createErrorResponse(string $message, ?\Throwable $exception = null): ToolCallResult
    {
        $data = [
            'success' => false,
            'message' => $message,
            'error' => $exception ? $exception->getMessage() : $message,
        ];

        return new ToolCallResult(
            result: json_encode($data, JSON_PRETTY_PRINT) ?: '{}',
            type: 'text',
            mimeType: 'application/json',
            isError: true
        );
    }

    /**
     * Get the DTO class name for this tool's input.
     *
     * @return class-string|null
     */
    abstract protected function getRequestClass(): ?string;

    /**
     * Execute the tool's main logic.
     */
    /** @return array<string, mixed> */
    abstract protected function execute(object $request): array;

    /**
     * Get the error message for this tool.
     */
    abstract protected function getErrorMessage(): string;

    public function getInputSchema(): array
    {
        $requestClass = $this->getRequestClass();
        if (null === $requestClass) {
            return [
                'type' => 'object',
                'properties' => [],
            ];
        }

        return $this->schemaGenerator->generateFromClass($requestClass);
    }

    public function call(ToolCall $input): ToolCallResult
    {
        try {
            $requestClass = $this->getRequestClass();

            if (null !== $requestClass) {
                $request = $requestClass::fromArray($input->arguments);
                $this->validateRequest($request);
            } else {
                $request = (object) $input->arguments;
            }

            $data = $this->execute($request);

            return $this->createSuccessResponse($data);
        } catch (\Throwable $e) {
            return $this->createErrorResponse($this->getErrorMessage(), $e);
        }
    }
}
