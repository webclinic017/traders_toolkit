<?php

namespace App\Service;

use Yasumi\Yasumi;
use Yasumi\Filters\OfficialHolidaysFilter;

class Holidays
{
	public $holidaysProvider;

	/**
	 * Makes all of methods in Yasumi available
	 */
	public function __construct()
	{
		$currentYear = new \DateTime();
		$this->holidaysProvider = Yasumi::create('USA', $currentYear->format('Y'));
		// return Yasumi::create('USA', 2019);
	}
}
