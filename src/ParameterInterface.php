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

interface ParameterInterface
{
    /** @link https://elastica-docs.readthedocs.io/en/latest/client.html */
    const ELASTICA_CLIENT_CONFIG = 'earc.data_elasticsearch.elastica_client_config'; // default [host => 'localhost', post => 9200]
    const INDEX_NAME = 'earc.data_elasticsearch.index_name'; // default 'earc-data'
    const WHITELIST = 'earc.data_elasticsearch.whitelist'; // default []
    const BLACKLIST = 'earc.data_elasticsearch.blacklist'; // default []
}
