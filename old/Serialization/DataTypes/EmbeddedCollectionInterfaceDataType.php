<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data
 * @link https://github.com/Koudela/eArc-data/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\Data\Serialization\DataTypes;

use eArc\Data\Collection\EmbeddedCollection;
use eArc\Data\Collection\Interfaces\EmbeddedCollectionInterface;
use eArc\Data\Entity\Interfaces\EmbeddedEntityInterface;
use eArc\Data\Entity\Interfaces\EntityBaseInterface;
use eArc\Serializer\DataTypes\Interfaces\DataTypeInterface;
use eArc\Serializer\Exceptions\SerializeException;
use eArc\Serializer\SerializerTypes\Interfaces\SerializerTypeInterface;
use eArc\Serializer\Services\FactoryService;
use eArc\Serializer\Services\SerializeService;
use ReflectionClass;
use ReflectionException;

class EmbeddedCollectionInterfaceDataType implements DataTypeInterface
{
    public function isResponsibleForSerialization(?object $object, $propertyName, $propertyValue): bool
    {
        return $propertyValue instanceof EmbeddedCollectionInterface;
    }

    public function serialize(?object $object, $propertyName, $propertyValue, SerializerTypeInterface $serializerType): array
    {
        if (!$propertyValue instanceof EmbeddedCollectionInterface) {
            throw new SerializeException(sprintf(
                '{98f03a83-e885-4ed8-a941-0ba63fe29f5e} Responsibility failure. Property value has to be an instance of %s.', EmbeddedCollectionInterface::class
            ));
        }

        $embeddedEntities = [];
        foreach ($propertyValue->asArray() as $embeddedEntity) {
            $embeddedEntities[] = di_get(SerializeService::class)->getAsArray($embeddedEntity, $serializerType);
        }

        return [
            'type' => get_class($propertyValue),
            'value' => [
                'fQCN' => $propertyValue->getEntityName(),
                'embeddedEntities' => $embeddedEntities,
            ]
        ];
    }

    public function isResponsibleForDeserialization(?object $object, string $type, $value): bool
    {
        return is_subclass_of($type, EmbeddedCollectionInterface::class, true);
    }

    public function deserialize(?object $object, string $type, $value, SerializerTypeInterface $serializerType): EmbeddedCollection
    {
        /** @var EntityBaseInterface $object */
        $embeddedCollection = new EmbeddedCollection($object, $value['fQCN']);

        foreach ($value['embeddedEntities'] as $embeddedEntityData) {
            $embeddedEntity = di_get(FactoryService::class)->initObject($value['fQCN']);
            di_get(FactoryService::class)->attachProperties($embeddedEntity, $embeddedEntityData, $serializerType);
            if ($embeddedEntity instanceof EmbeddedEntityInterface) {
                $embeddedCollection->add($embeddedEntity);
            }
        }

        return $embeddedCollection;
    }
}
