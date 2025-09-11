<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\GetIssueDetailsRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GetIssueDetailsRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidGetIssueDetailsRequest(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
            'include' => ['attachments', 'journals'],
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);

        $this->assertEquals(123, $request->issueId);
        $this->assertEquals(['attachments', 'journals'], $request->include);
    }

    public function testValidWithEmptyInclude(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);

        $this->assertEquals(123, $request->issueId);
        $this->assertEquals([], $request->include); // Default empty array
    }

    public function testValidWithAllIncludeOptions(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
            'include' => ['children', 'attachments', 'relations', 'changesets', 'journals', 'watchers', 'allowed_statuses'],
        ]);

        $violations = $this->validator->validate($request);
        $this->assertCount(0, $violations);

        $this->assertEquals(123, $request->issueId);
        $this->assertCount(7, $request->include);
    }

    public function testInvalidIssueId(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 0, // Invalid: must be positive
            'include' => ['attachments'],
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testInvalidIncludeOption(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
            'include' => ['invalid_option'], // Invalid option
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testMixedValidAndInvalidIncludeOptions(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
            'include' => ['attachments', 'invalid_option'], // One valid, one invalid
        ]);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count());
    }

    public function testFromArrayWithDefaults(): void
    {
        $request = GetIssueDetailsRequest::fromArray([]);

        // Should have default values but fail validation due to missing required issue_id
        $this->assertEquals(0, $request->issueId);
        $this->assertEquals([], $request->include);

        $violations = $this->validator->validate($request);
        $this->assertGreaterThan(0, $violations->count()); // Should fail validation
    }

    public function testFromArrayWithNonArrayInclude(): void
    {
        $request = GetIssueDetailsRequest::fromArray([
            'issue_id' => 123,
            'include' => 'not_an_array', // Will be converted to empty array
        ]);

        $this->assertEquals(123, $request->issueId);
        $this->assertEquals([], $request->include); // Should default to empty array
    }
}
