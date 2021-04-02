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

use eArc\Data\Entity\Interfaces\EntityInterface;
use eArc\Serializer\DataTypes\Interfaces\DataTypeInterface;
use eArc\Serializer\Exceptions\SerializeException;
use eArc\Serializer\SerializerTypes\Interfaces\SerializerTypeInterface;

class EntityInterfaceDataType implements DataTypeInterface
{
    public function isResponsibleForSerialization(?object $object, $propertyName, $propertyValue): bool
    {
        return $propertyValue instanceof EntityInterface;
    }

    public function serialize(?object $object, $propertyName, $propertyValue, SerializerTypeInterface $serializerType): float|int|array|string|null
    {
        throw new SerializeException(sprintf(
            '{f18a6531-c0da-49a4-8d3b-c72430a4ab66} A property with a direct reference to an entity is disallowed. Use the primary key of %s and a dynamic getter instead.',
            get_class($propertyValue)
        ));
    }

    public function isResponsibleForDeserialization(?object $object, string $type, $value): bool
    {
        return is_subclass_of($type, EntityInterface::class, true);
    }

    public function deserialize(?object $object, string $type, $value, SerializerTypeInterface $serializerType): float|object|array|int|string|null
    {
        throw new SerializeException(sprintf(
            '{0480ea6e-1289-4694-8da9-a886d777efe3} A property with a direct reference to an entity is disallowed. Use the primary key of %s and a dynamic getter instead.',
            $type
        ));
    }
}
