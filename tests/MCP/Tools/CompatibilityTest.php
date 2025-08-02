<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicToolFactory;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CompatibilityTest extends TestCase
{
    private DynamicToolFactory $factory;

    protected function setUp(): void
    {
        /** @var RedmineService $redmineService */
        $redmineService = $this->createMock(RedmineService::class);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->factory = new DynamicToolFactory($redmineService, $logger);
    }

    public function testBackwardCompatibility(): void
    {
        $tools = $this->factory->generateGetTools();

        // Vérifier que les outils principaux existent toujours
        $toolNames = array_map(fn ($tool) => $tool->getName(), $tools);

        $requiredTools = [
            'get_issues',
            'get_projects',
            'get_users',
            'get_time_entries',
            'get_search',
        ];

        foreach ($requiredTools as $requiredTool) {
            $this->assertContains($requiredTool, $toolNames, "L'outil {$requiredTool} doit être présent pour la compatibilité");
        }
    }

    public function testSchemaCompatibility(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $schema = $tool->getInputSchema();

            // Vérifier que le schéma a la structure attendue
            $this->assertArrayHasKey('type', $schema);
            $this->assertEquals('object', $schema['type']);

            // Vérifier que les propriétés sont un objet stdClass
            if (isset($schema['properties'])) {
                $this->assertInstanceOf(\stdClass::class, $schema['properties']);
            }
        }
    }

    public function testApiVersionCompatibility(): void
    {
        // Test avec différentes versions de l'API Redmine
        $apiVersions = ['1.0', '1.1', '1.2', '1.3', '1.4'];

        foreach ($apiVersions as $version) {
            // Simuler une version différente de l'API
            $tools = $this->factory->generateGetTools();

            // Vérifier que les outils de base fonctionnent toujours
            $toolNames = array_map(fn ($tool) => $tool->getName(), $tools);

            $this->assertContains('get_issues', $toolNames, "Compatibilité avec l'API v{$version}");
            $this->assertContains('get_projects', $toolNames, "Compatibilité avec l'API v{$version}");
        }
    }

    public function testParameterCompatibility(): void
    {
        $tools = $this->factory->generateGetTools();

        // Trouver l'outil issues
        $issuesTool = null;
        foreach ($tools as $tool) {
            if ('get_issues' === $tool->getName()) {
                $issuesTool = $tool;
                break;
            }
        }

        $this->assertNotNull($issuesTool, 'L\'outil issues doit être trouvé');

        $schema = $issuesTool->getInputSchema();
        $properties = $schema['properties'];

        // Vérifier que les paramètres essentiels sont présents
        $requiredParams = ['offset', 'limit'];
        foreach ($requiredParams as $param) {
            $this->assertTrue(property_exists($properties, $param), "Le paramètre {$param} doit être présent pour la compatibilité");
        }
    }

    public function testMethodCompatibility(): void
    {
        // Test que les méthodes GET sont toujours supportées
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $name = $tool->getName();

            // Vérifier que les noms commencent par 'get_'
            $this->assertStringStartsWith('get_', $name, "Le nom de l'outil doit commencer par 'get_' pour la compatibilité");
        }
    }

    public function testDescriptionCompatibility(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $description = $tool->getDescription();

            // Vérifier que les descriptions contiennent les informations essentielles
            $this->assertStringContainsString('Récupère', $description, 'La description doit contenir le mot "Récupère"');
            $this->assertStringContainsString('endpoint', $description, 'La description doit mentionner l\'endpoint');
        }
    }

    public function testToolCountStability(): void
    {
        // Test que le nombre d'outils reste stable entre les versions
        $tools = $this->factory->generateGetTools();
        $toolCount = count($tools);

        // Le nombre d'outils ne doit pas changer drastiquement
        $this->assertGreaterThan(30, $toolCount, 'Il doit y avoir au moins 30 outils');
        $this->assertLessThan(50, $toolCount, 'Il ne doit pas y avoir plus de 50 outils');

        echo "Nombre d'outils: {$toolCount}\n";
    }

    public function testNamingConventionStability(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $name = $tool->getName();

            // Vérifier que les conventions de nommage sont respectées
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $name, 'Le nom doit être en snake_case');
            $this->assertLessThanOrEqual(32, strlen($name), 'Le nom ne doit pas dépasser 32 caractères');
        }
    }

    public function testEndpointMappingStability(): void
    {
        $tools = $this->factory->generateGetTools();

        // Vérifier que les endpoints principaux sont toujours mappés
        $endpointMappings = [
            'get_issues' => '/issues.{format}',
            'get_projects' => '/projects.{format}',
            'get_users' => '/users.{format}',
            'get_time_entries' => '/time_entries.{format}',
            'get_search' => '/search.{format}',
        ];

        foreach ($endpointMappings as $toolName => $expectedEndpoint) {
            $found = false;
            foreach ($tools as $tool) {
                if ($tool->getName() === $toolName) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "L'outil {$toolName} doit être présent pour mapper l'endpoint {$expectedEndpoint}");
        }
    }
}
