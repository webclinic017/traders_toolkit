<?php
require 'library/Psr4AutoloaderClass.php';
$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNamespace('Scheb\YahooFinanceApi', 'library/scheb/yahoo-finance-api/src');
$loader->addNamespace('Scheb\YahooFinanceApi\ApiClientFactory', 'library/scheb/yahoo-finance-api/src');
// $loader->addNamespace('GuzzleHttp', 'library/scheb/yahoo-finance-api/vendor/guzzlehttp/guzzle/src');
$loader->addNameSpace('Yasumi','library/yasumi/src/Yasumi');
$loader->addNamespace('Yasumi\tests','library/yasumi/tests');
$loader->addNameSpace('Faker','library/Faker/src/Faker');
$loader->addNameSpace('SgCsv','library/csv/src/SgCsv');
$loader->addNameSpace('MGWebGroup','library/');
$loader->addNameSpace('MGWebGroup\tests','tests/');

require 'library/scheb/yahoo-finance-api/vendor/autoload.php';