<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\CachedProjectClient;
use App\SchemaGenerator;
use App\Tools\ListProjectsTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListProjectsToolTest extends TestCase
{
    private ListProjectsTool $tool;
    private CachedProjectClient|MockObject $projectClient;
    private ValidatorInterface $validator;
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->projectClient = $this->createMock(CachedProjectClient::class);
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->schemaGenerator = new SchemaGenerator();

        $this->tool = new ListProjectsTool(
            $this->projectClient,
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_list_projects', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->tool->getDescription();
        $this->assertStringContainsString('List all Redmine projects', $description);
        $this->assertStringContainsString('COMPLETE list', $description);
        $this->assertStringContainsString('ASK THE USER', $description);
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);

        // ListProjectsRequest has no parameters, so properties should be empty
        $properties = (array) $schema['properties'];
        $this->assertEmpty($properties);
    }

    public function testSuccessfulListProjects(): void
    {
        $mockProjects = [
            [
                'id' => 1,
                'name' => 'Project 1',
                'identifier' => 'proj1',
                'description' => 'First project',
                'status' => 'active',
                'created_on' => '2023-01-01',
                'updated_on' => '2023-02-01',
            ],
            [
                'id' => 2,
                'name' => 'Project 2',
                'identifier' => 'proj2',
                'description' => 'Second project',
                'status' => 'active',
                'created_on' => '2023-01-02',
                'updated_on' => '2023-02-02',
            ],
        ];

        $this->projectClient
            ->expects($this->once())
            ->method('getMyProjects')
            ->willReturn($mockProjects);

        $input = new ToolCall(
            id: 'test-call-1',
            name: 'list_projects',
            arguments: []
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $response = json_decode($result->result, true);
        $this->assertIsArray($response);
        $this->assertCount(2, $response);

        // Check project data
        $this->assertEquals(1, $response[0]['id']);
        $this->assertEquals('Project 1', $response[0]['name']);
        $this->assertEquals('proj1', $response[0]['identifier']);
    }

    public function testRepositoryException(): void
    {
        $this->projectClient
            ->expects($this->once())
            ->method('getMyProjects')
            ->willThrowException(new \RuntimeException('API Error'));

        $input = new ToolCall(
            id: 'test-call-2',
            name: 'list_projects',
            arguments: []
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertFalse($response['success']);
        $this->assertEquals('API Error', $response['error']);
        $this->assertEquals('Failed to list projects', $response['message']);
    }
}
