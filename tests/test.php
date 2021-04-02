<?php declare(strict_types=1);

namespace eArc\DataElasticsearchTests;

use BootstrapEArcData;
use DateTime;
use eArc\Data\Collection\EmbeddedCollection;
use eArc\Data\Entity\AbstractEmbeddedEntity;
use eArc\Data\Entity\AbstractEntity;
use eArc\Data\Entity\Interfaces\EmbeddedEntityInterface;
use eArc\Data\ParameterInterface;
use eArc\DataElasticsearch\ElasticsearchDataBridge;
use eArc\DataElasticsearchTests\Entities\Attribute;
use eArc\DataElasticsearchTests\Entities\AttributeCategory;
use eArc\DataElasticsearchTests\Entities\Product;

include(__DIR__.'/../vendor/autoload.php');
include(__DIR__.'/../vendor/earc/data/bootstrap/BootstrapEArcData.php');

class test extends AbstractEntity
{
    protected Embedded $embedded;
    protected string $bluebird = 'blue';
    private $date;
    public $samba = 3;

    public function __construct()
    {
        $this->date = new DateTime();
        $this->primaryKey = 'alpha';
        $this->embedded = new Embedded($this);
    }

    public function getBluebird() {
        return $this->bluebird;
    }
}

class Embedded extends AbstractEmbeddedEntity
{
    private string $zustand = 'ill';
    private EmbeddedCollection $embeddedCollection;

    public function __construct($ownerEntity)
    {
        $this->ownerEntity = $ownerEntity;
        $this->embeddedCollection = new EmbeddedCollection($this, Embedded2::class);
        $this->embeddedCollection->add(new Embedded2($this, 'bla bla'));
        $this->embeddedCollection->add(new Embedded2($this, 'bla blub'));
    }
}

class Embedded2 extends AbstractEmbeddedEntity
{
    protected $hello = 'world';
    public $cnt = 0;

    public function __construct($ownerEntity, $hello)
    {
        $this->ownerEntity = $ownerEntity;
        $this->hello = $hello;
        $this->cnt = rand(0, 9);
    }
}

BootstrapEArcData::init();

di_set_param(\eArc\DataElasticsearch\ParameterInterface::CLIENT_HOSTS, ['http://localhost:32775']);
di_tag(ParameterInterface::TAG_ON_PERSIST, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_FIND, ElasticsearchDataBridge::class);

$test = new test();

#data_persist($test);

dump(data_find(test::class, ['primaryKey' => 'alpha']));
exit;
#dump(data_find(test::class, ['bluebird' => ['green', 'blue', 'violet'], '_id' => 'alpha', 'date' => '2021-04-02']));
#dump(data_find(test::class, ['_id' => 'alpha', 'samba' => '3']));
#dump(data_find(test::class, ['_id' => 'alpha', 'bluebird' => 'blue']));
#dump(data_find(test::class, ['date..range' => ['>' => '2021-03-29T23:00']]));

#dump(data_find(test::class, ['embedded.embeddedCollection._items' => ['hello..match' => ['BlubX', 'Kartoffel', 'BLA']]]));
#dump(data_find(test::class, ['_id' => ['alpha' => 'alpha', 'beta' => 'beta', 'gamma' => 'gamma']]));
#dump(data_find(test::class, [
//    '.raw' => [
//        'nested' => [
//            'path' => 'embedded.embeddedCollection._items',
//            'query' => [
//                'bool' => [
//                    'must' => [
//                        ['match' => ['embedded.embeddedCollection._items.hello.text' => 'blub']],
//                        ['term' => ['embedded.embeddedCollection._items.cnt' => 4]],
//                    ]
//                ]
//            ]
//        ],
//    ],
//    'embedded.embeddedCollection._items' => [
//        'hello..match' => 'blub',
//        'cnt' => 4,
//    ]
    #'embedded.embeddedCollection._items.hello..match' => 'bla bla',
    #'embedded.embeddedCollection._items.cnt' => 4,
    #]));

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
    data_load_stack(ProductBoxTemplate::class, $products)
));

