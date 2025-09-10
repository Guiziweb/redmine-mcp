<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\CachedTimeEntryClient;
use App\SchemaGenerator;
use App\Tools\ListTimeActivitiesTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListTimeActivitiesToolTest extends TestCase
{
    private ListTimeActivitiesTool $tool;
    private CachedTimeEntryClient|MockObject $timeEntryClient;
    private ValidatorInterface $validator;
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->timeEntryClient = $this->createMock(CachedTimeEntryClient::class);
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->schemaGenerator = new SchemaGenerator();

        $this->tool = new ListTimeActivitiesTool(
            $this->timeEntryClient,
            $this->validator,
            $this->schemaGenerator
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_list_activities', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->tool->getDescription();
        $this->assertStringContainsString('List available time entry activities', $description);
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);

        // ListTimeActivitiesRequest has no parameters
        $properties = (array) $schema['properties'];
        $this->assertEmpty($properties);
    }

    public function testSuccessfulListTimeActivities(): void
    {
        $mockActivities = [
            ['id' => 1, 'name' => 'Development'],
            ['id' => 2, 'name' => 'Testing'],
            ['id' => 3, 'name' => 'Documentation'],
        ];

        $this->timeEntryClient
            ->expects($this->once())
            ->method('getTimeEntryActivities')
            ->willReturn($mockActivities);

        $input = new ToolCall(
            id: 'test-call-1',
            name: 'list_time_activities',
            arguments: []
        );

        $result = $this->tool->call($input);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $response = json_decode($result->result, true);
        $this->assertIsArray($response);
        $this->assertCount(3, $response);

        // Check activities data
        $this->assertEquals(1, $response[0]['id']);
        $this->assertEquals('Development', $response[0]['name']);
    }

    public function testRepositoryException(): void
    {
        $this->timeEntryClient
            ->expects($this->once())
            ->method('getTimeEntryActivities')
            ->willThrowException(new \RuntimeException('API Error'));

        $input = new ToolCall(
            id: 'test-call-2',
            name: 'list_time_activities',
            arguments: []
        );

        $result = $this->tool->call($input);

        $this->assertTrue($result->isError);
        $response = json_decode($result->result, true);
        $this->assertFalse($response['success']);
        $this->assertEquals('API Error', $response['error']);
        $this->assertEquals('Failed to fetch time entry activities', $response['message']);
    }
}
