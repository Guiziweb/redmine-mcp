<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Dto\DtoInterface;
use App\SchemaGenerator;
use App\Tools\AbstractMcpTool;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AbstractMcpToolTest extends TestCase
{
    private TestableAbstractMcpTool $tool;
    private ValidatorInterface $validator;
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->schemaGenerator = new SchemaGenerator();

        $this->tool = new TestableAbstractMcpTool(
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetOutputSchemaReturnsNull(): void
    {
        $this->assertNull($this->tool->getOutputSchema());
    }

    public function testGetAnnotationsReturnsNull(): void
    {
        $this->assertNull($this->tool->getAnnotations());
    }

    public function testGetInputSchemaWithoutRequestClass(): void
    {
        $this->tool->setRequestClass(null);
        $schema = $this->tool->getInputSchema();

        $this->assertEquals([
            'type' => 'object',
            'properties' => [],
        ], $schema);
    }

    public function testCallWithValidRequest(): void
    {
        $this->tool->setRequestClass(TestDto::class);
        $this->tool->setExecuteResult(['success' => true, 'data' => 'test']);

        $toolCall = new ToolCall(
            id: 'test-1',
            name: 'test_tool',
            arguments: ['name' => 'test']
        );

        $result = $this->tool->call($toolCall);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $decoded = json_decode($result->result, true);
        $this->assertEquals(['success' => true, 'data' => 'test'], $decoded);
    }

    public function testCallWithValidationError(): void
    {
        $this->tool->setRequestClass(TestDto::class);

        $toolCall = new ToolCall(
            id: 'test-2',
            name: 'test_tool',
            arguments: [] // Missing required 'name' field
        );

        $result = $this->tool->call($toolCall);

        $this->assertTrue($result->isError);
        $decoded = json_decode($result->result, true);
        $this->assertFalse($decoded['success']);
        $this->assertStringContainsString('Validation failed', $decoded['error']);
    }

    public function testCallWithExecutionException(): void
    {
        $this->tool->setRequestClass(TestDto::class);
        $this->tool->setExecuteException(new \RuntimeException('Test error'));

        $toolCall = new ToolCall(
            id: 'test-3',
            name: 'test_tool',
            arguments: ['name' => 'test']
        );

        $result = $this->tool->call($toolCall);

        $this->assertTrue($result->isError);
        $decoded = json_decode($result->result, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('Test error', $decoded['error']);
    }
}

// Test implementations
class TestableAbstractMcpTool extends AbstractMcpTool
{
    /** @var class-string|null */
    private ?string $requestClass = null;
    /** @var array<string, mixed> */
    private array $executeResult = [];
    private ?\Throwable $executeException = null;

    public function getName(): string
    {
        return 'test_tool';
    }

    public function getDescription(): string
    {
        return 'Test tool';
    }

    public function getTitle(): string
    {
        return 'Test Tool';
    }

    /**
     * @param class-string|null $requestClass
     */
    public function setRequestClass(?string $requestClass): void
    {
        $this->requestClass = $requestClass;
    }

    /** @param array<string, mixed> $result */
    public function setExecuteResult(array $result): void
    {
        $this->executeResult = $result;
    }

    public function setExecuteException(\Throwable $exception): void
    {
        $this->executeException = $exception;
    }

    /**
     * @return class-string|null
     */
    protected function getRequestClass(): ?string
    {
        return $this->requestClass;
    }

    /** @return array<string, mixed> */
    protected function execute(object $request): array
    {
        if ($this->executeException) {
            throw $this->executeException;
        }

        return $this->executeResult;
    }

    protected function getErrorMessage(): string
    {
        return 'Test tool failed';
    }
}

class TestDto implements DtoInterface
{
    #[Assert\NotBlank]
    public string $name = '';

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? '';

        return $dto;
    }
}
