<?php declare(strict_types=1);

namespace eArc\DataElasticsearchTests;

use eArc\DataElasticsearchTests\Entities\Attribute;
use eArc\DataElasticsearchTests\Entities\AttributeCategory;
use eArc\DataElasticsearchTests\Entities\Product;

include(__DIR__.'/../vendor/autoload.php');

class ProductBoxTemplate {}

// complex query implemented via elasticsearch using a simple key-value dialect
// as abstraction like doctrine `findBy([...])` but much more extendable
// -> one size fits all (simple to start with, hard to master)

// .match => uses MATCH query on TEXT field
$colorCategories = data_find(AttributeCategory::class, ['name..match' => 'Farbe']);
$colors = data_find(Attribute::class, [
    // first level is composed via BOOL > MUST (AND)
    // uses TERMS query on primaryKeys (OR) as substitute for a join
    'attributeCategory' => $colorCategories,
    // uses MATCH query on TEXT field against 'blau grün violett' (OR)
    'name..match' => ['blau', 'grün', 'violett'],
]);
$products = data_find(Product::class, [
    'attribute' => $colors,
    'stock..range' => ['>' => 0], // uses RANGE query on LONG field
    'availability' => 'online', // uses TERM query on KEYWORD field
    'prices._items' => [ // uses NESTED query for a collection of embedded entities
        // in nested queries first level is joint via BOOL > MUST (AND) too
        'offerStartDate..exists' => null, // NOT NULL
        'offerStartDate..range' => ['<=' => 'now'], // <= NOW
        // .raw => put the elasticsearch query language in use directly
        'offerEndDate.raw' => ['bool' => ['should' => [ // OR
            ['bool' => ['must_not' => ['exists' => ['field' => 'offerEndDate']]]], // NULL
            ['range' => ['offerEndDate' => ['gte' => 'now']]], // >= NOW
        ]]],
        'price..range' => ['<=' => 15000, '>' => 5000], // uses two RANGE queries on LONG field
        'currency..text' => 'eur', // uses TERM query on TEXT field
    ],
]);
echo implode('', array_map(
    function (ProductBoxTemplate $entity) { return $entity->getTemplate(); },
    // load templates individually from memcache (1) or filesystem (2) or render it (3)
    // (they will be saved if rendered and deleted if related entities are updated or removed)
    data_load_batch(ProductBoxTemplate::class, $products)
));
