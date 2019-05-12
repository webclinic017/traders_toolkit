<?php

namespace App\PriceHistory;

// use Yasumi\Yasumi;
use Yasumi\Filters\OfficialHolidaysFilter;
use App\Service\Holidays;
use App\PriceHistory\ExchangeInterface;

class Exchange_NYSE implements ExchangeInterface
{
	private $holidaysProvider;

	private $holidays;

	public function __construct(Holidays $holidays)
	{
		$this->holidaysProvider = $holidays->holidaysProvider;
		$this->holidays = new OfficialHolidaysFilter($holidays->holidaysProvider->getIterator());
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
