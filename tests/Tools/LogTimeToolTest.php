<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\TimeEntryClient;
use App\SchemaGenerator;
use App\Tools\LogTimeTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogTimeToolTest extends TestCase
{
    private LogTimeTool $tool;
    private TimeEntryClient|MockObject $timeEntryClient;
    private ValidatorInterface $validator;
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->timeEntryClient = $this->createMock(TimeEntryClient::class);
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->schemaGenerator = new SchemaGenerator();

        $this->tool = new LogTimeTool(
            $this->timeEntryClient,
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_log_time', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->tool->getDescription();
        $this->assertStringContainsString('Log time', $description);
        $this->assertStringContainsString('IMPORTANT', $description);
        $this->assertStringContainsString('ask the user', $description);
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
    }

    public function testSuccessfulLogTime(): void
    {
        $this->timeEntryClient
            ->expects($this->once())
            ->method('logTime')
            ->with(123, 2.5, 'Test comment', 5)
            ->willReturn(['success' => true]);

        $input = new ToolCall(
            id: 'test-call-1',
            name: 'log_time',
            arguments: [
                'issue_id' => 123,
                'hours' => 2.5,
                'comment' => 'Test comment',
                'activity_id' => 5,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $response = json_decode($result->result, true);
        $this->assertTrue($response['success']);
    }

    public function testValidationError(): void
    {
        $input = new ToolCall(
            id: 'test-call-2',
            name: 'log_time',
            arguments: [
                'issue_id' => 0, // Invalid: must be positive
                'hours' => 2.5,
                'comment' => 'Test comment',
                'activity_id' => 5,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('Validation failed', $response['error']);
    }

    public function testRepositoryException(): void
    {
        $this->timeEntryClient
            ->expects($this->once())
            ->method('logTime')
            ->willThrowException(new \RuntimeException('API Error'));

        $input = new ToolCall(
            id: 'test-call-3',
            name: 'log_time',
            arguments: [
                'issue_id' => 123,
                'hours' => 2.5,
                'comment' => 'Test comment',
                'activity_id' => 5,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertFalse($response['success']);
        $this->assertEquals('API Error', $response['error']);
    }
}
