<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\CachedTimeEntryClient;
use App\Tools\ListTimeActivitiesTool;
use PHPUnit\Framework\TestCase;

class ListTimeActivitiesToolTest extends TestCase
{
    public function testListActivitiesReturnsArray(): void
    {
        $mockActivities = [
            ['id' => 9, 'name' => 'Development'],
            ['id' => 10, 'name' => 'Testing'],
            ['id' => 11, 'name' => 'Documentation'],
        ];

        $timeEntryClient = $this->createMock(CachedTimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getTimeEntryActivities')
            ->willReturn($mockActivities);

        $tool = new ListTimeActivitiesTool($timeEntryClient);
        $result = $tool->listActivities();

        $this->assertCount(3, $result);
        $this->assertEquals(9, $result[0]['id']);
        $this->assertEquals('Development', $result[0]['name']);
    }

    public function testListActivitiesHandlesEmptyArray(): void
    {
        $timeEntryClient = $this->createMock(CachedTimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getTimeEntryActivities')
            ->willReturn([]);

        $tool = new ListTimeActivitiesTool($timeEntryClient);
        $result = $tool->listActivities();

        $this->assertEmpty($result);
    }

    public function testListActivitiesThrowsExceptionOnClientError(): void
    {
        $timeEntryClient = $this->createMock(CachedTimeEntryClient::class);
        $timeEntryClient->expects($this->once())
            ->method('getTimeEntryActivities')
            ->willThrowException(new \RuntimeException('API Error'));

        $tool = new ListTimeActivitiesTool($timeEntryClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');

        $tool->listActivities();
    }
}
