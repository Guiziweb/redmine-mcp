<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\TimeEntryClient;
use App\Tools\LogTimeTool;
use PHPUnit\Framework\TestCase;

class LogTimeToolTest extends TestCase
{
    public function testLogTimeReturnsArray(): void
    {
        $expectedResult = [
            'time_entry' => [
                'id' => 123,
                'hours' => 2.5,
                'comments' => 'Fixed bug',
            ],
        ];

        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('logTime')
            ->with(42, 2.5, 'Fixed bug', null)
            ->willReturn($expectedResult);

        $tool = new LogTimeTool($timeEntryClient);
        $result = $tool->logTime(
            issue_id: 42,
            hours: 2.5,
            comment: 'Fixed bug'
        );

        $this->assertEquals(123, $result['time_entry']['id']);
        $this->assertEquals(2.5, $result['time_entry']['hours']);
    }

    public function testLogTimeWithActivity(): void
    {
        $expectedResult = ['time_entry' => ['id' => 456]];

        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('logTime')
            ->with(42, 1.0, 'Testing', 9)
            ->willReturn($expectedResult);

        $tool = new LogTimeTool($timeEntryClient);
        $result = $tool->logTime(
            issue_id: 42,
            hours: 1.0,
            comment: 'Testing',
            activity_id: 9
        );

        $this->assertArrayHasKey('time_entry', $result);
    }

    public function testLogTimeThrowsExceptionOnClientError(): void
    {
        $timeEntryClient = $this->createMock(TimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('logTime')
            ->willThrowException(new \RuntimeException('API Error'));

        $tool = new LogTimeTool($timeEntryClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');

        $tool->logTime(
            issue_id: 42,
            hours: 1.0,
            comment: 'Test'
        );
    }
}