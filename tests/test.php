<?php declare(strict_types=1);

namespace eArc\DataElasticsearchTests;

use BootstrapEArcData;
use DateTime;
use eArc\Data\Entity\AbstractEntity;
use eArc\Data\ParameterInterface;
use eArc\DataElasticsearch\ElasticsearchDataBridge;

include(__DIR__.'/../vendor/autoload.php');
include(__DIR__.'/../vendor/earc/data/bootstrap/BootstrapEArcData.php');

class test extends AbstractEntity
{
    protected string $bluebird = 'blue';
    private $date;

    public function __construct()
    {
        $this->date = new DateTime();
        $this->primaryKey = 'alpha';
    }
}

BootstrapEArcData::init();

di_set_param(\eArc\DataElasticsearch\ParameterInterface::ELASTICA_CLIENT_CONFIG, [
    'host' => 'localhost',
    'port' => 32771,
]);
di_tag(ParameterInterface::TAG_ON_PERSIST, ElasticsearchDataBridge::class);
di_tag(ParameterInterface::TAG_ON_FIND, ElasticsearchDataBridge::class);

$test = new test();

data_persist($test);

    dump(data_find(test::class, ['bluebird' => ['green', 'blue', 'violet'], 'primaryKey' => 'alpha', 'date' => '2021-03-29T22:15']));
