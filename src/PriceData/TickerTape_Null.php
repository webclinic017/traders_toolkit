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

use MGWebGroup\PriceData\TickerTapeProviderInterface;
use MGWebGroup\PriceData\PriceDataException;


class TickerTape_Null implements TickerTapeProviderInterface
{
	public function downloadTape($symbol, $fromDate, $toDate) {
		// $out = [
		// 	array('Timestamp' => time(), 'Price' => 10.99, 'Size' => 100),
		// 	array('Timestamp' => time(), 'Price' => 11.99, 'Size' => 200),
		// 	array('Timestamp' => time(), 'Price' => 12.99, 'Size' => 300),
		// ];

		throw new PriceDataException('No Ticker Tape Download functionality is defined at this time.');

		return $out;
	}

}