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
use eArc\Data\Entity\AbstractEmbeddedEntity;

class Price extends AbstractEmbeddedEntity
{
    public int $price;
    protected string $currency;
    private DateTime|null $offerStartDate;

    public function __construct(int $price, string $currency, DateTime|null $offerStartDate = null)
    {
        $this->price = $price;
        $this->currency = $currency;
        $this->offerStartDate = $offerStartDate;
    }
}
