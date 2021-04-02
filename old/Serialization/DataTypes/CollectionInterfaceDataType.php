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

use eArc\Data\Collection\Collection;
use eArc\Data\Collection\Interfaces\CollectionInterface;
use eArc\Data\Entity\Interfaces\EntityBaseInterface;
use eArc\Serializer\DataTypes\Interfaces\DataTypeInterface;
use eArc\Serializer\Exceptions\SerializeException;
use eArc\Serializer\SerializerTypes\Interfaces\SerializerTypeInterface;

class CollectionInterfaceDataType implements DataTypeInterface
{
    public function isResponsibleForSerialization(?object $object, $propertyName, $propertyValue): bool
    {
        return $propertyValue instanceof CollectionInterface;
    }

    public function serialize(?object $object, $propertyName, $propertyValue, SerializerTypeInterface $serializerType): array
    {
        if (!$propertyValue instanceof CollectionInterface) {
            throw new SerializeException(sprintf(
                '{08d03369-65c4-4628-b87e-93d713519c61} Responsibility failure. Property value has to be an instance of %s.', CollectionInterface::class
            ));
        }

        return [$propertyName => [
            'interface' => CollectionInterface::class,
            'fQCN' => $propertyValue->getEntityName(),
            'primaryKeys' => $propertyValue->getPrimaryKeys(),
        ]];
    }

    public function isResponsibleForDeserialization(?object $object, string $type, $value): bool
    {
        return is_subclass_of($type, CollectionInterface::class, true);
    }

    public function deserialize(?object $object, string $type, $value, SerializerTypeInterface $serializerType): Collection
    {
        /** @var EntityBaseInterface $object */
        $collection = new Collection($object, $value['fQCN']);

        foreach ($value['primaryKeys'] as $primaryKey) {
            $collection->add($primaryKey);
        }

        return $collection;
    }
}
