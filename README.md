# eArc-data-elasticsearch

Elasticsearch-bridge for the [earc/data](https://github.com/Koudela/eArc-data) 
persistence handler.

## installation

Install the earc data elasticsearch library via composer.

```shell
$ composer require earc/data
```

## basic usage

```php
use eArc\Data\Entity\AbstractEntity;use eArc\Data\ParameterInterface;
use eArc\DataElasticsearch\ElasticsearchDataBridge;

BootstrapEArcData::init();

di_tag(ParameterInterface::TAG_ON_PERSIST, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_REMOVE, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_FIND, ElasticsearchDataBridge::class);

// ...

class MyUserEntity extends AbstractEntity 
{
// ...
}

$primaryKeys = data_find(MyUserEntity::class, ['name' => 'Max', 'age' => [19, 20, 21]]);
$userEntities = data_load_stack(MyUserEntity::class, $primaryKeys);

// or

$userEntities = data_find_entities(MyUserEntity::class, ['name' => 'Max', 'age' => [19, 20, 21]]);
```

```php
use eArc\DataElasticsearch\ElasticsearchService;

BootstrapEArcData::init();

di_get(ElasticsearchService::class)->reBuildIndex(null);
```

## releases

### release v0.0

* the first official release
* php ^8.0 support
* elasticsearch ^7.0 support
