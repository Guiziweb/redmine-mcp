<?php

declare(strict_types=1);

namespace App\Tests\Tools;

use App\Client\CachedProjectClient;
use App\Tools\ListProjectsTool;
use PHPUnit\Framework\TestCase;

class ListProjectsToolTest extends TestCase
{
    public function testListProjectsReturnsJsonString(): void
    {
        $mockProjects = [
            ['id' => 1, 'name' => 'Project 1', 'identifier' => 'proj1'],
            ['id' => 2, 'name' => 'Project 2', 'identifier' => 'proj2'],
        ];

        $projectClient = $this->createMock(CachedProjectClient::class);
        $projectClient->expects($this->once())
            ->method('getMyProjects')
            ->willReturn($mockProjects);

        $tool = new ListProjectsTool($projectClient);
        $result = $tool->listProjects();

        // Verify it's valid JSON
        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
        $this->assertEquals('Project 1', $decoded[0]['name']);
    }

    public function testListProjectsHandlesEmptyArray(): void
    {
        $projectClient = $this->createMock(CachedProjectClient::class);
        $projectClient->expects($this->once())
            ->method('getMyProjects')
            ->willReturn([]);

        $tool = new ListProjectsTool($projectClient);
        $result = $tool->listProjects();

        $decoded = json_decode($result, true);
        $this->assertIsArray($decoded);
        $this->assertEmpty($decoded);
    }

    public function testListProjectsThrowsExceptionOnClientError(): void
    {
        $projectClient = $this->createMock(CachedProjectClient::class);
        $projectClient->expects($this->once())
            ->method('getMyProjects')
            ->willThrowException(new \RuntimeException('API Error'));

        $tool = new ListProjectsTool($projectClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('API Error');

        $tool->listProjects();
    }
}