<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicToolFactory;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PerformanceTest extends TestCase
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

    public function testToolGenerationPerformance(): void
    {
        $startTime = microtime(true);

        $tools = $this->factory->generateGetTools();

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // La génération ne doit pas prendre plus de 5 secondes
        $this->assertLessThan(5.0, $executionTime, 'La génération des outils doit être rapide');

        echo 'Temps de génération: '.round($executionTime * 1000, 2)."ms\n";
    }

    public function testMemoryUsage(): void
    {
        $initialMemory = memory_get_usage();

        $tools = $this->factory->generateGetTools();

        $finalMemory = memory_get_usage();
        $memoryUsed = $finalMemory - $initialMemory;

        // L'utilisation mémoire ne doit pas dépasser 100MB
        $this->assertLessThan(100 * 1024 * 1024, $memoryUsed, 'L\'utilisation mémoire doit être raisonnable');

        echo 'Mémoire utilisée: '.round($memoryUsed / 1024 / 1024, 2)."MB\n";
    }

    public function testToolNameLengthLimits(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $name = $tool->getName();

            // Vérifier que les noms ne dépassent pas 32 caractères
            $this->assertLessThanOrEqual(32, strlen($name), "Le nom '{$name}' ne doit pas dépasser 32 caractères");

            // Vérifier que les noms ne sont pas trop courts
            $this->assertGreaterThan(3, strlen($name), "Le nom '{$name}' doit avoir au moins 3 caractères");
        }
    }

    public function testDescriptionLengthLimits(): void
    {
        $tools = $this->factory->generateGetTools();

        foreach ($tools as $tool) {
            $description = $tool->getDescription();

            // Vérifier que les descriptions ne sont pas vides
            $this->assertNotEmpty($description, 'La description ne doit pas être vide');

            // Vérifier que les descriptions ne sont pas trop longues
            $this->assertLessThan(500, strlen($description), 'La description ne doit pas être trop longue');
        }
    }

    public function testConcurrentToolGeneration(): void
    {
        $results = [];

        // Simuler plusieurs générations simultanées
        for ($i = 0; $i < 5; ++$i) {
            $startTime = microtime(true);
            $tools = $this->factory->generateGetTools();
            $endTime = microtime(true);

            $results[] = [
                'count' => count($tools),
                'time' => $endTime - $startTime,
            ];
        }

        // Vérifier que toutes les générations donnent le même nombre d'outils
        $firstCount = $results[0]['count'];
        foreach ($results as $result) {
            $this->assertEquals($firstCount, $result['count'], 'Le nombre d\'outils doit être cohérent');
        }

        echo 'Générations concurrentes testées: '.count($results)."\n";
    }

    public function testToolSchemaConsistency(): void
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

                // Vérifier que les propriétés sont cohérentes
                foreach (get_object_vars($schema['properties']) as $propertyName => $property) {
                    $this->assertIsString($propertyName);
                    $this->assertNotEmpty($propertyName);
                }
            }
        }
    }

    public function testToolUniquenessUnderStress(): void
    {
        // Test avec plusieurs générations pour s'assurer que les noms restent uniques
        $allNames = [];

        for ($i = 0; $i < 3; ++$i) {
            $tools = $this->factory->generateGetTools();
            $names = array_map(fn ($tool) => $tool->getName(), $tools);

            foreach ($names as $name) {
                $allNames[] = $name;
            }
        }

        $uniqueNames = array_unique($allNames);
        // Chaque génération doit donner le même nombre d'outils uniques
        $expectedUniqueCount = count($allNames) / 3;
        $this->assertEquals($expectedUniqueCount, count($uniqueNames), 'Tous les noms doivent rester uniques même sous stress');
    }
}
