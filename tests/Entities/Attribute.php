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

use eArc\Data\Entity\AbstractEntity;
use eArc\Data\Entity\Interfaces\EntityInterface;
use eArc\Data\Entity\Interfaces\Events\PrePersistInterface;
use eArc\Data\Manager\Interfaces\DataStoreInterface;
use eArc\Data\Manager\Interfaces\Events\OnPersistInterface;

class Attribute extends AbstractEntity implements PrePersistInterface
{
    public string $name;
    protected string|null $categoryPK;

    public function __construct(string $name, AttributeCategory $category)
    {
        $this->primaryKey = $category->getName().'::'.$name;
        $this->name = $name;

        if (is_null($category->getPrimaryKey())) {
            data_persist($category);
        }

        $this->categoryPK = $category->getPrimaryKey();
        $category->addAttribute($this);
    }

    public function getCategory(): AttributeCategory
    {
        return data_load(AttributeCategory::class, $this->categoryPK);
    }

    public function prePersist(EntityInterface $entity): void
    {
        data_schedule(data_load(AttributeCategory::class, $this->categoryPK));
    }
}
