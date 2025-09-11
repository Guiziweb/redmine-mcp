<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\IssueClient;
use App\SchemaGenerator;
use App\Tools\GetIssueDetailsTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GetIssueDetailsToolTest extends TestCase
{
    private GetIssueDetailsTool $tool;
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

        $this->tool = new GetIssueDetailsTool(
            $this->issueClient,
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_get_issue_details', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->tool->getDescription();
        $this->assertStringContainsString('Get detailed information', $description);
        $this->assertStringContainsString('specific Redmine issue', $description);
        $this->assertStringContainsString('comprehensive issue data', $description);
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);

        // Check required fields
        $this->assertContains('issue_id', $schema['required']);
    }

    public function testSuccessfulGetIssueDetails(): void
    {
        $mockIssueDetails = [
            'id' => 123,
            'subject' => 'Test issue',
            'description' => 'Test description',
            'status' => ['id' => 1, 'name' => 'New'],
            'project' => ['id' => 1, 'name' => 'Test Project'],
            'attachments' => [],
            'journals' => [],
        ];

        $this->issueClient
            ->expects($this->once())
            ->method('getIssueDetails')
            ->with(123, ['attachments', 'journals'])
            ->willReturn($mockIssueDetails);

        $input = new ToolCall(
            id: 'test-call-1',
            name: 'get_issue_details',
            arguments: [
                'issue_id' => 123,
                'include' => ['attachments', 'journals'],
            ]
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $response = json_decode($result->result, true);
        $this->assertIsArray($response);
        $this->assertEquals(123, $response['id']);
        $this->assertEquals('Test issue', $response['subject']);
    }

    public function testSuccessfulGetIssueDetailsWithoutInclude(): void
    {
        $mockIssueDetails = [
            'id' => 123,
            'subject' => 'Test issue',
            'description' => 'Test description',
            'status' => ['id' => 1, 'name' => 'New'],
        ];

        $this->issueClient
            ->expects($this->once())
            ->method('getIssueDetails')
            ->with(123, [])
            ->willReturn($mockIssueDetails);

        $input = new ToolCall(
            id: 'test-call-2',
            name: 'get_issue_details',
            arguments: [
                'issue_id' => 123,
            ]
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $response = json_decode($result->result, true);
        $this->assertEquals(123, $response['id']);
    }

    public function testValidationErrorMissingIssueId(): void
    {
        $input = new ToolCall(
            id: 'test-call-3',
            name: 'get_issue_details',
            arguments: [
                // Missing issue_id - should fail validation
                'include' => ['attachments'],
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertStringContainsString('Validation failed', $response['error']);
    }

    public function testValidationErrorInvalidIssueId(): void
    {
        $input = new ToolCall(
            id: 'test-call-4',
            name: 'get_issue_details',
            arguments: [
                'issue_id' => 0, // Invalid: must be positive
                'include' => ['attachments'],
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertStringContainsString('Validation failed', $response['error']);
    }

    public function testValidationErrorInvalidIncludeOption(): void
    {
        $input = new ToolCall(
            id: 'test-call-5',
            name: 'get_issue_details',
            arguments: [
                'issue_id' => 123,
                'include' => ['invalid_option'], // Invalid include option
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
            ->method('getIssueDetails')
            ->willThrowException(new \RuntimeException('API Error'));

        $input = new ToolCall(
            id: 'test-call-6',
            name: 'get_issue_details',
            arguments: [
                'issue_id' => 123,
                'include' => ['attachments'],
            ]
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertEquals('API Error', $response['error']);
    }

    public function testGetTitle(): void
    {
        $this->assertEquals('Get Issue Details', $this->tool->getTitle());
    }
}
