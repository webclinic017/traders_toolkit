<?php
require __DIR__.'/autoloader.php';

use MGWebGroup\PriceData\TickerTape_Null;
use MGWebGroup\PriceData\OHLCVFromYahoo;
use MGWebGroup\PriceData\PriceHistory;
use MGWebGroup\PriceData\PriceDataException;
use MGWebGroup\General\GeneralException;
use MGWebGroup\General\CSVManager;

$symbol = 'A';

$tickerTape = new TickerTape_Null(); // this is a plug class for non-existent TickerTape func
$tickerTapeLocation = __DIR__.'/../data/source/ptv/null';

$OHLCVFromYahoo = new OHLCVFromYahoo();
$OHLCVLocation = __DIR__.'/../data/source/ohlcv/'.$symbol.'_d.csv';

// try { // see if file locations can be opened
// 	$ph = new PriceHistory($OHLCVFromYahoo, $OHLCVLocation, $tickerTape, $tickerTapeLocation, $symbol);
// 	// exit();
// 	$fromDate = new \DateTime('10 days ago');
// 	// $toDate = null;
// 	// $history = $ph->downloadOHLCV($unit = 'P1D', $fromDate, $toDate = null);
// 	// printHistory($history);

// 	// $ph->sortHistory($history, 'Timestamp', SORT_DESC);
// 	// echo 'Sorted:'.PHP_EOL;
// 	// printHistory($history);

// 	$history = [[123456,1,2,3,4,5], [123457,6,7,8,9,10]];
// 	$map = $ph->saveData($history, $ph->historyHeaders, $ph->OHLCVResource);

// 	var_dump($map); 


// } catch (PriceDataException $e) {
// 	var_dump($e->getMessage());
// 	// log message
// 	exit(1);
// }

try {

	$csv = new CsvManager($OHLCVLocation);
	// var_dump($csv->getMap()); exit();
	// $csv->seek(10);
	// var_dump($csv->current());

	$history = [[123456,1,2,3,4,5], [123457,6,7,8,9,10]];
	$headers = ['Timestamp', 'Open', 'High', 'Low', 'Close', 'Volume']; //Timestamp,Open,High,Low,Close,Volume

	$csv->importData($history, $headers);
	// $csv->seek(2);
	// var_dump($csv->current()); exit();

	$insertedArray = [123458,10,20,30,40,50];
	$csv[] = $insertedArray;

	var_dump($csv->getMap());
	$modifiedArray = [123459,100,200,300,400,500];
	$csv[0] = $modifiedArray;


} catch (GeneralException $e) {
	var_dump($e->getMessage());
	exit(1);
}

// $ph->setSymbol($symbol);

function printHistory ($history)
{
	echo PHP_EOL;
	echo sprintf('%20.20s %15.15s %15.15s %15.15s %15.15s %15.15s', 'Timestamp', 'Open', 'High', 'Low', 'Close', 'Volume').PHP_EOL;
	foreach ($history as $record) {
		echo sprintf('%20.20s %15.15s %15.15s %15.15s %15.15s %15.15s', date('Y-m-d H:i:s', $record['Timestamp']), $record['Open'], $record['High'], $record['Low'], $record['Close'], $record['Volume']).PHP_EOL;
	}
	echo PHP_EOL;
}




// namespace MGWebGroup;

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

