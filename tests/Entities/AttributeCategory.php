<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data-elasticsearch
 * @link https://github.com/Koudela/eArc-data-elasticsearch/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\DataElasticsearchTests\Entities;

use eArc\Data\Collection\Collection;
use eArc\Data\Entity\AbstractEntity;

class AttributeCategory extends AbstractEntity
{
    private string $name;
    private Collection $attributes;

    public function __construct(string $name)
    {
        $this->primaryKey = $name;
        $this->name = $name;
        $this->attributes = new Collection($this, Attribute::class);
    }

    public function getName(): string
    {
        return $this->primaryKey;
    }

    public function addAttribute(Attribute $attribute)
    {
        $this->attributes->add($attribute->getPrimaryKey());
    }
}
