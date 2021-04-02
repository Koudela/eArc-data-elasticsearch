# eArc-data-elasticsearch

Elasticsearch-bridge for the [earc/data](https://github.com/Koudela/eArc-data) 
persistence handler.

## installation

Install the earc data elasticsearch library via composer.

```shell
$ composer require earc/data-elasticsearch
```

## basic usage

### bootstrap

If your elasticsearch server is *not* located at `localhost:9200` or you need to
authenticate, you have to configure it.

```php
use eArc\DataElasticsearch\ParameterInterface;

$hosts = ['https://user:pass@elasticsearch.my-server.com:32775'];

di_set_param(ParameterInterface::CLIENT_HOSTS, $hosts);
```

Initialize the earc/data package.

```php
BootstrapEArcData::init();
```

Then register the earc/data-elasticsearch bridge.

```php
use eArc\Data\ParameterInterface;
use eArc\DataElasticsearch\ElasticsearchDataBridge;

di_tag(ParameterInterface::TAG_ON_PERSIST, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_REMOVE, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_FIND, ElasticsearchDataBridge::class);
```

Now your entities will be indexed and removed from the index automatically.
You are ready to search your earc/data entities via elasticsearch.

### initialize index

If you have persisted entities before installing the earc/data-elasticsearch bridge,
you can use `IndexService::rebuildIndex()` to index the entities that are in your
data-store already.

```php
use eArc\DataElasticsearch\IndexService;

di_get(IndexService::class)->rebuildIndex([
    // list of your entity classes
]);
```

This has to be done only once.

### search

To search is very straight forward. 

```php
$primaryKeys = data_find(MyUserEntity::class, ['name' => ['Max', 'Moritz'], 'age' => 21]);
$userEntities = data_load_stack(MyUserEntity::class, $primaryKeys);
```

This would find all entities of the `MyUserEntitiy` class with a `name` property of
`Max` or `Moritz` and an `age` property of `21`.

If you don't need the primary keys `data_find_entities` is shorter.

```php
$userEntities = data_find_entities(MyUserEntity::class, ['name' => ['Max', 'Moritz'], 'age' => 21]);
```

To find all existing primary keys use the empty array.

```php
$allUserPrimaryKeys = data_find(MyUserEntity::class, []);
```

### enhanced syntax

earc/data-elasticsearch supports more than the complete earc/data `data_find` syntax.
From the support for ranges and full text search to every elasticsearch query possible.
This one size fits all approach is simple to start with, but as hard as the
elasticsearch dsl to master.

#### range

The use of the range is done via a `..range` postfix.

```php
data_find(MyUserEntity::class, ['age..range' => ['>' => 18]]);
```

This gives an open range above 18. 

Closed ranges are possible too.

```php
data_find(MyUserEntity::class, ['lastLogin..range' => ['>=' => '2021-01-01', '<=' => '2021-01-31']]);
```

#### match

To perform a full text search (use the elasticsearch verb `match` against a 
`text` field) use the `..match` postfix.

```php
data_find(MyUserEntity::class, ['city..match' => 'Münster']);
```

#### text

The `..text` postfix gives a keyword (`term`) search against a `text` field. This
becomes handy if you search a single word but doesn't know if your target
is uppercase, lowercase or uppercase first.

```php
data_find(Price::class, ['currency..text' => 'eur']);
```

#### exists

Elasticsearch does not know `null` values. Instead of `IS NOT NULL` you can check 
if a property exists, which is in most cases the same.

```php
data_find(Price::class, ['currency..exists' => null]);
```

Or check if a property does not exist, which is similar to `IS NULL`.

```php
data_find(Price::class, ['currency..exists_not' => null]);
```

#### _id

The getter `getPrimaryKey()` is used as elasticsearch document id. Thus, you can
use the property `_id` to test against one or more primary keys.

```php
data_find(MyUserEntity::class, ['_id' => ['1', '2', '392']]);
```

#### embedded entity

To query embedded entities you can use the dot syntax.

```php
data_find(MyUserEntity::class, [
    'login.email' => 'kai@email.com',
    'login.password' => 'l9TFoW5549', 
]);
```

#### embedded entity collection (nested)

Embedded entity collections have two properties `_entityName` and `_items`. `_items`
invoke a nested query.

```php
data_find(MyUserEntity::class, [
    'group._entityName' => Permission::class,
    'group._items' => [
        'name' => ['admin', 'moderator'],
        'active' => true,
    ], 
]);
```

#### joins

Elasticsearch does not know joins, but they can be realized via two separate queries.

```php
$colorCategories = data_find(AttributeCategory::class, ['name..match' => ['colour', 'color']]);
$colors = data_find(Attribute::class, [
    'attributeCategory' => $colorCategories,
    'name..match' => ['blue', 'green', 'violet'],
]);
```

#### raw query

You can always call upon the raw power of the elasticsearch dsl via the `.raw` postfix.

```php
data_find(Price::class, [        
    'offerStartDate.raw' => ['bool' => ['must' => [
        ['exists' => ['field' => 'offerStartDate']],
        ['range' => ['offerStartDate' => ['lte' => 'now']]],
    ]]],
]);
```

#### raw search body

To write the complete search in the elasticsearch dsl use the `.raw_body` key.
```php
data_find(Price::class, [        
    '.raw_body' => [
        'query' => [
            'constant_score' => [
                'filter' => [
                    'bool' => [
                        'must' => [
                            ['exists' => ['field' => 'offerStartDate']],
                            ['range' => ['offerStartDate' => ['lte' => 'now']]],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
```

## advanced usage

### index name

The indices of the entities are named `earc-data-` plus the lowercase version
of the fully qualified class name where the backslash `\\` is replaced by 
the minus sign `-`. The `earc-data` prefix can be configured.

```php
use eArc\DataElasticsearch\ParameterInterface;

di_set_param(ParameterInterface::INDEX_PREFIX, 'my-index-prefix');
```

### whitelist and blacklist

All entities are indexed by default. This can be changed via whitelisting or
blacklisting.

```php
use eArc\DataElasticsearch\ParameterInterface;

di_set_param(ParameterInterface::WHITELIST, [
    // list of entity class names
]);
```

Only the entities on the whitelist will be indexed.

```php
use eArc\DataElasticsearch\ParameterInterface;

di_set_param(ParameterInterface::BLACKLIST, [
    // list of entity class names
]);
```

All but the entities on the blacklist will be indexed.

If black- and whitelist is configured the whitelist is used only.

### extend the elasticsearch bridge

To extend the elasticsearch bridge just decorate one of its classes.

```php
use eArc\DataElasticsearch\DocumentFactory;

di_decorate(DocumentFactory::class, MyDocumentFactory::class);
```

Since there are only three classes (`DocumentFactory`, `ElasticsearchDataBridge` and
`IndexService`) copy and pasting them into your project is a reasonable option.

## releases

### release v0.0

* the first official release
* php ^8.0 support
* elasticsearch ^7.0 support
