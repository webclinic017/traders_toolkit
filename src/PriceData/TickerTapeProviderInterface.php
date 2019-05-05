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

interface TickerTapeProviderInterface
{
	/**
	* Downloads Price-Time-Volume data from a provider. This data is also known as Ticker Tape
	* @param string $symbol
	* @param DateTime $fromDate
	* @param DateTime $toDate
	* @return array ( 0 => array('Timestamp' => integer, 'Price' => float, 'Size' => integer), 1 => ... )
	*/
	public function downloadTape($symbol, $fromDate, $toDate);

}