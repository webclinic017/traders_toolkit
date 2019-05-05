<?php
define('ROOT_PATH', realpath(__DIR__.'/..'));

require ROOT_PATH.'/vendor/autoload.php';
require ROOT_PATH.'/src/Psr4AutoloaderClass.php';

$loader = new Psr4AutoloaderClass;
$loader->register();
$loader->addNameSpace('MGWebGroup\PriceData','src/PriceData');
$loader->addNameSpace('MGWebGroup\Tests','tests/');
