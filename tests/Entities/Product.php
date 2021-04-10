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

use DateTime;
use eArc\Data\Collection\EmbeddedCollection;
use eArc\Data\Entity\AbstractEntity;

class Product extends AbstractEntity
{
    public EmbeddedCollection $prices;
    public MainImage|null $mainImage = null;
    protected string $name;
    protected int $number;
    private DateTime $date;
    protected string|null $description;

    public function __construct(string $name, int $number, string|null $description = null)
    {
        $this->name = $name;
        $this->primaryKey = (string) $number;
        $this->number = $number;
        $this->prices = new EmbeddedCollection($this, Price::class);
        $this->date = new DateTime();
        $this->description = $description;
    }

    public function setMainImage(MainImage|null $mainImage): void
    {
        $mainImage->setOwnerEntity($this);
        $this->mainImage = $mainImage;
    }
}
