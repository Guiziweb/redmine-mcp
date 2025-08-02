<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicToolFactory;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AllToolsTest extends TestCase
{
    private DynamicToolFactory $factory;
    private array $availableEndpoints;

    protected function setUp(): void
    {
        /** @var RedmineService $redmineService */
        $redmineService = $this->createMock(RedmineService::class);
        /** @var LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $this->factory = new DynamicToolFactory($redmineService, $logger);
        $this->availableEndpoints = $this->factory->getAvailableEndpoints();
    }

    public function testAllToolsAreGenerated(): void
    {
        $tools = $this->factory->generateGetTools();

        // Vérifier qu'il y a des outils générés
        $this->assertGreaterThan(0, count($tools), 'Au moins un outil doit être généré');

        echo "Nombre total d'outils générés: ".count($tools)."\n";

        // Lister tous les outils
        foreach ($tools as $tool) {
            echo '- '.$tool->getName().' ('.$tool->getDescription().")\n";
        }

        // Vérifier que les outils principaux sont présents
        $toolNames = array_map(fn ($tool) => $tool->getName(), $tools);

        // Debug: afficher les noms d'outils
        echo "Noms d'outils: ".implode(', ', $toolNames)."\n";
        echo 'get_issues présent: '.(in_array('get_issues', $toolNames) ? 'OUI' : 'NON')."\n";

        // Les noms ont changé à cause de la correction des doublons
        $this->assertContains('get_issues', $toolNames, 'L\'outil get_issues doit être présent');
        $this->assertContains('get_projects', $toolNames, 'L\'outil get_projects doit être présent');
        $this->assertContains('get_users', $toolNames, 'L\'outil get_users doit être présent');
    }

    public function testEndpointsAreAvailable(): void
    {
        // Vérifier que les endpoints principaux sont disponibles
        $this->assertArrayHasKey('/issues.{format}', $this->availableEndpoints);
        $this->assertArrayHasKey('/projects.{format}', $this->availableEndpoints);
        $this->assertArrayHasKey('/users.{format}', $this->availableEndpoints);

        echo "Endpoints disponibles:\n";
        foreach ($this->availableEndpoints as $endpoint => $methods) {
            echo "- {$endpoint}: ".implode(', ', $methods)."\n";
        }
    }

    public function testToolSchemasAreValid(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $schema = $tool->getInputSchema();

            // Vérifier la structure du schéma
            $this->assertIsArray($schema);
            $this->assertArrayHasKey('type', $schema);
            $this->assertEquals('object', $schema['type']);

            if (isset($schema['properties'])) {
                $this->assertInstanceOf(\stdClass::class, $schema['properties']);
            }

            // Vérifier que le nom et la description sont valides
            $this->assertNotEmpty($tool->getName());
            $this->assertNotEmpty($tool->getDescription());
            $this->assertLessThanOrEqual(32, strlen($tool->getName()), 'Le nom de l\'outil ne doit pas dépasser 32 caractères');
        }
    }

    public function testSpecificToolsHaveRequiredParameters(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $name = $tool->getName();
            $schema = $tool->getInputSchema();

            // Test spécifique pour l'outil issues
            if ('get_issues' === $name) {
                $properties = $schema['properties'];
                $this->assertTrue(property_exists($properties, 'offset'), 'L\'outil issues doit avoir le paramètre offset');
                $this->assertTrue(property_exists($properties, 'limit'), 'L\'outil issues doit avoir le paramètre limit');
            }

            // Test spécifique pour l'outil projects
            if ('get_projects' === $name) {
                $properties = $schema['properties'];
                $this->assertTrue(property_exists($properties, 'offset'), 'L\'outil projects doit avoir le paramètre offset');
                $this->assertTrue(property_exists($properties, 'limit'), 'L\'outil projects doit avoir le paramètre limit');
            }
        }
    }

    public function testToolNamesAreUnique(): void
    {
        $tools = $this->factory->generateGetTools();
        $names = array_map(fn ($tool) => $tool->getName(), $tools);

        // Debug: identifier les doublons
        $nameCounts = array_count_values($names);
        $duplicates = array_filter($nameCounts, fn ($count) => $count > 1);

        if (!empty($duplicates)) {
            echo "Doublons trouvés:\n";
            foreach ($duplicates as $name => $count) {
                echo "- {$name}: {$count} fois\n";
            }
        }

        $uniqueNames = array_unique($names);
        $this->assertEquals(count($names), count($uniqueNames), 'Tous les noms d\'outils doivent être uniques');

        // Vérifier qu'il n'y a pas de noms vides ou invalides
        foreach ($names as $name) {
            $this->assertNotEmpty($name, 'Le nom de l\'outil ne doit pas être vide');
            $this->assertMatchesRegularExpression('/^[a-z_]+$/', $name, 'Le nom de l\'outil doit être en snake_case');
        }
    }

    public function testToolDescriptionsAreInformative(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $description = $tool->getDescription();

            $this->assertNotEmpty($description, 'La description ne doit pas être vide');
            $this->assertGreaterThan(10, strlen($description), 'La description doit être informative');
            $this->assertStringContainsString('Récupère', $description, 'La description doit expliquer l\'action');
        }
    }
}
