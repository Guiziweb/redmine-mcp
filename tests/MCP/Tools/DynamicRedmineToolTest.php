<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicRedmineTool;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class DynamicRedmineToolTest extends TestCase
{
    private DynamicRedmineTool $tool;
    private array $openApiSpec;

    protected function setUp(): void
    {
        // Charger le fichier OpenAPI de test
        $yamlPath = __DIR__.'/../../../redmine_openapi.yml';
        $this->openApiSpec = Yaml::parseFile($yamlPath);

        // Créer des mocks simples
        $redmineService = $this->createMock(RedmineService::class);
        $logger = $this->createMock(LoggerInterface::class);

        // Créer une instance de test de DynamicRedmineTool
        $this->tool = new class($redmineService, $logger, '/issues.{format}', 'get') extends DynamicRedmineTool {
            public function getName(): string
            {
                return 'test_get_issues';
            }

            public function getDescription(): string
            {
                return 'Test tool for issues';
            }

            public function getTitle(): ?string
            {
                return 'Test Issues Tool';
            }
        };
    }

    public function testInputSchemaGeneration(): void
    {
        $schema = $this->tool->getInputSchema();

        // Vérifier la structure du schéma
        $this->assertEquals('object', $schema['type']);
        $this->assertInstanceOf(\stdClass::class, $schema['properties']);

        // Debug: afficher les propriétés disponibles
        $properties = $schema['properties'];
        $availableProperties = [];
        foreach (get_object_vars($properties) as $name => $property) {
            $availableProperties[] = $name;
        }

        echo 'Propriétés disponibles dans le schéma: '.implode(', ', $availableProperties)."\n";

        // Vérifier que les propriétés importantes sont présentes
        // Note: format est un paramètre de chemin, pas de requête, donc il n'apparaît pas dans le schéma
        $this->assertTrue(property_exists($properties, 'offset'), 'La propriété offset doit être présente');
        $this->assertTrue(property_exists($properties, 'limit'), 'La propriété limit doit être présente');
    }

    public function testOpenApiSpecLoading(): void
    {
        // Vérifier que le fichier OpenAPI est bien chargé
        $this->assertIsArray($this->openApiSpec);
        $this->assertArrayHasKey('paths', $this->openApiSpec);
        $this->assertArrayHasKey('components', $this->openApiSpec);

        // Vérifier que l'endpoint /issues.{format} existe
        $this->assertArrayHasKey('/issues.{format}', $this->openApiSpec['paths']);

        // Vérifier que les paramètres offset et limit sont définis dans les composants
        $this->assertArrayHasKey('parameters', $this->openApiSpec['components']);
        $this->assertArrayHasKey('offset', $this->openApiSpec['components']['parameters']);
        $this->assertArrayHasKey('limit', $this->openApiSpec['components']['parameters']);
    }

    public function testParameterResolution(): void
    {
        // Vérifier que les paramètres offset et limit sont bien définis
        $offsetParam = $this->openApiSpec['components']['parameters']['offset'];
        $limitParam = $this->openApiSpec['components']['parameters']['limit'];

        $this->assertEquals('offset', $offsetParam['name']);
        $this->assertEquals('query', $offsetParam['in']);
        $this->assertEquals('integer', $offsetParam['schema']['type']);

        $this->assertEquals('limit', $limitParam['name']);
        $this->assertEquals('query', $limitParam['in']);
        $this->assertEquals('integer', $limitParam['schema']['type']);
    }

    public function testEndpointDefinition(): void
    {
        $endpoint = $this->openApiSpec['paths']['/issues.{format}'];

        // Vérifier que l'endpoint a une méthode GET
        $this->assertArrayHasKey('get', $endpoint);

        // Vérifier que les paramètres sont bien référencés
        $parameters = $endpoint['get']['parameters'];
        $hasOffset = false;
        $hasLimit = false;

        foreach ($parameters as $param) {
            if (isset($param['$ref']) && '#/components/parameters/offset' === $param['$ref']) {
                $hasOffset = true;
            }
            if (isset($param['$ref']) && '#/components/parameters/limit' === $param['$ref']) {
                $hasLimit = true;
            }
        }

        $this->assertTrue($hasOffset, 'Le paramètre offset doit être référencé');
        $this->assertTrue($hasLimit, 'Le paramètre limit doit être référencé');
    }
}
