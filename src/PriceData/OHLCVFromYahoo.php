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

use MGWebGroup\PriceData\OHLCVProviderInterface;

class OHLCVFromYahoo implements OHLCVProviderInterface
{
	public function downloadHistory($symbol, $fromDate, $toDate, $unit)
	{
		$out = [
			array('Date' => '2019-05-01', 'Open' => 10.99, 'High' => 13, 'Low' => 10, 'Close' => 13, 'Volume' => 1000),
			array('Date' => '2019-05-02', 'Open' => 11.99, 'High' => 14, 'Low' => 10, 'Close' => 14, 'Volume' => 2000),
		];
		return $out;
	}

}