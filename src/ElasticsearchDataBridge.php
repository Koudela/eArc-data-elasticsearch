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

use DateTime;
use eArc\Data\Entity\Interfaces\EntityInterface;
use eArc\Data\Exceptions\DataException;
use eArc\Data\Manager\Interfaces\Events\OnFindInterface;
use eArc\Data\Manager\Interfaces\Events\OnPersistInterface;
use eArc\Data\Manager\Interfaces\Events\OnRemoveInterface;
use Exception;

class ElasticsearchDataBridge implements OnPersistInterface, OnRemoveInterface, OnFindInterface
{
    protected IndexService $indexService;

    protected array $whitelist;
    protected array $blacklist;

    public function __construct()
    {
        $this->indexService = di_get(IndexService::class);

        $this->whitelist = di_param(ParameterInterface::WHITELIST, []);
        $this->blacklist = di_param(ParameterInterface::BLACKLIST, []);
    }



    public function onPersist(array $entities): void
    {
        foreach ($entities as $entity) {
            if (!$this->isResponsible($entity::class)) {
                continue;
            }

            if (!$entity instanceof EntityInterface) {
                throw new DataException(sprintf(
                    '{5f3d8ff6-36a8-49fc-aa51-3baf2185a8d2} Entities has to implement the %s.',
                    EntityInterface::class,
                ));
            }

            if (!$entity->getPrimaryKey()) {
                throw new DataException(sprintf(
                    '{f2c0e228-24c4-4bf1-80df-f2b364d61404} Primary key on entity of class %s must not be empty.',
                    $entity::class
                ));
            }

            $this->indexService->initIndex($entity::class);

            try {
                $this->indexService->deleteEntity($entity::class, $entity->getPrimaryKey());
            } catch (Exception $exception) {
                unset($exception);
            }

            $this->indexService->addEntity($entity);
        }
    }

    public function onRemove(string $fQCN, array $primaryKeys): void
    {
        if ($this->isResponsible($fQCN)) {
            foreach ($primaryKeys as $primaryKey) {
                try {
                    $this->indexService->deleteEntity($fQCN, $primaryKey);
                } catch (Exception $exception) {
                    unset($exception);
                }
            }
        }
    }

    public function onFind(string $fQCN, array $keyValuePairs): array|null
    {
        if (!$this->isResponsible($fQCN)) {
            return null;
        }

        if (array_key_exists('.raw_body', $keyValuePairs)) {
            if (count($keyValuePairs) > 1) {
                throw new DataException(
                    '{4fcef1f6-7607-47e1-8542-bbd3e1d7fdc5} The .raw_body key has to be the only key of the outermost key value pairs.'
                );
            }

            $body = $keyValuePairs['.raw_body'];
        } else {
            $body = ["query" => ["constant_score" => ["filter" => [
                "bool" => $this->collectMustQueryPart($keyValuePairs)
            ]]]];
        }

        $response = di_get(IndexService::class)->search($fQCN, $body);

        $primaryKeys = [];

        foreach ($response['hits']['hits'] as $result) {
            if (isset($result['_id'])) {
                $pk = $result['_id'];
                $primaryKeys[$pk] = $pk;
            }
        }

        return $primaryKeys;
    }

    protected function isResponsible(string $fQCN): bool
    {
        if (empty($this->whitelist)) {
            return !array_key_exists($fQCN, $this->blacklist);
        }

        return array_key_exists($fQCN, $this->whitelist);
    }

    protected function collectMustQueryPart(array $keyValuePairs, string $prefix = ''): array
    {
        $boolQuery = ["must" => []];

        foreach ($keyValuePairs as $key => $value) {
            if (substr($key, -7, 7) === '..range') {
                $key = substr($key, 0, -7);
                foreach ($value as $k => $val) {
                    switch ($k) {
                        case '<':
                            $boolQuery["must"][] = ["range" => [$prefix.$key => ['lt' => $this->transformDateTime($val)]]];
                            break;
                        case '>':
                            $boolQuery["must"][] = ["range" => [$prefix.$key => ['gt' => $this->transformDateTime($val)]]];
                            break;
                        case '<=':
                            $boolQuery["must"][] = ["range" => [$prefix.$key => ['lte' => $this->transformDateTime($val)]]];
                            break;
                        case '>=':
                            $boolQuery["must"][] = ["range" => [$prefix.$key => ['gte' => $this->transformDateTime($val)]]];
                            break;
                    }
                }
            } elseif ($queryPart = $this->getMustQueryPart($key, $value, $prefix)) {
                $boolQuery["must"][] = $queryPart;
            }
        }

        return $boolQuery;
    }

    protected function getMustQueryPart(string $key, bool|int|float|string|DateTime|array $value, $prefix = ''): array
    {
        if (substr($key, -7, 7) === '..match') {
            $value = $this->transformDateTime($value);
            $value = is_array($value) ? implode(' ', $value) : $value;

            return ["match" => [$prefix.substr($key, 0, -7).'.text' => $value]];
        } elseif (substr($key, -6, 6) === '..text') {
            $value = $this->transformDateTime($value);
            $value = is_array($value) ? array_values($value) : $value;

            return [(is_array($value) ? "terms" : "term") => [$prefix.substr($key, 0, -6).'.text' => $value]];
        } elseif (substr($key, -8, 8) === '..exists') {
            return ['exists' => ['field' => $prefix.substr($key, 0, -8)]];
        } elseif (substr($key, -12, 12) === '..exists_not') {
            return ['bool' => ['must_not' => ['exists' => ['field' => $prefix.substr($key, 0, -12)]]]];
        } elseif (is_array($value)) {
            if (substr($key, -4, 4) === '.raw') {
                return $value;
            } elseif (substr($key, -7, 7) === '._items') {
                return ['nested' => [
                    'path' => $prefix.$key,
                    'query' => ['bool' => $this->collectMustQueryPart($value, $prefix.$key.'.')],
                ]];
            }

            return ["terms" => [$key => array_values($this->transformDateTime($value))]];
        }

        return ["term" => [$prefix.$key => $this->transformDateTime($value)]];
    }

    protected function transformDateTime(bool|int|float|string|DateTime|array $value): bool|int|float|string|array
    {
        if (is_array($value)) {
            return array_map(function($val) { return $this->transformDateTime($val); }, $value);
        }

        if ($value instanceof DateTime) {
            return $value->format('c');
        }

        return $value;
    }
}
