<?php

namespace App\Tests\Integration;

use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;

class PaginationIntegrationTest extends TestCase
{
    private RedmineService $redmineService;

    protected function setUp(): void
    {
        // Ce test nécessite une configuration Redmine valide
        // Il sera marqué comme skipped si les variables d'environnement ne sont pas configurées
        $redmineUrl = $_ENV['REDMINE_URL'] ?? null;
        $apiKey = $_ENV['REDMINE_API_KEY'] ?? null;

        if (!$redmineUrl || !$apiKey) {
            $this->markTestSkipped('Variables d\'environnement REDMINE_URL et REDMINE_API_KEY requises');
        }

        /** @var \Psr\Log\LoggerInterface $logger */
        $logger = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->redmineService = new RedmineService($redmineUrl, $apiKey, $logger);
    }

    public function testPaginationWithOffsetAndLimit(): void
    {
        // Test avec offset=25 et limit=10
        $params = [
            'offset' => 25,
            'limit' => 10,
            'sort' => 'id:desc',
        ];

        $result = $this->redmineService->getIssues($params);

        // Vérifications de base
        $this->assertIsArray($result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertArrayHasKey('total_count', $result);
        $this->assertArrayHasKey('offset', $result);
        $this->assertArrayHasKey('limit', $result);

        // Vérifications de pagination
        $this->assertEquals(25, $result['offset'], 'L\'offset retourné doit être 25');
        $this->assertEquals(10, $result['limit'], 'La limite retournée doit être 10');
        $this->assertCount(10, $result['issues'], 'Le nombre d\'issues retournées doit être 10');

        // Vérifier que les IDs sont bien dans l'ordre décroissant et différents de la première page
        if (!empty($result['issues'])) {
            $firstId = $result['issues'][0]['id'];
            $lastId = end($result['issues'])['id'];

            $this->assertLessThan($firstId, $lastId, 'Les IDs doivent être dans l\'ordre décroissant');

            // Vérifier que ces IDs sont différents de ceux de la première page
            $firstPageParams = ['offset' => 0, 'limit' => 10, 'sort' => 'id:desc'];
            $firstPage = $this->redmineService->getIssues($firstPageParams);
            $firstPageIds = array_column($firstPage['issues'], 'id');

            $this->assertNotContains($firstId, $firstPageIds, 'L\'ID de la page 2 ne doit pas être dans la page 1');
        }
    }

    public function testPaginationWithDifferentLimits(): void
    {
        // Test avec différentes limites
        $testCases = [
            ['offset' => 0, 'limit' => 5],
            ['offset' => 5, 'limit' => 5],
            ['offset' => 10, 'limit' => 5],
        ];

        $previousIds = [];

        foreach ($testCases as $case) {
            $result = $this->redmineService->getIssues($case);

            $this->assertEquals($case['offset'], $result['offset']);
            $this->assertEquals($case['limit'], $result['limit']);
            $this->assertCount($case['limit'], $result['issues']);

            // Vérifier qu'il n'y a pas de chevauchement avec les pages précédentes
            $currentIds = array_column($result['issues'], 'id');
            foreach ($previousIds as $prevId) {
                $this->assertNotContains($prevId, $currentIds, 'Aucun ID ne doit apparaître dans plusieurs pages');
            }

            $previousIds = array_merge($previousIds, $currentIds);
        }
    }

    public function testPaginationEdgeCases(): void
    {
        // Test avec offset=0 (première page)
        $result = $this->redmineService->getIssues(['offset' => 0, 'limit' => 10]);
        $this->assertEquals(0, $result['offset']);
        $this->assertGreaterThan(0, count($result['issues']));

        // Test avec un offset très grand (devrait retourner une page vide)
        $result = $this->redmineService->getIssues(['offset' => 10000, 'limit' => 10]);
        $this->assertEquals(10000, $result['offset']);
        $this->assertCount(0, $result['issues']);
    }

    public function testSortingWithPagination(): void
    {
        // Test que le tri fonctionne avec la pagination
        $result = $this->redmineService->getIssues([
            'offset' => 25,
            'limit' => 5,
            'sort' => 'id:desc',
        ]);

        if (count($result['issues']) >= 2) {
            $firstId = $result['issues'][0]['id'];
            $secondId = $result['issues'][1]['id'];
            $this->assertGreaterThan($secondId, $firstId, 'Les IDs doivent être triés en ordre décroissant');
        }
    }
}
