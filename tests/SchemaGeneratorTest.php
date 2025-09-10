<?php

declare(strict_types=1);

namespace App\Tests;

use App\Dto\LogTimeRequest;
use App\SchemaGenerator;
use PHPUnit\Framework\TestCase;

class SchemaGeneratorTest extends TestCase
{
    private SchemaGenerator $schemaGenerator;

    protected function setUp(): void
    {
        $this->schemaGenerator = new SchemaGenerator();
    }

    public function testGenerateFromLogTimeRequest(): void
    {
        $schema = $this->schemaGenerator->generateFromClass(LogTimeRequest::class);

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);

        // Check properties exist
        $properties = (array) $schema['properties'];
        $this->assertArrayHasKey('issue_id', $properties);
        $this->assertArrayHasKey('hours', $properties);
        $this->assertArrayHasKey('comment', $properties);
        $this->assertArrayHasKey('activity_id', $properties);

        // Check required fields
        $this->assertContains('issue_id', $schema['required']);
        $this->assertContains('hours', $schema['required']);
        $this->assertContains('comment', $schema['required']);
        $this->assertContains('activity_id', $schema['required']);

        // Check property types
        $this->assertEquals('integer', $properties['issue_id']['type']);
        $this->assertEquals('number', $properties['hours']['type']);
        $this->assertEquals('string', $properties['comment']['type']);
        $this->assertEquals('integer', $properties['activity_id']['type']);
    }

    public function testGeneratePropertyDescription(): void
    {
        $schema = $this->schemaGenerator->generateFromClass(LogTimeRequest::class);
        $properties = (array) $schema['properties'];

        // Check descriptions are generated from property names
        $this->assertEquals('Issue id', $properties['issue_id']['description']);
        $this->assertEquals('Activity id', $properties['activity_id']['description']);
    }

    public function testValidationConstraints(): void
    {
        $schema = $this->schemaGenerator->generateFromClass(LogTimeRequest::class);
        $properties = (array) $schema['properties'];

        // Check Range constraint on hours
        $this->assertArrayHasKey('minimum', $properties['hours']);
        $this->assertArrayHasKey('maximum', $properties['hours']);
        $this->assertEquals(0.1, $properties['hours']['minimum']);
        $this->assertEquals(24, $properties['hours']['maximum']);

        // Check Length constraint on comment
        $this->assertArrayHasKey('maxLength', $properties['comment']);
        $this->assertEquals(1000, $properties['comment']['maxLength']);

        // Check Positive constraint (minimum > 0)
        $this->assertArrayHasKey('minimum', $properties['issue_id']);
        $this->assertEquals(0.1, $properties['issue_id']['minimum']);
    }
}
