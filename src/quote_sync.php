<?php

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
