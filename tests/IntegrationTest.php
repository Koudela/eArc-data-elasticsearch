<?php declare(strict_types=1);
/**
 * e-Arc Framework - the explicit Architecture Framework
 *
 * @package earc/data-elasticsearch
 * @link https://github.com/Koudela/eArc-data-elasticsearch/
 * @copyright Copyright (c) 2019-2021 Thomas Koudela
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace eArc\DataElasticsearchTests;

use DateTime;
use eArc\Data\Exceptions\QueryException;
use eArc\Data\Initializer;
use eArc\Data\ParameterInterface;
use eArc\DataElasticsearch\ElasticsearchDataBridge;
use eArc\DataElasticsearch\IndexService;
use eArc\DataElasticsearchTests\Entities\Attribute;
use eArc\DataElasticsearchTests\Entities\AttributeCategory;
use eArc\DataElasticsearchTests\Entities\BlacklistedEntity;
use eArc\DataElasticsearchTests\Entities\MainImage;
use eArc\DataElasticsearchTests\Entities\Price;
use eArc\DataElasticsearchTests\Entities\Product;
use eArc\DataFilesystem\FilesystemDataBridge;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    public function init(): void
    {
        if (!function_exists('di_get')) {
            Initializer::init();

            di_tag(ParameterInterface::TAG_ON_LOAD, FilesystemDataBridge::class);

            di_tag(ParameterInterface::TAG_ON_PERSIST, ElasticsearchDataBridge::class);
            di_tag(ParameterInterface::TAG_ON_PERSIST, FilesystemDataBridge::class);

            di_tag(ParameterInterface::TAG_ON_REMOVE, ElasticsearchDataBridge::class);
            di_tag(ParameterInterface::TAG_ON_REMOVE, FilesystemDataBridge::class);

            di_tag(ParameterInterface::TAG_ON_FIND, ElasticsearchDataBridge::class);
            di_tag(ParameterInterface::TAG_ON_FIND, FilesystemDataBridge::class);

            di_set_param(\eArc\DataFilesystem\ParameterInterface::DATA_PATH, __DIR__ . '/data');
            di_set_param(\eArc\DataElasticsearch\ParameterInterface::CLIENT_HOSTS, ['localhost:32769']);

            $colour = new AttributeCategory('colour');
            data_persist($colour);
            $white = new Attribute('white', $colour);
            $black = new Attribute('black', $colour);
            $yellow = new Attribute('yellow', $colour);
            $orange = new Attribute('orange', $colour);
            $blue = new Attribute('blue', $colour);
            $silentGreen = new Attribute('Silent Green', $colour);
            data_persist_batch([$white, $black, $yellow, $orange, $blue, $silentGreen]);

            di_get(IndexService::class)->refreshIndex(Attribute::class);

            $kitchenForm = new AttributeCategory('kitchen-form');
            data_persist($kitchenForm);
            $uForm = new Attribute('u-form', $kitchenForm);
            $lForm = new Attribute('l-form', $kitchenForm);
            $islandForm = new Attribute('island-form', $kitchenForm);
            data_persist_batch([$uForm, $lForm, $islandForm]);

            di_get(IndexService::class)->refreshIndex(AttributeCategory::class);

            $laptop = new Product('laptop', 12);
            $price = new Price(120000, 'EUR', new DateTime());
            $laptop->prices->add($price);
            $table = new Product('table', 20, 'Something you can place dishes on.');
            $window = new Product('window', 113, 'When you look through something, then something looks through you.');
            $windowMainImage = new MainImage('aaf4477df', 'window');
            $window->setMainImage($windowMainImage);
            data_persist_batch([$laptop, $table, $window]);

            di_get(IndexService::class)->refreshIndex(Product::class);
        }
    }

    public function testRebuildIndex(): void
    {
        $this->init();

        di_get(IndexService::class)->rebuildIndex([
            Attribute::class,
            AttributeCategory::class,
            Product::class,
        ]);

        $this->testSearchEmpty();
        $this->testSearchPlain();
        $this->testSearchSet();
        $this->testSearchRange();
        $this->testSearchMatch();
        $this->testSearchText();
        $this->testSearchExists();
        $this->testSearchExistsNot();
        $this->testSearchId();
        $this->testSearchEmbedded();
        $this->testJoinCollection();
        $this->testJoin();
        $this->testJoinCollection();
        $this->testRawQuery();
        $this->testRawSearchBody();
    }

    public function testSearchEmpty(): void
    {
        $this->init();

        $result = data_find(Attribute::class, []);
        self::assertEquals([
            'colour::white' => 'colour::white',
            'colour::black' => 'colour::black',
            'colour::yellow' => 'colour::yellow',
            'colour::orange' => 'colour::orange',
            'colour::blue' => 'colour::blue',
            'colour::Silent+Green' => 'colour::Silent+Green',
            'kitchen-form::u-form' => 'kitchen-form::u-form',
            'kitchen-form::l-form' => 'kitchen-form::l-form',
            'kitchen-form::island-form' => 'kitchen-form::island-form',
        ], $result);
    }

    public function testSearchPlain(): void
    {
        $this->init();

        $result = data_find(Attribute::class, ['name' => 'white', 'categoryPK' => 'kitchen-form']);
        self::assertEquals([], $result);
        $result = data_find(Attribute::class, ['name' => 'island-form', 'categoryPK' => 'kitchen-form']);
        self::assertEquals([
            'kitchen-form::island-form' => 'kitchen-form::island-form',
        ], $result);

    }

    public function testSearchSet(): void
    {
        $this->init();

        $result = data_find(Attribute::class, ['name' => ['white', 'black', 'blue']]);

        self::assertEquals([
            'colour::white' => 'colour::white',
            'colour::black' => 'colour::black',
            'colour::blue' => 'colour::blue',
        ], $result);
    }

    public function testSearchRange(): void
    {
        $this->init();

        $result = data_find(Product::class, ['number..range' => ['<=' => 20]]);
        self::assertEquals([
            12 => '12',
            20 => '20',
        ], $result);
        $result = data_find(Product::class, ['number..range' => ['>' => 20]]);
        self::assertEquals([
            113 => '113',
        ], $result);
        $result = data_find(Product::class, ['number..range' => ['>' => 12, '<=' => 113]]);
        self::assertEquals([
            20 => '20',
            113 => '113',
        ], $result);
    }

    public function testSearchMatch(): void
    {
        $this->init();

        $result = data_find(Attribute::class, ['name..match' => 'Silent Green']);
        self::assertEquals([
            'colour::Silent+Green' => 'colour::Silent+Green'
        ], $result);

        $result = data_find(Attribute::class, ['name..match' => 'Silent!Green']);
        self::assertEquals([
            'colour::Silent+Green' => 'colour::Silent+Green'
        ], $result);

        $result = data_find(Attribute::class, ['name..match' => 'silent']);
        self::assertEquals([
            'colour::Silent+Green' => 'colour::Silent+Green'
        ], $result);
    }

    public function testSearchText(): void
    {
        $this->init();

        $result = data_find(Attribute::class, ['name..text' => 'Silent Green']);
        self::assertEquals([], $result);

        $result = data_find(Attribute::class, ['name..text' => 'Green']);
        self::assertEquals([], $result);

        $result = data_find(Attribute::class, ['name..text' => 'green']);
        self::assertEquals([
            'colour::Silent+Green' => 'colour::Silent+Green'
        ], $result);
    }

    public function testSearchExists(): void
    {
        $this->init();

        $result = data_find(Product::class, ['description..exists' => null]);
        self::assertEquals([
            20 => '20',
            113 => '113',
        ], $result);
        $result = data_find(Product::class, ['mainImage..exists' => null]);
        self::assertEquals([
            113 => '113',
        ], $result);
    }

    public function testSearchExistsNot(): void
    {
        $this->init();

        $result = data_find(Product::class, ['description..exists_not' => null]);
        self::assertEquals([
            12 => '12',
        ], $result);
        $result = data_find(Product::class, ['mainImage..exists_not' => null]);
        self::assertEquals([
            12 => '12',
            20 => '20',
        ], $result);
    }

    public function testSearchId(): void
    {
        $this->init();

        $result = data_find(Attribute::class, ['_id' => ['colour::black', 'colour::white', 'colour::not-available']]);
        self::assertEquals([
            'colour::white' => 'colour::white',
            'colour::black' => 'colour::black',
        ], $result);
    }

    public function testSearchEmbedded(): void
    {
        $this->init();

        $result = data_find(Product::class, ['mainImage.alt' => 'window']);
        self::assertEquals([
            113 => '113',
        ], $result);
    }

    public function testSearchEmbeddedCollection(): void
    {
        $this->init();

        $result = data_find(Product::class, ['prices._items' => [
            'price..range' => ['>' => '0']
        ]]);

        self::assertEquals([
            12 => '12',
        ], $result);
    }

    public function testJoin(): void
    {
        $this->init();

        $primaryKeys = data_find(AttributeCategory::class, ['name' => 'colour']);
        $result = data_find(Attribute::class, ['categoryPK' => $primaryKeys]);

        self::assertEquals([
            'colour::white' => 'colour::white',
            'colour::black' => 'colour::black',
            'colour::yellow' => 'colour::yellow',
            'colour::orange' => 'colour::orange',
            'colour::blue' => 'colour::blue',
            'colour::Silent+Green' => 'colour::Silent+Green',
        ], $result);
    }

    public function testJoinCollection(): void
    {
        $this->init();

        $primaryKeys = data_find(Attribute::class, ['name..match' => ['white']]);
        $result = data_find(AttributeCategory::class, ['attributes.items' => $primaryKeys]);

        self::assertEquals([
            'colour' => 'colour',
        ], $result);
    }

    public function testRawQuery(): void
    {
        $this->init();

        $result = data_find(Product::class, [
            'prices._items' => [
                'offerStartDate.raw' => ['bool' => ['should' => [ // OR
                    ['bool' => ['must_not' => ['exists' => ['field' => 'offerStartDate']]]], // NULL
                    ['range' => ['offerStartDate' => ['lte' => 'now']]], // >= NOW
                ]]],
            ],
        ]);

        self::assertEquals([
            12 => '12',
        ], $result);
    }

    public function testRawSearchBody(): void
    {
        $this->init();

        $result = data_find(Product::class, [
            '.raw_body' => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'bool' => [
                                'must' => [
                                    ['exists' => ['field' => 'name']],
                                    ['range' => ['number' => ['lte' => '20']]],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertEquals([
            12 => '12',
            20 => '20',
        ], $result);
    }

    public function testBlacklist(): void
    {
        $this->init();

        di_set_param(\eArc\DataElasticsearch\ParameterInterface::BLACKLIST, [BlacklistedEntity::class => true]);
        di_clear_cache(ElasticsearchDataBridge::class);
        $blacklisted = new BlacklistedEntity('hello world');
        data_persist($blacklisted);

        $result = data_find(BlacklistedEntity::class, []);
        self::assertEquals([
            'hello world' => 'hello world',
        ], $result);

        $exception = null;
        try {
            data_find(BlacklistedEntity::class, ['_id' => 'hello world']);
        } catch (QueryException $exception) {

        }
        self::assertTrue($exception instanceof QueryException);
        self::assertTrue(str_contains($exception->getMessage(), '{fa2b3bb2-c6a9-4117-ae8b-57f9463a3f2d}'));
    }

    public function testWhitelist(): void
    {
        $this->init();

        di_set_param(\eArc\DataElasticsearch\ParameterInterface::WHITELIST, [AttributeCategory::class => true]);
        di_clear_cache(ElasticsearchDataBridge::class);

        $result = data_find(AttributeCategory::class, ['_id' => 'colour']);
        self::assertEquals([
            'colour' => 'colour',
        ], $result);

        $exception = null;
        try {
            data_find(Attribute::class, ['_id' => 'hello world']);
        } catch (QueryException $exception) {

        }
        self::assertTrue($exception instanceof QueryException);
        self::assertTrue(str_contains($exception->getMessage(), '{fa2b3bb2-c6a9-4117-ae8b-57f9463a3f2d}'));

    }

    public function testIndexName(): void
    {
        $this->init();

        $indexName = di_get(IndexService::class)->getIndexName(Attribute::class);
        self::assertEquals('earc-data-earc-dataelasticsearchtests-entities-attribute', $indexName);
        di_set_param(\eArc\DataElasticsearch\ParameterInterface::INDEX_PREFIX, 'test-case');
        di_clear_cache(IndexService::class);
        $indexName = di_get(IndexService::class)->getIndexName(Attribute::class);
        self::assertEquals('test-case-earc-dataelasticsearchtests-entities-attribute', $indexName);
    }
}
