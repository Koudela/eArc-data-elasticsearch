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

use eArc\Data\Entity\AbstractEmbeddedEntity;

class MainImage extends AbstractEmbeddedEntity
{
    protected string $src;
    protected string $alt;

    public function __construct(string $src, string $alt)
    {
        $this->src = $src;
        $this->alt = $alt;
    }
}
