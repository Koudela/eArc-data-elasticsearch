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
use eArc\Data\Collection\Interfaces\CollectionInterface;
use eArc\Data\Collection\Interfaces\EmbeddedCollectionInterface;
use eArc\Data\Entity\Interfaces\EmbeddedEntityInterface;
use eArc\Data\Entity\Interfaces\EntityInterface;
use ReflectionClass;

class DocumentFactory
{
    public function build(EntityInterface $entity): array
    {
        $documentBody = [
            '_timestamp' => (new DateTime)->format('c'),
        ];
        $this->generateEntityData([], $entity, $documentBody, '');

        return $documentBody;
    }

    /**
     * @param array<EntityInterface|EmbeddedEntityInterface|CollectionInterface|EmbeddedCollectionInterface> $parents
     * @param array $data
     * @param string $name
     * @param mixed $value
     */
    protected function addData(array $parents, array &$data, string $name, mixed $value): void
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->addValue($data, $name, $val);
            }
        } elseif ($value instanceof DateTime) {
            $this->addValue($data, $name, $value->format('c'));
        } elseif (is_object($value)) {
            if ($value instanceof EmbeddedEntityInterface) {
                if (!in_array($value, $parents)) {
                    $data[$name] = [];
                    $this->generateEntityData(array_merge($parents, [$value]), $value, $data[$name], '');
                }
            } elseif ($value instanceof CollectionInterface) {
                $data[$name] = [];
                $this->addValue($data[$name], '_entityName', $value->getEntityName());
                $this->addValue($data[$name], '_items', $value->getPrimaryKeys());
            } elseif ($value instanceof EmbeddedCollectionInterface) {
                $data[$name] = [];
                $this->addValue($data[$name], '_entityName', $value->getEntityName());
                if (!in_array($value, $parents)) {
                    $data[$name]['_items'] = [];
                    $parents = array_merge($parents, [$value]);
                    /** @var EmbeddedEntityInterface $item */
                    foreach ($value->asArray() as $item) {
                        if (!in_array($item, $parents)) {
                            $itemData = [];
                            $this->generateEntityData($parents, $item, $itemData, '');
                            $data[$name]['_items'][] = $itemData;
                        }
                    }
                }
            }
        } else {
            $this->addValue($data, $name, $value);
        }
    }

    protected function addValue(array &$data, string $name, mixed $value): void
    {
        if (!array_key_exists($name, $data)) {
            $data[$name] = [];
        }

        $data[$name][] = $value;
    }

    /**
     * @param array<EntityInterface|EmbeddedEntityInterface|CollectionInterface|EmbeddedCollectionInterface> $parents
     * @param EmbeddedEntityInterface|EntityInterface $entity
     * @param array $data
     * @param string $path
     */
    protected function generateEntityData(array $parents, EmbeddedEntityInterface|EntityInterface $entity, array &$data, string $path)
    {
        $reflectionClass = new ReflectionClass($entity);
        do {
            foreach ($reflectionClass->getProperties() as $reflectionProperty) {
                $reflectionProperty->setAccessible(true);
                $value = $reflectionProperty->getValue($entity);
                $name = $path.$reflectionProperty->getName();
                $this->addData($parents ,$data, $name, $value);
            }
        } while ($reflectionClass = $reflectionClass->getParentClass());
    }
}
