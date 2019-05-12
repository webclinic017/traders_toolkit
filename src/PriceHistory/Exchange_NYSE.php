<?php

namespace App\PriceHistory;

use Yasumi\Yasumi;
use Yasumi\Filters\OfficialHolidaysFilter;
use App\PriceHistory\ExchangeInterface;

class Exchange_NYSE implements ExchangeInterface
{
	private $holidaysProvider;

	// private $holidays;

	public function __construct()
	{
		$this->holidaysProvider = Yasumi::create('USA', 2019);

		// $this->holidays = new OfficialHolidaysFilter($holidaysProvider->getIterator());
	}

	public function isTradingDay($date) 
	{
		return $this->holidaysProvider->isWorkingDay($date);
	}

	public function isOpen($DateTime)
	{

	}

	public function getTradedInstruments()
	{

	}

	public function isTraded($instrument) 
	{

	}
}
