<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\IssueClient;
use App\Tools\ListIssuesTool;
use PHPUnit\Framework\TestCase;

class ListIssuesToolTest extends TestCase
{
    public function testListIssuesReturnsArray(): void
    {
        $mockIssues = [
            ['id' => 1, 'subject' => 'Issue 1', 'status' => ['name' => 'New']],
            ['id' => 2, 'subject' => 'Issue 2', 'status' => ['name' => 'In Progress']],
        ];

        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getMyIssues')
            ->with(25, null)
            ->willReturn($mockIssues);

        $tool = new ListIssuesTool($issueClient);
        $result = $tool->listIssues();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]['id']);
        $this->assertEquals('Issue 1', $result[0]['subject']);
    }

    public function testListIssuesWithProjectFilter(): void
    {
        $mockIssues = [
            ['id' => 3, 'subject' => 'Issue 3', 'status' => ['name' => 'New']],
        ];

        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getMyIssues')
            ->with(25, 42)
            ->willReturn($mockIssues);

        $tool = new ListIssuesTool($issueClient);
        $result = $tool->listIssues(project_id: 42);

        $this->assertCount(1, $result);
    }

    public function testListIssuesWithCustomLimit(): void
    {
        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getMyIssues')
            ->with(50, null)
            ->willReturn([]);

        $tool = new ListIssuesTool($issueClient);
        $result = $tool->listIssues(limit: 50);

        $this->assertEmpty($result);
    }

    public function testListIssuesThrowsExceptionOnClientError(): void
    {
        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getMyIssues')
            ->willThrowException(new \RuntimeException('API Error'));

        $tool = new ListIssuesTool($issueClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');

        $tool->listIssues();
    }
}
