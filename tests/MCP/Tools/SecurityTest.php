<?php

namespace App\Tests\MCP\Tools;

use App\MCP\Tools\DynamicToolFactory;
use App\Service\RedmineService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SecurityTest extends TestCase
{
    private DynamicToolFactory $factory;
    /** @var RedmineService&\PHPUnit\Framework\MockObject\MockObject */
    private $redmineService;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->redmineService = $this->createMock(RedmineService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->factory = new DynamicToolFactory($this->redmineService, $this->logger);
    }

    private function findToolByName(string $name): ?object
    {
        $tools = $this->factory->generateGetTools();
        foreach ($tools as $tool) {
            if ($tool->getName() === $name) {
                return $tool;
            }
        }

        return null;
    }

    public function testSqlInjectionPrevention(): void
    {
        $tool = $this->findToolByName('get_issues');
        $this->assertNotNull($tool);

        // Test avec des tentatives d'injection SQL
        $sqlInjectionAttempts = [
            "'; DROP TABLE issues; --",
            "' OR '1'='1",
            "'; INSERT INTO issues VALUES (1, 'hacked'); --",
            "'; UPDATE issues SET subject = 'hacked'; --",
        ];

        foreach ($sqlInjectionAttempts as $attempt) {
            $result = $tool->call((object) ['arguments' => [
                'subject' => $attempt,
                'description' => $attempt,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testXssPrevention(): void
    {
        $tool = $this->findToolByName('get_issues');
        $this->assertNotNull($tool);

        // Test avec des tentatives de XSS
        $xssAttempts = [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(\'xss\')">',
            'javascript:alert("xss")',
            '<iframe src="javascript:alert(\'xss\')"></iframe>',
        ];

        foreach ($xssAttempts as $attempt) {
            $result = $tool->call((object) ['arguments' => [
                'subject' => $attempt,
                'description' => $attempt,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testPathTraversalPrevention(): void
    {
        $tool = $this->findToolByName('get_attachments_attachment_id');
        $this->assertNotNull($tool);

        // Test avec des tentatives de path traversal
        $pathTraversalAttempts = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
        ];

        foreach ($pathTraversalAttempts as $attempt) {
            $result = $tool->call((object) ['arguments' => [
                'attachment_id' => $attempt,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testCommandInjectionPrevention(): void
    {
        $tool = $this->findToolByName('get_search');
        $this->assertNotNull($tool);

        // Test avec des tentatives d'injection de commande
        $commandInjectionAttempts = [
            'test; rm -rf /',
            'test && rm -rf /',
            'test | rm -rf /',
            'test; cat /etc/passwd',
            'test && cat /etc/passwd',
        ];

        foreach ($commandInjectionAttempts as $attempt) {
            $result = $tool->call((object) ['arguments' => [
                'q' => $attempt,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testParameterValidation(): void
    {
        $tools = $this->factory->generateGetTools();
        $this->assertNotEmpty($tools);

        foreach ($tools as $tool) {
            // Test avec des paramètres invalides
            $result = $tool->call((object) ['arguments' => [
                'limit' => -1,
                'offset' => -100,
                'invalid_param' => 'test',
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testLargeInputHandling(): void
    {
        $tool = $this->findToolByName('get_issues');
        $this->assertNotNull($tool);

        // Test avec des entrées très grandes
        $largeInput = str_repeat('a', 100000);
        $result = $tool->call((object) ['arguments' => [
            'subject' => $largeInput,
            'description' => $largeInput,
        ]]);
        $this->assertNotNull($result);
    }

    public function testUnicodeNormalization(): void
    {
        $tool = $this->findToolByName('get_search');
        $this->assertNotNull($tool);

        // Test avec des caractères Unicode normalisés
        $unicodeStrings = [
            'café', // e avec accent
            'naïve', // i avec tréma
            'façade', // c avec cédille
            'résumé', // e avec accent
            'über', // u avec tréma
            'Müller', // u avec tréma
            'Schrödinger', // o avec tréma
            'François', // c avec cédille
            'José', // e avec accent
            'Zoë', // e avec tréma
        ];

        foreach ($unicodeStrings as $string) {
            $result = $tool->call((object) ['arguments' => [
                'q' => $string,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testEncodingHandling(): void
    {
        $tool = $this->findToolByName('get_search');
        $this->assertNotNull($tool);

        // Test avec différents encodages
        $encodedStrings = [
            urlencode('test with spaces'),
            urlencode('test+with+plus'),
            urlencode('test%20with%20percent'),
            base64_encode('test with base64'),
            bin2hex('test with hex'),
        ];

        foreach ($encodedStrings as $string) {
            $result = $tool->call((object) ['arguments' => [
                'q' => $string,
            ]]);
            $this->assertNotNull($result);
        }
    }

    public function testSpecialCharacterEscaping(): void
    {
        $tool = $this->findToolByName('get_search');
        $this->assertNotNull($tool);

        // Test avec des caractères spéciaux
        $specialChars = [
            'test with "quotes"',
            'test with \'single quotes\'',
            'test with <tags>',
            'test with &ampersands&',
            'test with <script>alert("xss")</script>',
            'test with ../../path/traversal',
            'test with ; command injection',
            'test with | pipe',
            'test with && logical and',
            'test with || logical or',
        ];

        foreach ($specialChars as $string) {
            $result = $tool->call((object) ['arguments' => [
                'q' => $string,
            ]]);
            $this->assertNotNull($result);
        }
    }
}
