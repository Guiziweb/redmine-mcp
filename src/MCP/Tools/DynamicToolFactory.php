<?php

namespace App\MCP\Tools;

use App\Service\RedmineService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class DynamicToolFactory
{
    private RedmineService $redmineService;
    private LoggerInterface $logger;
    private array $openApiSpec;

    public function __construct(RedmineService $redmineService, LoggerInterface $logger)
    {
        $this->redmineService = $redmineService;
        $this->logger = $logger;

        $yamlPath = __DIR__.'/../../../redmine_openapi.yml';
        $this->openApiSpec = Yaml::parseFile($yamlPath);
    }

    public function generateGetTools(): array
    {
        $tools = [];
        $usedNames = [];

        foreach ($this->openApiSpec['paths'] as $path => $methods) {
            if (!isset($methods['get'])) {
                continue;
            }

            // Créer un tool pour chaque endpoint GET
            $tool = new class($this->redmineService, $this->logger, $path, 'get') extends DynamicRedmineTool {
                public function getName(): string
                {
                    // Convertir l'endpoint en nom de tool suivant les conventions MCP
                    $name = $this->endpoint;

                    // Nettoyer l'endpoint
                    $name = str_replace(['/', '.', '{', '}', 'format'], ['_', '_', '', '', ''], $name);
                    $name = trim($name, '_');

                    // Construire le nom final en snake_case
                    $finalName = 'get_'.$name;

                    // Limiter à 32 caractères comme recommandé
                    if (strlen($finalName) > 32) {
                        $finalName = substr($finalName, 0, 32);
                    }

                    return $finalName;
                }

                public function getDescription(): string
                {
                    $summary = $this->openApiSpec['paths'][$this->endpoint][$this->method]['summary'] ?? '';
                    $description = $this->openApiSpec['paths'][$this->endpoint][$this->method]['description'] ?? '';

                    $desc = "Récupère des données depuis l'endpoint {$this->endpoint}";
                    if ($summary) {
                        $desc .= " - {$summary}";
                    }
                    if ($description) {
                        $desc .= " - {$description}";
                    }

                    return $desc;
                }

                public function getTitle(): ?string
                {
                    $endpointName = str_replace(['/', '.', '{', '}', 'format'], [' ', ' ', '', '', ''], $this->endpoint);
                    $endpointName = trim($endpointName);
                    $endpointName = ucwords($endpointName);

                    return "Redmine {$endpointName} Fetcher";
                }
            };

            // Vérifier s'il y a un doublon et ajouter un suffixe si nécessaire
            $originalName = $tool->getName();
            $finalName = $originalName;
            $counter = 1;

            while (in_array($finalName, $usedNames)) {
                $suffix = '_'.str_repeat('a', $counter);
                $finalName = substr($originalName, 0, 32 - strlen($suffix)).$suffix;
                ++$counter;
            }

            $usedNames[] = $finalName;

            // Créer un nouveau tool avec le nom corrigé
            $finalTool = new class($this->redmineService, $this->logger, $path, 'get', $finalName) extends DynamicRedmineTool {
                private string $customName;

                public function __construct($redmineService, $logger, $endpoint, $method, $customName)
                {
                    parent::__construct($redmineService, $logger, $endpoint, $method);
                    $this->customName = $customName;
                }

                public function getName(): string
                {
                    return $this->customName;
                }

                public function getDescription(): string
                {
                    $summary = $this->openApiSpec['paths'][$this->endpoint][$this->method]['summary'] ?? '';
                    $description = $this->openApiSpec['paths'][$this->endpoint][$this->method]['description'] ?? '';

                    $desc = "Récupère des données depuis l'endpoint {$this->endpoint}";
                    if ($summary) {
                        $desc .= " - {$summary}";
                    }
                    if ($description) {
                        $desc .= " - {$description}";
                    }

                    return $desc;
                }

                public function getTitle(): ?string
                {
                    $endpointName = str_replace(['/', '.', '{', '}', 'format'], [' ', ' ', '', '', ''], $this->endpoint);
                    $endpointName = trim($endpointName);
                    $endpointName = ucwords($endpointName);

                    return "Redmine {$endpointName} Fetcher";
                }
            };

            $tools[] = $finalTool;
        }

        return $tools;
    }

    public function generateToolsForMethod(string $method): array
    {
        $tools = [];
        $usedNames = [];

        foreach ($this->openApiSpec['paths'] as $path => $methods) {
            if (!isset($methods[$method])) {
                continue;
            }

            // Créer un tool pour chaque endpoint avec la méthode spécifiée
            $tool = new class($this->redmineService, $this->logger, $path, $method) extends DynamicRedmineTool {
                public function getName(): string
                {
                    // Convertir l'endpoint en nom de tool suivant les conventions MCP
                    $name = $this->endpoint;

                    // Nettoyer l'endpoint
                    $name = str_replace(['/', '.', '{', '}', 'format'], ['_', '_', '', '', ''], $name);
                    $name = trim($name, '_');

                    // Construire le nom final en snake_case avec la méthode
                    $finalName = $this->method.'_'.$name;

                    // Limiter à 32 caractères comme recommandé
                    if (strlen($finalName) > 32) {
                        $finalName = substr($finalName, 0, 32);
                    }

                    return $finalName;
                }

                public function getDescription(): string
                {
                    $summary = $this->openApiSpec['paths'][$this->endpoint][$this->method]['summary'] ?? '';
                    $description = $this->openApiSpec['paths'][$this->endpoint][$this->method]['description'] ?? '';

                    $desc = strtoupper($this->method)." sur l'endpoint {$this->endpoint}";
                    if ($summary) {
                        $desc .= " - {$summary}";
                    }
                    if ($description) {
                        $desc .= " - {$description}";
                    }

                    return $desc;
                }

                public function getTitle(): ?string
                {
                    $endpointName = str_replace(['/', '.', '{', '}', 'format'], [' ', ' ', '', '', ''], $this->endpoint);
                    $endpointName = trim($endpointName);
                    $endpointName = ucwords($endpointName);

                    return "Redmine {$endpointName} ".ucfirst($this->method).'er';
                }
            };

            // Vérifier s'il y a un doublon et ajouter un suffixe si nécessaire
            $originalName = $tool->getName();
            $finalName = $originalName;
            $counter = 1;

            while (in_array($finalName, $usedNames)) {
                $suffix = '_'.str_repeat('a', $counter);
                $finalName = substr($originalName, 0, 32 - strlen($suffix)).$suffix;
                ++$counter;
            }

            $usedNames[] = $finalName;

            // Créer un nouveau tool avec le nom corrigé
            $finalTool = new class($this->redmineService, $this->logger, $path, $method, $finalName) extends DynamicRedmineTool {
                private string $customName;

                public function __construct($redmineService, $logger, $endpoint, $method, $customName)
                {
                    parent::__construct($redmineService, $logger, $endpoint, $method);
                    $this->customName = $customName;
                }

                public function getName(): string
                {
                    return $this->customName;
                }

                public function getDescription(): string
                {
                    $summary = $this->openApiSpec['paths'][$this->endpoint][$this->method]['summary'] ?? '';
                    $description = $this->openApiSpec['paths'][$this->endpoint][$this->method]['description'] ?? '';

                    $desc = strtoupper($this->method)." sur l'endpoint {$this->endpoint}";
                    if ($summary) {
                        $desc .= " - {$summary}";
                    }
                    if ($description) {
                        $desc .= " - {$description}";
                    }

                    return $desc;
                }

                public function getTitle(): ?string
                {
                    $endpointName = str_replace(['/', '.', '{', '}', 'format'], [' ', ' ', '', '', ''], $this->endpoint);
                    $endpointName = trim($endpointName);
                    $endpointName = ucwords($endpointName);

                    return "Redmine {$endpointName} ".ucfirst($this->method).'er';
                }
            };

            $tools[] = $finalTool;
        }

        return $tools;
    }

    public function getAvailableEndpoints(): array
    {
        $endpoints = [];

        foreach ($this->openApiSpec['paths'] as $path => $methods) {
            $endpoints[$path] = array_keys($methods);
        }

        return $endpoints;
    }
}
