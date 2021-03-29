<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data-elasticsearch
 * @link https://github.com/Koudela/eArc-data-elasticsearch/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\DataElasticsearch;

class ElasticsearchService
{
    /**
     * @param string[]|null $entityClassNames
     */
    public function reBuildIndex(array|null $entityClassNames): void
    {
        di_get(ElasticsearchDataBridge::class)->reBuildIndex($entityClassNames);
    }
}
