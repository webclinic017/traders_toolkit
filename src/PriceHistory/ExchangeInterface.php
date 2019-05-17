<?php

namespace App\PriceHistory;

interface ExchangeInterface
{
	/**
	 * @param \DateTime $date
	 * @return bool
	 */
	public function isTradingDay($date);

	/**
	 * @param \DateTime $datetime
	 * @return bool
	 */
	public function isOpen($datetime);

	/**
	 * Queries Instruments table for which Exchange they belong to
	 * @return array
	 */
	public function getTradedInstruments();

	public function isTraded($instrument);

}
