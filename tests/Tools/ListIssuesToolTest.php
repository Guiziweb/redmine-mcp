<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\IssueClient;
use App\SchemaGenerator;
use App\Tools\ListIssuesTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListIssuesToolTest extends TestCase
{
    private ListIssuesTool $tool;
    private IssueClient|MockObject $issueClient;
    private ValidatorInterface $validator;
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->issueClient = $this->createMock(IssueClient::class);
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->schemaGenerator = new SchemaGenerator();

        $this->tool = new ListIssuesTool(
            $this->issueClient,
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_list_issues', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->tool->getDescription();
        $this->assertStringContainsString('List Redmine issues', $description);
        $this->assertStringContainsString('IMPORTANT', $description);
        $this->assertStringContainsString('ASK THE USER', $description);
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);

        // Check required fields
        $this->assertContains('project_id', $schema['required']);
    }

    public function testSuccessfulGetMyIssues(): void
    {
        $mockIssues = [
            ['id' => 1, 'subject' => 'Test issue 1'],
            ['id' => 2, 'subject' => 'Test issue 2'],
        ];

        $this->issueClient
            ->expects($this->once())
            ->method('getMyIssues')
            ->with(25, 123)
            ->willReturn($mockIssues);

        $input = new ToolCall(
            id: 'test-call-1',
            name: 'get_my_issues',
            arguments: [
                'project_id' => 123,
                'limit' => 25,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $response = json_decode($result->result, true);
        $this->assertIsArray($response);
        $this->assertCount(2, $response);
    }

    public function testValidationErrorMissingProjectId(): void
    {
        $input = new ToolCall(
            id: 'test-call-2',
            name: 'get_my_issues',
            arguments: [
                // Missing project_id - should fail validation
                'limit' => 25,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertStringContainsString('Validation failed', $response['error']);
    }

    public function testValidationErrorInvalidProjectId(): void
    {
        $input = new ToolCall(
            id: 'test-call-3',
            name: 'get_my_issues',
            arguments: [
                'project_id' => 0, // Invalid: must be positive
                'limit' => 25,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertStringContainsString('Validation failed', $response['error']);
    }

    public function testRepositoryException(): void
    {
        $this->issueClient
            ->expects($this->once())
            ->method('getMyIssues')
            ->willThrowException(new \RuntimeException('API Error'));

        $input = new ToolCall(
            id: 'test-call-4',
            name: 'get_my_issues',
            arguments: [
                'project_id' => 123,
                'limit' => 25,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertEquals('API Error', $response['error']);
    }
}
