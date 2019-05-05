<?php

namespace MGWebGroup;

define('ROOT_PATH', realpath(__DIR__.'/..'));

require ROOT_PATH.'/vendor/autoload.php';


/**

use Yasumi\Yasumi;
use Yasumi\Filters\OfficialHolidaysFilter;


$holidays = Yasumi::create('USA', 2019);
$holidays->addHoliday($holidays->goodFriday($holidays->getYear(), $timezone = 'America/New_York', $locale = 'en_US'));

$officialHolidays = new OfficialHolidaysFilter($holidays->getIterator());
// var_dump($officialHolidays);
foreach ($officialHolidays as $holiday) {
	echo sprintf('%s: %s', $holiday->getName(), $holiday->format('Y-m-d')).PHP_EOL;
}

*/

// require ROOT_PATH.'/library/csv/src/SgCsv/FileReader.php';
// require ROOT_PATH.'/library/csv/src/SgCsv/CsvReader.php';
// require ROOT_PATH.'/library/csv/src/SgCsv/CsvMappedReader.php';

// use SgCsv\CsvMappedReader;

// $reader = new CsvMappedReader($filename = ROOT_PATH.'/data/source/ohlcv/A_d.csv', $fileMode = 'r' );

// $reader->buildSeekMap(10);

// $reader->seek(13);
// var_dump($reader->current()); exit();

// // var_dump($reader->getSeekMap()); exit();


require ROOT_PATH.'/src/PriceHistoryProvider.php';
require ROOT_PATH.'/src/PriceHistory.php';

use MGWebGroup\PriceHistoryProvider;
use MGWebGroup\PriceHistory;

class OHLCVProvider 
{}

class TickerTapeProvider
{}

$provider = new OHLCVProvider;
$fromDate = new \DateTime();
$toDate = null;

$priceHistory = new PriceHistory();

echo $priceHistory->downloadOHLCV($fromDate, $toDate, $provider);
$priceHistory->downloadTickerTape($fromDate, $toDate, new TickerTapeProvider);