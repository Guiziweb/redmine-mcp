<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicToolFactory;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SpecificToolsTest extends TestCase
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

    public function testIssuesTool(): void
    {
        $tools = $this->factory->generateGetTools();
        $issuesTool = null;

        foreach ($tools as $tool) {
            if ('get_issues' === $tool->getName()) {
                $issuesTool = $tool;
                break;
            }
        }

        $this->assertNotNull($issuesTool, 'L\'outil get_issues doit être trouvé');

        // Vérifier le schéma
        $schema = $issuesTool->getInputSchema();
        $this->assertEquals('object', $schema['type']);

        $properties = $schema['properties'];
        $this->assertTrue(property_exists($properties, 'offset'));
        $this->assertTrue(property_exists($properties, 'limit'));
        $this->assertTrue(property_exists($properties, 'sort'));

        // Vérifier la description
        $this->assertStringContainsString('issues', $issuesTool->getDescription());
        $this->assertStringContainsString('List', $issuesTool->getDescription());
    }

    public function testProjectsTool(): void
    {
        $tools = $this->factory->generateGetTools();
        $projectsTool = null;

        foreach ($tools as $tool) {
            if ('get_projects' === $tool->getName()) {
                $projectsTool = $tool;
                break;
            }
        }

        $this->assertNotNull($projectsTool, 'L\'outil get_projects doit être trouvé');

        // Vérifier le schéma
        $schema = $projectsTool->getInputSchema();
        $this->assertEquals('object', $schema['type']);

        $properties = $schema['properties'];
        $this->assertTrue(property_exists($properties, 'offset'));
        $this->assertTrue(property_exists($properties, 'limit'));

        // Vérifier la description
        $this->assertStringContainsString('projects', $projectsTool->getDescription());
        $this->assertStringContainsString('List', $projectsTool->getDescription());
    }

    public function testUsersTool(): void
    {
        $tools = $this->factory->generateGetTools();
        $usersTool = null;

        foreach ($tools as $tool) {
            if ('get_users' === $tool->getName()) {
                $usersTool = $tool;
                break;
            }
        }

        $this->assertNotNull($usersTool, 'L\'outil get_users doit être trouvé');

        // Vérifier le schéma
        $schema = $usersTool->getInputSchema();
        $this->assertEquals('object', $schema['type']);

        $properties = $schema['properties'];
        $this->assertTrue(property_exists($properties, 'offset'));
        $this->assertTrue(property_exists($properties, 'limit'));

        // Vérifier la description
        $this->assertStringContainsString('users', $usersTool->getDescription());
        $this->assertStringContainsString('List', $usersTool->getDescription());
    }

    public function testTimeEntriesTool(): void
    {
        $tools = $this->factory->generateGetTools();
        $timeEntriesTool = null;

        foreach ($tools as $tool) {
            if ('get_time_entries' === $tool->getName()) {
                $timeEntriesTool = $tool;
                break;
            }
        }

        $this->assertNotNull($timeEntriesTool, 'L\'outil get_time_entries doit être trouvé');

        // Vérifier le schéma
        $schema = $timeEntriesTool->getInputSchema();
        $this->assertEquals('object', $schema['type']);

        $properties = $schema['properties'];
        $this->assertTrue(property_exists($properties, 'offset'));
        $this->assertTrue(property_exists($properties, 'limit'));

        // Vérifier la description
        $this->assertStringContainsString('time_entries', $timeEntriesTool->getDescription());
        $this->assertStringContainsString('List', $timeEntriesTool->getDescription());
    }

    public function testSearchTool(): void
    {
        $tools = $this->factory->generateGetTools();
        $searchTool = null;

        foreach ($tools as $tool) {
            if ('get_search' === $tool->getName()) {
                $searchTool = $tool;
                break;
            }
        }

        $this->assertNotNull($searchTool, 'L\'outil get_search doit être trouvé');

        // Vérifier le schéma
        $schema = $searchTool->getInputSchema();
        $this->assertEquals('object', $schema['type']);

        $properties = $schema['properties'];
        $this->assertTrue(property_exists($properties, 'q'), 'L\'outil search doit avoir le paramètre q');

        // Vérifier la description
        $this->assertStringContainsString('search', $searchTool->getDescription());
    }

    public function testToolNamesAreConsistent(): void
    {
        $tools = $this->factory->generateGetTools();

        // Vérifier que les noms suivent un pattern cohérent
        foreach ($tools as $tool) {
            $name = $tool->getName();

            // Les noms doivent commencer par 'get_' ou 'list_'
            $this->assertTrue(
                str_starts_with($name, 'get_') || str_starts_with($name, 'list_'),
                "Le nom de l'outil '{$name}' doit commencer par 'get_' ou 'list_'"
            );

            // Les noms ne doivent contenir que des lettres minuscules et des underscores
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+$/',
                $name,
                "Le nom de l'outil '{$name}' doit être en snake_case"
            );
        }
    }
}
