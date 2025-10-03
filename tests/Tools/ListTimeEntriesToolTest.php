<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\TimeEntryClient;
use App\Tools\ListTimeEntriesTool;
use PHPUnit\Framework\TestCase;

class ListTimeEntriesToolTest extends TestCase
{
    public function testListTimeEntriesReturnsArrayWithSummary(): void
    {
        $mockEntries = [
            ['id' => 1, 'hours' => 2.5, 'spent_on' => '2024-01-15', 'project' => ['name' => 'Project A']],
            ['id' => 2, 'hours' => 3.0, 'spent_on' => '2024-01-15', 'project' => ['name' => 'Project A']],
            ['id' => 3, 'hours' => 1.5, 'spent_on' => '2024-01-16', 'project' => ['name' => 'Project B']],
        ];

        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getMyTimeEntries')
            ->with(100, null, null, null)
            ->willReturn($mockEntries);

        $tool = new ListTimeEntriesTool($timeEntryClient);
        $result = $tool->listTimeEntries();

        $this->assertArrayHasKey('time_entries', $result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('period', $result);

        // Check summary
        $this->assertEquals(7.0, $result['summary']['total_hours']);
        $this->assertEquals(3, $result['summary']['total_entries']);
        $this->assertEquals(2, $result['summary']['working_days']);
        $this->assertEquals(3.5, $result['summary']['average_hours_per_day']);

        // Check project breakdown
        $this->assertEquals(5.5, $result['summary']['project_breakdown']['Project A']);
        $this->assertEquals(1.5, $result['summary']['project_breakdown']['Project B']);

        // Check daily breakdown
        $this->assertEquals(5.5, $result['summary']['daily_breakdown']['2024-01-15']);
        $this->assertEquals(1.5, $result['summary']['daily_breakdown']['2024-01-16']);
    }

    public function testListTimeEntriesWithDateFilter(): void
    {
        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getMyTimeEntries')
            ->with(100, '2024-01-01', '2024-01-31', null)
            ->willReturn([]);

        $tool = new ListTimeEntriesTool($timeEntryClient);
        $result = $tool->listTimeEntries(
            from: '2024-01-01',
            to: '2024-01-31'
        );

        $this->assertEquals('2024-01-01', $result['period']['from']);
        $this->assertEquals('2024-01-31', $result['period']['to']);
    }

    public function testListTimeEntriesWithProjectFilter(): void
    {
        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getMyTimeEntries')
            ->with(100, null, null, 42)
            ->willReturn([]);

        $tool = new ListTimeEntriesTool($timeEntryClient);
        $result = $tool->listTimeEntries(project_id: 42);

        $this->assertEquals(42, $result['period']['project_filter']);
    }

    public function testListTimeEntriesHandlesEmptyArray(): void
    {
        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getMyTimeEntries')
            ->willReturn([]);

        $tool = new ListTimeEntriesTool($timeEntryClient);
        $result = $tool->listTimeEntries();

        $this->assertEquals(0, $result['summary']['total_hours']);
        $this->assertEquals(0, $result['summary']['total_entries']);
        $this->assertEquals(0, $result['summary']['working_days']);
    }

    public function testListTimeEntriesThrowsExceptionOnClientError(): void
    {
        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getMyTimeEntries')
            ->willThrowException(new \RuntimeException('API Error'));

        $tool = new ListTimeEntriesTool($timeEntryClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');

        $tool->listTimeEntries();
    }
}
