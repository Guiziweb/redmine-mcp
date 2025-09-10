<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\DtoInterface;
use App\Dto\ListIssuesRequest;
use App\Dto\ListProjectsRequest;
use App\Dto\ListTimeActivitiesRequest;
use App\Dto\LogTimeRequest;
use PHPUnit\Framework\TestCase;

class DtoInterfaceTest extends TestCase
{
    /**
     * @dataProvider dtoClassesProvider
     */
    public function testDtoImplementsInterface(string $dtoClass): void
    {
        $this->assertInstanceOf(DtoInterface::class, $dtoClass::fromArray([]));
    }

    /**
     * @dataProvider dtoClassesProvider
     * @param class-string $dtoClass
     */
    public function testFromArrayReturnsCorrectType(string $dtoClass): void
    {
        $dto = $dtoClass::fromArray([]);
        $this->assertInstanceOf($dtoClass, $dto);
    }

    public function testListIssuesRequestFromArray(): void
    {
        $data = [
            'project_id' => 123,
            'limit' => 50,
        ];

        $dto = ListIssuesRequest::fromArray($data);

        $this->assertInstanceOf(ListIssuesRequest::class, $dto);
        $this->assertEquals(123, $dto->projectId);
        $this->assertEquals(50, $dto->limit);
    }

    public function testLogTimeRequestFromArray(): void
    {
        $data = [
            'issue_id' => 456,
            'hours' => 2.5,
            'comment' => 'Work done',
            'activity_id' => 1,
        ];

        $dto = LogTimeRequest::fromArray($data);

        $this->assertInstanceOf(LogTimeRequest::class, $dto);
        $this->assertEquals(456, $dto->issueId);
        $this->assertEquals(2.5, $dto->hours);
        $this->assertEquals('Work done', $dto->comment);
        $this->assertEquals(1, $dto->activityId);
    }

    public function testEmptyDtosFromArray(): void
    {
        $projectsDto = ListProjectsRequest::fromArray([]);
        $activitiesDto = ListTimeActivitiesRequest::fromArray([]);

        $this->assertInstanceOf(ListProjectsRequest::class, $projectsDto);
        $this->assertInstanceOf(ListTimeActivitiesRequest::class, $activitiesDto);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function dtoClassesProvider(): array
    {
        return [
            'ListIssuesRequest' => [ListIssuesRequest::class],
            'LogTimeRequest' => [LogTimeRequest::class],
            'ListProjectsRequest' => [ListProjectsRequest::class],
            'ListTimeActivitiesRequest' => [ListTimeActivitiesRequest::class],
        ];
    }
}
