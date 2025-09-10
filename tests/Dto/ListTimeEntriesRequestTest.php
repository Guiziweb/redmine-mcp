<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\ListTimeEntriesRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class ListTimeEntriesRequestTest extends TestCase
{
    public function testValidTimeEntriesRequest(): void
    {
        $request = new ListTimeEntriesRequest(
            from: '2023-11-01',
            to: '2023-11-30',
            limit: 50,
            projectId: 123
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertCount(0, $violations);
    }

    public function testValidWithDefaults(): void
    {
        $request = new ListTimeEntriesRequest();

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertCount(0, $violations);
        $this->assertNull($request->from);
        $this->assertNull($request->to);
        $this->assertEquals(100, $request->limit);
        $this->assertNull($request->projectId);
    }

    public function testInvalidDates(): void
    {
        $request = new ListTimeEntriesRequest(
            from: 'invalid-date',
            to: '2023-13-45'
        );

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidLimit(): void
    {
        $request = new ListTimeEntriesRequest(limit: 0);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testLimitTooHigh(): void
    {
        $request = new ListTimeEntriesRequest(limit: 150);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidProjectId(): void
    {
        $request = new ListTimeEntriesRequest(projectId: -1);

        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
    }

    public function testFromArray(): void
    {
        $data = [
            'from' => '2023-11-01',
            'to' => '2023-11-30',
            'limit' => 50,
            'project_id' => 123,
        ];

        $request = ListTimeEntriesRequest::fromArray($data);

        $this->assertEquals('2023-11-01', $request->from);
        $this->assertEquals('2023-11-30', $request->to);
        $this->assertEquals(50, $request->limit);
        $this->assertEquals(123, $request->projectId);
    }

    public function testFromArrayWithDefaults(): void
    {
        $request = ListTimeEntriesRequest::fromArray([]);

        $this->assertNull($request->from);
        $this->assertNull($request->to);
        $this->assertEquals(100, $request->limit);
        $this->assertNull($request->projectId);
    }
}
