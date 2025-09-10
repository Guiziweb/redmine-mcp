<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\ListIssuesRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ListIssuesRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidGetMyIssuesRequest(): void
    {
        $request = ListIssuesRequest::fromArray([
            'project_id' => 123,
            'limit' => 50,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);

        $this->assertEquals(123, $request->projectId);
        $this->assertEquals(50, $request->limit);
    }

    public function testValidWithDefaultLimit(): void
    {
        $request = ListIssuesRequest::fromArray([
            'project_id' => 123,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);

        $this->assertEquals(123, $request->projectId);
        $this->assertEquals(25, $request->limit); // Default value
    }

    public function testInvalidProjectId(): void
    {
        $request = ListIssuesRequest::fromArray([
            'project_id' => 0, // Invalid: must be positive
            'limit' => 25,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidLimitTooLow(): void
    {
        $request = ListIssuesRequest::fromArray([
            'project_id' => 123,
            'limit' => 0, // Invalid: below minimum
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidLimitTooHigh(): void
    {
        $request = ListIssuesRequest::fromArray([
            'project_id' => 123,
            'limit' => 101, // Invalid: above maximum
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testFromArrayWithDefaults(): void
    {
        $request = ListIssuesRequest::fromArray([]);

        // Should have default values but fail validation due to missing required project_id
        $this->assertEquals(0, $request->projectId);
        $this->assertEquals(25, $request->limit);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count()); // Should fail validation
    }
}
