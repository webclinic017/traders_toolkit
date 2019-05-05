<?php

/*
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MGWebGroup\PriceData;

interface OHLCVProviderInterface
{
	/**
	* Units for the Open, High, Low, Close, Volume (OHLCV) data. 
	* These follow interval_spec for the \DateInterval class
	*/
	const UNIT_1MIN = 'PT1M';
	const UNIT_2MIN = 'PT2M';
	const UNIT_3MIN = 'PT3M';
	const UNIT_5MIN = 'PT3M';
	const UNIT_6MIN = 'PT6M';
	const UNIT_10MIN = 'PT10M';
	const UNIT_15MIN = 'PT15M';
	const UNIT_30MIN = 'PT30M';
	const UNIT_60MIN = 'PT60M';
	const UNIT_120MIN = 'PT120M';
	const UNIT_DAILY = 'P1D';
	const UNIT_WEEKLY = 'P1W';
	const UNIT_MONTHLY = 'P1M';
	const UNIT_YEARLY = 'P1Y';

	/**
	* Downloads OHLCV data from a provider.
	* @param string $symbol
	* @param DateTime $fromDate
	* @param DateTime $toDate
	* @param string $unit one of the UNIT_* constants of this interface
	* @return array ( 0 => array('Date' => timestamp, 'Open' => float, 'High' => float, 'Low' => float, 'Close' => float, 'Volume' => null | float), 1 => ... )
	*/
	public function downloadHistory($symbol, $fromDate, $toDate, $unit);

}