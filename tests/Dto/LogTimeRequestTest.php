<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\LogTimeRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LogTimeRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidLogTimeRequest(): void
    {
        $request = LogTimeRequest::fromArray([
            'issue_id' => 123,
            'hours' => 2.5,
            'comment' => 'Working on feature X',
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);
    }

    public function testInvalidIssueId(): void
    {
        $request = LogTimeRequest::fromArray([
            'issue_id' => 0, // Invalid: must be positive
            'hours' => 2.5,
            'comment' => 'Working on feature X',
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        $this->assertContains('Issue ID must be a positive integer', $violationMessages);
    }

    public function testInvalidHours(): void
    {
        // Test minimum hours
        $request = LogTimeRequest::fromArray([
            'issue_id' => 123,
            'hours' => 0.05, // Invalid: below minimum
            'comment' => 'Working on feature X',
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());

        // Test maximum hours
        $request = LogTimeRequest::fromArray([
            'issue_id' => 123,
            'hours' => 25, // Invalid: above maximum
            'comment' => 'Working on feature X',
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testEmptyComment(): void
    {
        $request = LogTimeRequest::fromArray([
            'issue_id' => 123,
            'hours' => 2.5,
            'comment' => '', // Invalid: cannot be empty
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testCommentTooLong(): void
    {
        $request = LogTimeRequest::fromArray([
            'issue_id' => 123,
            'hours' => 2.5,
            'comment' => str_repeat('a', 1001), // Invalid: too long
            'activity_id' => 5,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testFromArrayWithDefaults(): void
    {
        $request = LogTimeRequest::fromArray([]);

        $this->assertEquals(0, $request->issueId);
        $this->assertEquals(0.0, $request->hours);
        $this->assertEquals('', $request->comment);
        $this->assertEquals(0, $request->activityId);
    }
}
