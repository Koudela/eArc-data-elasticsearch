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

use eArc\Data\Exceptions\DataException;
use eArc\Data\Manager\Interfaces\Events\OnFindInterface;
use eArc\Data\Manager\Interfaces\Events\OnPersistInterface;
use eArc\Data\Manager\Interfaces\Events\OnRemoveInterface;
use Elastica\Client;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Search;
use Exception;

class ElasticsearchDataBridge implements OnPersistInterface, OnRemoveInterface, OnFindInterface
{
    protected Client $elasticaClient;
    protected Index $index;
    protected DocumentFactory $documentFactory;
    protected array $whitelist;
    protected array $blacklist;

    public function __construct()
    {
        $this->elasticaClient = new Client(di_param(
            ParameterInterface::ELASTICA_CLIENT_CONFIG,
            [
                'host' => 'localhost',
                'port' => 9200,
            ]
        ));

        $this->index = $this->elasticaClient->getIndex(di_param(ParameterInterface::INDEX_NAME, 'earc-data'));

        try {
            $this->index->create();
        } catch (Exception) {
        }

        $this->whitelist = di_param(ParameterInterface::WHITELIST, []);
        $this->blacklist = di_param(ParameterInterface::BLACKLIST, []);

        $this->documentFactory = di_get(DocumentFactory::class);
    }

    public function onPersist(array $entities): void
    {
        foreach ($entities as $entity) {
            if (!$this->isResponsible($entity::class)) {
                continue;
            }

            if (!$entity->getPrimaryKey()) {
                throw new DataException(sprintf(
                    '{f2c0e228-24c4-4bf1-80df-f2b364d61404} Primary key on entity of class %s must not be empty.',
                    $entity::class
                ));
            }

            try {
                $this->index->getDocument($entity::class.'::'.$entity->getPrimaryKey());
                $this->index->deleteById($entity::class.'::'.$entity->getPrimaryKey());
            } catch (NotFoundException $exception) {
                unset($exception);
            }
            $this->index->addDocument($this->documentFactory->build($entity));
        }
    }

    public function onRemove(string $fQCN, array $primaryKeys): void
    {
        if ($this->isResponsible($fQCN)) {
            foreach ($primaryKeys as $primaryKey) {
                $this->index->deleteById($fQCN.'::'.$primaryKey);
            }
        }
    }

    public function onFind(string $fQCN, array $keyValuePairs): array|null
    {
        if (!$this->isResponsible($fQCN)) {
            return null;
        }

        $query = new Query\BoolQuery();

        foreach ($keyValuePairs as $key => $value) {
            if ($key === 'primaryKey') {
                if (is_array($value)) {
                    $query->addMust(new Query\Terms('_id', $this->primaryKeysToIds($fQCN, $value)));
                } else {
                    $query->addMust(new Term(['_id' => $fQCN.'::'.$value]));
                }
            } else if (is_array($value)) {
                $query->addMust(new Query\Terms($key, $value));
            } else {
                $query->addMust(new Term([$key => $value]));
            }
        }

        $results = (new Search($this->elasticaClient))
            ->addIndex($this->index)
            ->search($query)
            ->getResults();

        $primaryKeys = [];

        foreach ($results as $result) {
            $pk = $result->getHit()['_source']['~primaryKey'];
            $primaryKeys[$pk] = $pk;
        }

        return $primaryKeys;
    }

    /**
     * @param string[]|null $entityClassNames
     */
    public function reBuildIndex(array|null $entityClassNames): void
    {
        $whitelist = $this->whitelist;
        $entityClassNames ?? di_param(ParameterInterface::WHITELIST, []);

        // do not use elasticsearch for `data_find()`
        di_set_param(ParameterInterface::WHITELIST, []);
        $this->whitelist = [];

        $this->index->delete();

        foreach ($entityClassNames as $fQCN) {
            $entities = data_find_entities($fQCN, []);

            foreach ($entities as $entity) {
                $this->index->addDocument($this->documentFactory->build($entity));
            }
        }

        // reset elasticsearch setting
        $this->whitelist = $whitelist;
        di_set_param(ParameterInterface::WHITELIST, $whitelist);
    }

    protected function primaryKeysToIds(string $fQCN, array $primaryKeys): array
    {
        return array_map(function($pk) use ($fQCN) { return $fQCN.'::'.$pk; }, $primaryKeys);
    }

    protected function isResponsible(string $fQCN): bool
    {
        if (empty($this->whitelist)) {
            return !array_key_exists($fQCN, $this->blacklist);
        }

        return array_key_exists($fQCN, $this->whitelist);
    }
}
