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
    /** @link https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/connceting.html#auth-http */
    const CLIENT_HOSTS = 'earc.data_elasticsearch.client_hosts'; // default ['http://localhost:9200']
    const INDEX_PREFIX = 'earc.data_elasticsearch.index_prefix'; // default 'earc-data'
    const WHITELIST = 'earc.data_elasticsearch.whitelist'; // default []
    const BLACKLIST = 'earc.data_elasticsearch.blacklist'; // default []
}
