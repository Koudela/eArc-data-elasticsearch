<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data
 * @link https://github.com/Koudela/eArc-data/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\Data\Serialization;

use eArc\Data\Serialization\DataTypes\CollectionInterfaceDataType;
use eArc\Data\Serialization\DataTypes\EmbeddedCollectionInterfaceDataType;
use eArc\Data\Serialization\DataTypes\EmbeddedEntityInterfaceDataType;
use eArc\Data\Serialization\DataTypes\EntityInterfaceDataType;
use eArc\Serializer\DataTypes\ArrayDataType;
use eArc\Serializer\DataTypes\ClassDataType;
use eArc\Serializer\DataTypes\DateTimeDataType;
use eArc\Serializer\DataTypes\ObjectDataType;
use eArc\Serializer\DataTypes\SimpleDataType;
use eArc\Serializer\SerializerTypes\Interfaces\SerializerTypeInterface;

class SerializerType implements SerializerTypeInterface
{
    public function getDataTypes(): iterable
    {
        yield CollectionInterfaceDataType::class => null;
        yield EmbeddedCollectionInterfaceDataType::class => null;
        yield EmbeddedEntityInterfaceDataType::class => null;
        yield EntityInterfaceDataType::class => null;

        yield DateTimeDataType::class => null;
        yield SimpleDataType::class => null;
        yield ArrayDataType::class => null;
        yield ClassDataType::class => null;
        yield ObjectDataType::class => null;
    }

    public function filterProperty(string $fQCN, string $propertyName): bool
    {
        return true;
    }
}
