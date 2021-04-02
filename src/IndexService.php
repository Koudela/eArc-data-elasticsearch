<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data-elasticsearch
 * @link https://github.com/Koudela/eArc-data-elasticsearch/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\DataElasticsearch;

use eArc\Data\Entity\Interfaces\EntityInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class IndexService
{
    protected Client $client;
    protected DocumentFactory $documentFactory;

    protected string $indexPrefix;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->setHosts(di_param(
                ParameterInterface::CLIENT_HOSTS,
                ['http://localhost:9200']
            ))
            ->build();

        $this->documentFactory = di_get(DocumentFactory::class);

        $this->indexPrefix = di_param(ParameterInterface::INDEX_PREFIX, 'earc-data');
    }

    public function addEntity(EntityInterface $entity): void
    {
        $this->client->index([
            'index' => $this->getIndexName($entity::class),
            'id' => $entity->getPrimaryKey(),
            'body' => $this->documentFactory->build($entity),
        ]);

    }

    public function deleteEntity(string $fQCN, string $primaryKey)
    {
        $this->client->delete(['index' => $this->getIndexName($fQCN), 'id' => $primaryKey]);
    }

    public function search(string $fQCN, array $body): array
    {
        return $this->client->search([
            "index" => $this->getIndexName($fQCN),
            "body" => $body,
        ]);
    }

    public function deleteIndex(string $fQCN): void
    {
        $this->client->indices()->delete(['index' => $this->getIndexName($fQCN)]);
    }

    public function initIndex(string $fQCN): void
    {
        if (!$this->client->indices()->exists(['index' => $this->getIndexName($fQCN)])) {
            $this->client->indices()->create([
                'index' => $this->getIndexName($fQCN),
                'body' => [
                    'mappings' => $this->getMappings(),
                ],
            ]);
        }

    }

    /**
     * @param string[] $entityClassNames
     */
    public function rebuildIndex(array $entityClassNames): void
    {
        foreach ($entityClassNames as $fQCN) {
            $this->deleteIndex($fQCN);
            $this->initIndex($fQCN);

            $entities = data_find_entities($fQCN, []);

            foreach ($entities as $entity) {
                $this->addEntity($entity);
            }
        }
    }

    protected function getIndexName(string $fQCN): string
    {
        return $this->indexPrefix.'-'.strtolower(str_replace('\\', '-', $fQCN));
    }

    protected function getMappings(): array
    {
        return [
            "dynamic_templates"=> [
                [
                    "items_as_nested" => [
                        "path_match" => "*._items",
                        "mapping" => [
                            "type" => "nested",
                        ],
                    ]
                ],
                [
                    "strings_as_keywords" => [
                        "match_mapping_type" => "string",
                        "mapping" => [
                            "type" => "keyword",
                            "fields" => [
                                "text" => [
                                    "type" => "text",
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'properties' => [
                '_.timestamp' => [
                    'type' => 'date',
                ],
            ]
        ];
    }
}
