<?php

namespace App\PriceHistory;

interface ExchangeInterface
{
	/**
	 * @param \DateTime $date
	 * @return bool
	 */
	public function isTradingDay($date);

	public function isOpen($DateTime);

	public function getTradedInstruments();

	public function isTraded($instrument);

}
