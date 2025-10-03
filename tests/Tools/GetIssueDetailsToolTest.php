<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\IssueClient;
use App\Tools\GetIssueDetailsTool;
use PHPUnit\Framework\TestCase;

class GetIssueDetailsToolTest extends TestCase
{
    public function testGetIssueDetailsReturnsArray(): void
    {
        $expectedDetails = [
            'issue' => [
                'id' => 123,
                'subject' => 'Test Issue',
                'description' => 'Description here',
                'status' => ['name' => 'New'],
                'attachments' => [],
                'journals' => [],
            ],
        ];

        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getIssueDetails')
            ->with(123, ['attachments', 'relations', 'journals', 'watchers'])
            ->willReturn($expectedDetails);

        $tool = new GetIssueDetailsTool($issueClient);
        $result = $tool->getIssueDetails(123);

        $this->assertEquals(123, $result['issue']['id']);
        $this->assertEquals('Test Issue', $result['issue']['subject']);
    }

    public function testGetIssueDetailsThrowsExceptionOnClientError(): void
    {
        $issueClient = $this->createMock(IssueClient::class);
        $issueClient->expects($this->once())
            ->method('getIssueDetails')
            ->willThrowException(new \RuntimeException('Issue not found'));

        $tool = new GetIssueDetailsTool($issueClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Issue not found');

        $tool->getIssueDetails(999);
    }
}