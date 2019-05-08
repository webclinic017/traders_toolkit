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

use MGWebGroup\PriceData\AbstractOHLCV;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
use MGWebGroup\PriceData\PriceDataException;


class OHLCVFromYahoo extends AbstractOHLCV
{
	public function downloadHistory($symbol, $fromDate, $toDate, $unit)
	{
        $api = ApiClientFactory::createApiClient();

        switch ($unit) 
        {
        	case OHLCVProviderInterface::UNIT_DAILY:
        		$interval = ApiClient::INTERVAL_1_DAY;
        		break;
        	case OHLCVProviderInterface::UNIT_WEEKLY:
        		$interval = ApiClient::INTERVAL_1_WEEK;
        		break;
        	case OHLCVProviderInterface::UNIT_MONTHLY:
        		$interval = ApiClient::INTERVAL_1_MONTH;
        		break;
        	default:
        		throw new PriceDataException(sprintf('Unit of %s for OHLCV history is NOT supported in Yahoo provider.', $unit));
        }

        $result = $api->getHistoricalData($symbol, $interval, $fromDate, $toDate);

        return array_map(function($item) {
        	$close = ($item->getAdjClose() != $item->getClose())? $item->getAdjClose() : $item->getClose();
        	return [
        		self::COLUMN_0 => $item->getDate()->format('U'),
        		self::COLUMN_1 => round($item->getOpen(), 2),
        		self::COLUMN_2 => round($item->getHigh(), 2),
        		self::COLUMN_3  => round($item->getLow(), 2),
        		self::COLUMN_4 => round($close),
        		self::COLUMN_5 => $item->getVolume(),
        	];
        }, $result);

	}

}