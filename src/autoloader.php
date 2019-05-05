<?php
define('ROOT_PATH', realpath(__DIR__.'/..'));

require ROOT_PATH.'/vendor/autoload.php';
require ROOT_PATH.'/src/Psr4AutoloaderClass.php';

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNameSpace('MGWebGroup', ROOT_PATH.'/src');
// $loader->addNameSpace('MGWebGroup\PriceData', ROOT_PATH.'/src/PriceData');
// $loader->addNameSpace('MGWebGroup\Tests', ROOT_PATH.'/tests');
