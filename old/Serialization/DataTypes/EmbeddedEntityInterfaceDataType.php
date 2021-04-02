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

use eArc\Data\Entity\Interfaces\EmbeddedEntityInterface;
use eArc\Serializer\DataTypes\Interfaces\DataTypeInterface;
use eArc\Serializer\Exceptions\SerializeException;
use eArc\Serializer\SerializerTypes\Interfaces\SerializerTypeInterface;
use eArc\Serializer\Services\FactoryService;
use eArc\Serializer\Services\SerializeService;

class EmbeddedEntityInterfaceDataType implements DataTypeInterface
{
    public function isResponsibleForSerialization(?object $object, $propertyName, $propertyValue): bool
    {
        return $propertyValue instanceof EmbeddedEntityInterface;
    }

    public function serialize(?object $object, $propertyName, $propertyValue, SerializerTypeInterface $serializerType): array
    {
        if (!$propertyValue instanceof EmbeddedEntityInterface) {
            throw new SerializeException(sprintf(
                '{664b39c6-7a5a-4eba-8d35-652ba0d30a62} Responsibility failure. Property value has to be an instance of %s.', EmbeddedEntityInterface::class
            ));
        }
        return [
            'type' => get_class($propertyValue),
            'value' => di_get(SerializeService::class)->getAsArray($propertyValue, $serializerType),
        ];
    }

    public function isResponsibleForDeserialization(?object $object, string $type, $value): bool
    {
        return is_subclass_of($type, EmbeddedEntityInterface::class, true);
    }

    public function deserialize(?object $object, string $type, $value, SerializerTypeInterface $serializerType): EmbeddedEntityInterface
    {
        $object = di_get(FactoryService::class)->initObject($type);

        return di_get(FactoryService::class)->attachProperties($object, $value, $serializerType);
    }
}
