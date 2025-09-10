<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\TimeEntryClient;
use App\SchemaGenerator;
use App\Tools\ListTimeEntriesTool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\AI\McpSdk\Capability\Tool\ToolCall;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListTimeEntriesToolTest extends TestCase
{
    private ListTimeEntriesTool $tool;
    private TimeEntryClient|MockObject $timeEntryClient;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->timeEntryClient = $this->createMock(TimeEntryClient::class);
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->tool = new ListTimeEntriesTool(
            $this->timeEntryClient,
            $this->validator,
            new SchemaGenerator()
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('redmine_get_my_time_entries', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertStringContainsString('time entries', $this->tool->getDescription());
        $this->assertStringContainsString('work hour analysis', $this->tool->getDescription());
    }

    public function testGetInputSchema(): void
    {
        $schema = $this->tool->getInputSchema();

        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }

    public function testSuccessfulGetTimeEntries(): void
    {
        $mockTimeEntries = [
            [
                'id' => 1,
                'hours' => 8.0,
                'spent_on' => '2023-11-01',
                'comments' => 'Development work',
                'project' => ['id' => 1, 'name' => 'Test Project'],
                'activity' => ['id' => 1, 'name' => 'Development'],
                'created_on' => '2023-11-01T10:00:00Z',
                'updated_on' => '2023-11-01T10:00:00Z',
            ],
            [
                'id' => 2,
                'hours' => 4.0,
                'spent_on' => '2023-11-02',
                'comments' => 'Bug fixing',
                'project' => ['id' => 1, 'name' => 'Test Project'],
                'activity' => ['id' => 2, 'name' => 'Debug'],
                'created_on' => '2023-11-02T14:00:00Z',
                'updated_on' => '2023-11-02T14:00:00Z',
            ],
        ];

        $this->timeEntryClient
            ->expects($this->once())
            ->method('getMyTimeEntries')
            ->with(100, '2023-11-01', '2023-11-30', null)
            ->willReturn($mockTimeEntries);

        $toolCall = new ToolCall(
            id: 'test-1',
            name: 'redmine_get_my_time_entries',
            arguments: [
                'from' => '2023-11-01',
                'to' => '2023-11-30',
            ]
        );

        $result = $this->tool->call($toolCall);

        $this->assertFalse($result->isError);
        $this->assertEquals('application/json', $result->mimeType);

        $decoded = json_decode($result->result, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('time_entries', $decoded);
        $this->assertArrayHasKey('summary', $decoded);

        // Verify calculations
        $summary = $decoded['summary'];
        $this->assertEquals(12.0, $summary['total_hours']);
        $this->assertEquals(2, $summary['total_entries']);
        $this->assertEquals(2, $summary['working_days']);
        $this->assertEquals(6.0, $summary['average_hours_per_day']);

        // Verify breakdowns
        $this->assertEquals(['Test Project' => 12.0], $summary['project_breakdown']);
        $this->assertEquals(['2023-W44' => 12.0], $summary['weekly_breakdown']); // Both dates are in week 44 of 2023
        $this->assertEquals(['2023-11-01' => 8.0, '2023-11-02' => 4.0], $summary['daily_breakdown']);
    }

    public function testContractCompliant(): void
    {
        $mockTimeEntries = array_fill(0, 5, [
            'id' => 1,
            'hours' => 7.0,
            'spent_on' => '2023-11-01',
            'comments' => 'Work',
            'project' => ['id' => 1, 'name' => 'Project'],
            'activity' => ['id' => 1, 'name' => 'Development'],
            'created_on' => '2023-11-01T10:00:00Z',
            'updated_on' => '2023-11-01T10:00:00Z',
        ]);

        $this->timeEntryClient
            ->expects($this->once())
            ->method('getMyTimeEntries')
            ->willReturn($mockTimeEntries);

        $toolCall = new ToolCall(
            id: 'test-2',
            name: 'redmine_get_my_time_entries',
            arguments: []
        );

        $result = $this->tool->call($toolCall);
        $decoded = json_decode($result->result, true);

        $this->assertEquals(35.0, $decoded['summary']['total_hours']);
    }

    public function testValidationError(): void
    {
        $toolCall = new ToolCall(
            id: 'test-3',
            name: 'redmine_get_my_time_entries',
            arguments: [
                'from' => 'invalid-date',
                'limit' => -1,
            ]
        );

        $result = $this->tool->call($toolCall);

        $this->assertTrue($result->isError);
        $decoded = json_decode($result->result, true);
        $this->assertFalse($decoded['success']);
        $this->assertStringContainsString('Validation failed', $decoded['error']);
    }

    public function testRepositoryException(): void
    {
        $this->timeEntryClient
            ->expects($this->once())
            ->method('getMyTimeEntries')
            ->willThrowException(new \RuntimeException('API Error'));

        $toolCall = new ToolCall(
            id: 'test-4',
            name: 'redmine_get_my_time_entries',
            arguments: []
        );

        $result = $this->tool->call($toolCall);

        $this->assertTrue($result->isError);
        $decoded = json_decode($result->result, true);
        $this->assertFalse($decoded['success']);
        $this->assertEquals('API Error', $decoded['error']);
    }

    public function testWithProjectFilter(): void
    {
        $this->timeEntryClient
            ->expects($this->once())
            ->method('getMyTimeEntries')
            ->with(100, null, null, 123)
            ->willReturn([]);

        $toolCall = new ToolCall(
            id: 'test-5',
            name: 'redmine_get_my_time_entries',
            arguments: [
                'project_id' => 123,
            ]
        );

        $result = $this->tool->call($toolCall);

        $this->assertFalse($result->isError);
        $decoded = json_decode($result->result, true);
        $this->assertEquals(123, $decoded['period']['project_filter']);
    }
}
