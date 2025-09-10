<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Generates JSON Schema from Symfony Validator attributes.
 */
final class SchemaGenerator
{
    /**
     * @return array<string, mixed>
     */
    public function generateFromClass(string $className): array
    {
        /** @var class-string $className */
        $reflection = new \ReflectionClass($className);
        $properties = [];
        $required = [];

        foreach ($reflection->getProperties() as $property) {
            $propertySchema = $this->generatePropertySchema($property);

            $properties[$this->getJsonPropertyName($property->getName())] = $propertySchema['schema'];

            if ($propertySchema['required']) {
                $required[] = $this->getJsonPropertyName($property->getName());
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => (object) $properties,
        ];

        if (! empty($required)) {
            $schema['required'] = $required;
        }

        return $schema;
    }

    /**
     * @return array{schema: array<string, mixed>, required: bool}
     */
    private function generatePropertySchema(\ReflectionProperty $property): array
    {
        $attributes = $property->getAttributes();
        $schema = [];
        $required = false;

        // Get base type from property type
        $type = $property->getType();
        if ($type && $type instanceof \ReflectionNamedType) {
            $typeName = $type->getName();
            $schema['type'] = $this->mapPhpTypeToJsonSchema($typeName);
        }

        // Process validation attributes
        foreach ($attributes as $attribute) {
            $constraint = $attribute->newInstance();

            $this->processConstraint($constraint, $schema, $required);
        }

        // Add description from property name (camelCase to sentence)
        $schema['description'] = $this->generateDescription($property->getName());

        return [
            'schema' => $schema,
            'required' => $required,
        ];
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function processConstraint(mixed $constraint, array &$schema, bool &$required): void
    {
        switch (true) {
            case $constraint instanceof Assert\NotNull:
            case $constraint instanceof Assert\NotBlank:
                $required = true;

                break;

            case $constraint instanceof Assert\Range:
                if (null !== $constraint->min) {
                    $schema['minimum'] = $constraint->min;
                }
                if (null !== $constraint->max) {
                    $schema['maximum'] = $constraint->max;
                }

                break;

            case $constraint instanceof Assert\Length:
                if (null !== $constraint->max) {
                    $schema['maxLength'] = $constraint->max;
                }
                if (null !== $constraint->min) {
                    $schema['minLength'] = $constraint->min;
                }

                break;

            case $constraint instanceof Assert\Positive:
                $schema['minimum'] = 0.1; // Assuming positive means > 0

                break;

            case $constraint instanceof Assert\Date:
                $schema['format'] = 'date';

                break;
        }
    }

    private function mapPhpTypeToJsonSchema(string $phpType): string
    {
        return match ($phpType) {
            'int' => 'integer',
            'float' => 'number',
            'string' => 'string',
            'bool' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }

    private function getJsonPropertyName(string $propertyName): string
    {
        // Convert camelCase to snake_case
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $propertyName));
    }

    private function generateDescription(string $propertyName): string
    {
        // Convert camelCase to readable description
        $description = preg_replace('/([a-z])([A-Z])/', '$1 $2', $propertyName);

        return ucfirst(strtolower($description));
    }
}
