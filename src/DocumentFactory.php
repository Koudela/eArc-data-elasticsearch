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
use Elastica\Document;
use ReflectionClass;

class DocumentFactory
{
    public function build(EntityInterface $entity): Document
    {
        $document = new Document($entity::class.'::'.$entity->getPrimaryKey());
        $data = ['~primaryKey' => $entity->getPrimaryKey()];
        $this->generateEntityData([], $entity, $data, '');
        $document->setData($data);
dump($document);
        return $document;
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
            $this->addValue($data, $name, $value->format('Y-m-d').'T'.$value->format('H:i:s'));
        } elseif (is_object($value)) {
            if ($value instanceof EmbeddedEntityInterface) {
                if (!in_array($value, $parents)) {
                    $this->generateEntityData(array_merge($parents, [$value]), $value, $data, $name.'.');
                }
            } elseif ($value instanceof CollectionInterface) {
                $this->addValue($data, $name.'.~entityName', $value->getEntityName());
                $this->addValue($data, $name.'.~primaryKeys', $value->getPrimaryKeys());
            } elseif ($value instanceof EmbeddedCollectionInterface) {
                $this->addValue($data, $name.'.~entityName', $value->getEntityName());
                /** @var EmbeddedEntityInterface $item */
                foreach ($value->asArray() as $item) {
                    $this->generateEntityData($parents, $item, $data, $name.'.~items.');
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

        $data[$name] = (string) $value;
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
