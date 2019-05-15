<?php

namespace App\PriceHistory;

use Yasumi\Yasumi;
use Yasumi\Filters\OfficialHolidaysFilter;
// use App\Service\Holidays;
use App\PriceHistory\ExchangeInterface;


class Exchange_NYSE implements ExchangeInterface
{
	/**
	 * @var Yasumi holidays object
	 */
	private $holidaysProvider;

	private $timeZone = 'America/New_York';

	/**
	 * @var ArrayIterator iterator for the holidays to be used in foreach
	 */
	// public $holidays;


	public function __construct()
	{
		$date = new \DateTime();
		// $this->initYear = (int)$date->format('Y');
		
		$this->holidaysProvider = Yasumi::create('USA', (int)$date->format('Y'));
		
		$this->matchHolidays((int)$date->format('Y'));

		// $this->holidays = new OfficialHolidaysFilter($holidays->holidaysProvider->getIterator());
	}

	public function isTradingDay($date) 
	{
		if ( $this->holidaysProvider->getYear() != (int)$date->format('Y')) {
			// save current year
			$initYear = $this->holidaysProvider->getYear();
			// instaniate Yasumi for a different year
			$this->holidaysProvider = Yasumi::create('USA', $date->format('Y'));
			$this->matchHolidays((int)$date->format('Y'));
			$out = $this->holidaysProvider->isWorkingDay($date);
			// revert back
			$this->holidaysProvider = Yasumi::create('USA', $initYear);
		} else {
			$out = $this->holidaysProvider->isWorkingDay($date);
		}
		return $out;
	}

	/**
	 * In this func, the trick to get seconds offset from midnight is to use this formula:
	 * $datetime->format('U') % 86400 + $secondsOffsetFromUTC = $datetime->format('U') % 86400 + $datetime->format('Z')
	 * @param DateTime $datetime
	 * @return bool 
	 */
	public function isOpen($datetime)
	{
		$secondsOffsetFromUTC = $datetime->format('Z');
		// $interval = new DateInterval(sprintf('PT%dS', $secondsOffsetFromUTC));
		// $convertSecondsToSwatch = 999/(24*3600);
		// foreach ($this->holidays as $key => $date) {
		// 	echo sprintf('%s %s', $key, $date->format('c')).PHP_EOL;
		// }
		// exit();

		// check for holidays and weekends
		if (!$this->isTradingDay($datetime)) { 
			return false; 
		} 
		// check for post trading hours
		elseif ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 16*3600 || $datetime->format('U') % 86400 + $secondsOffsetFromUTC < 9.5*3600 ) {
			return false;
		}

		// check for July 3rd: If July 4th occurs on a weekday and is not a substitute, prior trading day is open till 1300
		if ('07-03' == $datetime->format('m-d') && in_array($datetime->format('w'), [1,2,3,4])) {
			// var_dump($datetime->format('c'), $datetime->format('B'));
			return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;
		}

		// check for post-Thanksgiving Friday: market is open till 1300 on this day
		$thanksGiving = new \DateTime('last Thursday of November this year');
		$thanksGiving->modify('next day');
		if ('11' == $datetime->format('m') && $thanksGiving->format('d') == $datetime->format('d')) {
			return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;
		}
		
		// check for pre-Christmas day 24-Dec: If Christmas occurs on a weekday from Tuesday, prior trading day is open till 1300
		if ('12-24' == $datetime->format('m-d') && in_array($datetime->format('w'), [1,2,3,4])) {
			return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 13*3600)? false : true;	
		}

		// check for regular trading hours
		return ($datetime->format('U') % 86400 + $secondsOffsetFromUTC > 9.5*3600 && $datetime->format('U') % 86400 + $secondsOffsetFromUTC < 16*3600 )? true : false;

	}

	public function getTradedInstruments()
	{

	}

	public function isTraded($instrument) 
	{

	}

	private function matchHolidays($year)
	{
		$this->holidaysProvider->addHoliday($this->holidaysProvider->goodFriday($year, $this->timeZone, 'en_US'));
		
		// remove columbusDay
		$this->holidaysProvider->removeHoliday('columbusDay');

		// remove veteransDay
		$this->holidaysProvider->removeHoliday('veteransDay');
	}
}
