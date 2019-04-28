<?php
require 'library/autoloader.php';

use SgCsv\CsvMappedReader;
use MGWebGroup\GetQuotes;

$pathToYUniverse = realpath('data/source/ohlcv/y_universe.src');
$timeZone = 'America/Phoenix';

// echo __DIR__.PHP_EOL;
// echo realpath();
echo $pathToYUniverse.PHP_EOL;

$csv = new CsvMappedReader($pathToYUniverse);

echo sprintf('%s Starting quotes download cron...', date('[Y:m:d H:i:s.v]')).PHP_EOL;
foreach ($csv as $record) {
	// var_dump($record);
	echo sprintf('%s Downloading quotes for %s', date('[Y:m:d H:i:s.v]'), $record['Symbol']);
	$quotes = new GetQuotes($record['Symbol'], $timeZone);
	$quotes->updateQuotes();
	echo ' complete';
	echo PHP_EOL;
	sleep(rand(1,5));
}
echo sprintf('%s quotes download cron finished.', date('[Y:m:d H:i:s.v]')).PHP_EOL;