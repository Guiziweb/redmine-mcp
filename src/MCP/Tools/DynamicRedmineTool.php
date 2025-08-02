<?php

namespace App\MCP\Tools;

use App\Service\RedmineService;
use Psr\Log\LoggerInterface;
use Symfony\AI\McpSdk\Capability\Tool\MetadataInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolAnnotationsInterface;
use Symfony\AI\McpSdk\Capability\Tool\ToolCallResult;
use Symfony\AI\McpSdk\Capability\Tool\ToolExecutorInterface;
use Symfony\Component\Yaml\Yaml;

abstract class DynamicRedmineTool implements MetadataInterface, ToolExecutorInterface
{
    protected RedmineService $redmineService;
    protected array $openApiSpec;
    protected LoggerInterface $logger;
    protected string $endpoint;
    protected string $method;

    public function __construct(RedmineService $redmineService, LoggerInterface $logger, string $endpoint, string $method = 'get')
    {
        $this->redmineService = $redmineService;
        $this->logger = $logger;
        $this->endpoint = $endpoint;
        $this->method = $method;

        $yamlPath = __DIR__.'/../../../redmine_openapi.yml';
        $this->openApiSpec = Yaml::parseFile($yamlPath);
    }

    public function call($input): ToolCallResult
    {
        $params = [];

        // Récupération dynamique des paramètres depuis le schéma OpenAPI
        $parameters = $this->openApiSpec['paths'][$this->endpoint][$this->method]['parameters'] ?? [];

        $this->logger->error('=== DEBUG MCP TOOL ===');
        $this->logger->error("Endpoint: {$this->endpoint}");
        $this->logger->error("Méthode: {$this->method}");
        $this->logger->error('Arguments reçus: '.json_encode($input->arguments));
        $this->logger->error('Paramètres OpenAPI trouvés: '.count($parameters));

        foreach ($parameters as $param) {
            // Résoudre les références $ref
            if (isset($param['$ref'])) {
                $refPath = $param['$ref'];
                $this->logger->error("Résolution de la référence: {$refPath}");

                // Extraire le chemin de la référence (ex: #/components/parameters/offset)
                if (0 === strpos($refPath, '#/components/parameters/')) {
                    $paramName = str_replace('#/components/parameters/', '', $refPath);
                    $param = $this->openApiSpec['components']['parameters'][$paramName] ?? null;
                    $this->logger->error('Paramètre résolu: '.($param ? $param['name'] : 'N/A'));
                }
            }

            if (!$param || ($param['in'] ?? '') !== 'query') {
                continue;
            }

            $name = $param['name'];
            $this->logger->error("Traitement du paramètre: {$name}");

            // Traitement dynamique de tous les paramètres
            if (isset($input->arguments[$name])) {
                $value = $input->arguments[$name];
                $this->logger->error('  - Valeur reçue: '.json_encode($value));
                $this->logger->error('  - Type: '.gettype($value));

                // Conversion spéciale pour les paramètres de type array : tableau vers chaîne avec virgules
                if (is_array($value)) {
                    $params[$name] = implode(',', $value);
                    $this->logger->error('  - Conversion tableau vers chaîne: '.$params[$name]);
                } else {
                    $params[$name] = $value;
                    $this->logger->error('  - Paramètre ajouté: '.json_encode($params[$name]));
                }
            } else {
                $this->logger->error('  - Paramètre non trouvé dans les arguments');
            }
        }

        $this->logger->error('Paramètres finaux envoyés à Redmine: '.json_encode($params));

        try {
            // Appel Redmine avec tous les paramètres dynamiques
            $result = $this->redmineService->callApi($this->endpoint, $this->method, $params);

            $this->logger->error("Résultat obtenu pour {$this->endpoint}");
            $this->logger->error('Total count: '.($result['total_count'] ?? 'N/A'));
            $this->logger->error('Offset retourné: '.($result['offset'] ?? 'N/A'));
            $this->logger->error('Issues reçues: '.count($result['issues'] ?? []));
            $this->logger->error('=== FIN DEBUG ===');

            return new ToolCallResult(
                result: json_encode($result),
                type: 'text',
                mimeType: 'application/json',
                isError: false,
            );
        } catch (\Exception $e) {
            $this->logger->error("Erreur lors de l'appel à l'API Redmine: ".$e->getMessage());

            // Retourner un résultat vide mais valide
            $emptyResult = [
                'data' => [],
                'total_count' => 0,
                'offset' => 0,
                'limit' => 25,
            ];

            return new ToolCallResult(
                result: json_encode($emptyResult),
                type: 'text',
                mimeType: 'application/json',
                isError: false,
            );
        }
    }

    /**
     * @return array{type: 'object', properties: \stdClass, required?: array<int, string>}
     *
     * @phpstan-ignore-next-line
     */
    public function getInputSchema(): array
    {
        $parameters = $this->openApiSpec['paths'][$this->endpoint][$this->method]['parameters'] ?? [];

        $schema = [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];

        foreach ($parameters as $param) {
            // Résoudre les références $ref
            if (isset($param['$ref'])) {
                $refPath = $param['$ref'];

                // Extraire le chemin de la référence (ex: #/components/parameters/offset)
                if (0 === strpos($refPath, '#/components/parameters/')) {
                    $paramName = str_replace('#/components/parameters/', '', $refPath);
                    $param = $this->openApiSpec['components']['parameters'][$paramName] ?? null;
                }
            }

            if (!$param || ($param['in'] ?? '') !== 'query') {
                continue;
            }

            $name = $param['name'];

            if (str_contains($name, '.') || strlen($name) > 50) {
                continue;
            }

            $paramSchema = $param['schema'] ?? [];

            // Supprimer les clés non JSON Schema (ex: explode)
            unset($paramSchema['explode']);
            unset($paramSchema['example']);

            // Ajouter la description dans le schéma
            if (!empty($param['description'])) {
                $paramSchema['description'] = $param['description'];
            }

            $schema['properties']->{$name} = $paramSchema;

            // Si param obligatoire (rare ici, vérifier sinon ignorer)
            if (!empty($param['required'])) {
                $schema['required'][] = $name;
            }
        }

        // Si pas de propriétés requises, supprimer la clé required pour éviter soucis
        if (empty($schema['required'])) {
            unset($schema['required']);
        }

        return $schema;
    }

    public function getOutputSchema(): ?array
    {
        // Essayer de trouver le schéma de réponse dans le spec OpenAPI
        $responseSchema = $this->openApiSpec['paths'][$this->endpoint][$this->method]['responses']['200']['content']['application/json']['schema'] ?? null;

        if ($responseSchema) {
            return $responseSchema;
        }

        // Fallback: chercher dans les composants
        $schemas = $this->openApiSpec['components']['schemas'] ?? [];

        // Essayer de deviner le nom du schéma basé sur l'endpoint
        $endpointName = str_replace(['/', '.', '{', '}'], ['', '', '', ''], $this->endpoint);
        $possibleSchemaNames = [
            ucfirst($endpointName).'List',
            $endpointName.'List',
            ucfirst($endpointName),
            $endpointName,
        ];

        foreach ($possibleSchemaNames as $schemaName) {
            if (isset($schemas[$schemaName])) {
                return $schemas[$schemaName];
            }
        }

        return null;
    }

    public function getAnnotations(): ?ToolAnnotationsInterface
    {
        return null;
    }

    // Méthodes abstraites que chaque tool doit implémenter
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function getTitle(): ?string;

    // Méthode pour récupérer l'endpoint
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}
