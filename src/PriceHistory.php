<?php

/*
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MGWebGroup;

use MGWebGroup\OHLCVProvider;

/**
* Handles only historical price information without any instant or current quotes.
* Ticker tape is the basis of all price information, which documents actual deals made in the market place.
* OHLCV data is a widely used transformation of the ticker tape. A lot of online services provide this info 
*   instead of the ticker tape. However this class, in an effort to be comprehensive covers methods for the
*   OHLCV information. 
* @author Alex Kay <alex110504@gmail.com>
*/
class PriceHistory
{
	/**
	* For extra long files defines number of ticks to process 
	*/
	const TICK_CHUNK = 10000;

	/**
	* Supported price-time-volume data formats (PTV formats)
	*/
	const PTV_FORMAT_OHLCV = 1;
	//...

	/**
	* Resource which stores tick data relative to project root
	* @var resource handle
	*/
	protected $tickerTapeResource;

	/**
	* Resource which stores tick data relative to project root
	* @var resource handle
	*/
	protected $OHLCVResource;

	/**
	* @var TickerTapeProvider
	*/
	protected $tickerTapeProvider;

	/**
	* @var OHLCVProvider
	*/
	protected $OHLCVProvider;

	/**
	* Market commodity to get prices about
	* @var string
	*/
	public $symbol;


	/**
	* @param OHLCVProvider $OHLCVProvider object
	* @param string $OHLCVLocation path/with/filename
	* @param TickerTapeProvider $tickerTapeProvider object
	* @param string $tickerTapeLocation path/with/filename

	*/	
	public function __construct(OHLCVProvider $OHLCVProvider, $OHLCVLocation, TickerTapeProvider $tickerTapeProvider, $tickerTapeLocation)
	{
		$this->OHLCVProvider = $OHLCVProvider;
		$this->tickerTapeProvider = $tickerTapeProvider;

		$this->OHLCVResource = fopen($OHLCVLocation, 'r');
		$this->tickerTapeResource = fopen($tickerTapeLocation, 'r');
	}

	/**
	* @param string $symbol
	* @param DateTime $fromDate
	* @param DateTime | null $toDate
	* @return array(<timestamp> => [price, size])?? To be established when TickerTapeProvider is working.
	*/
	public function downloadTickerTape($fromDate, $toDate=null) {
		if (empty($this->symbol)) throw new \Exception('No symbol defined for downloading of the ticker tape.');

		if (!($toDate instanceof \DateTime)) $toDate = new \DateTime();

		return $this->tickerTapeProvider->download($this->symbol, $fromDate, $toDate);
	}

	/**
	* Saves array of the ticker tape values to the $tickerTapeResource
	* @param array $tickerTape
	* @return true | false on success | failure
	*/
	public function saveTickerTape($tickerTape) {
		foreach ($tickerTape as $record) {
			// save values into the $tickerTapeResource
			//...
		}
	}

	/**
	* Loads ticker tape data from the resource into array. Should use TICK_CHUNK
	* @return array empty or with values
	*/
	public function loadTickerTape() {
		// read from the $tickerTapeResource
		//...
		return [];
	}

	/**
	* Transforms price-time data into OHLCV or other format.
	* @param array $tickerTape
	* @param OHLCVProvider UNIT_* constant $unit
	* @param integer self::PTV_FORMAT_* constant
	* @return array
	*/
	public function transformTickerTape($tickerTape, $unit = OHLCVProvider::UNIT_DAILY, $PTVFormat) {
		//...
		return [];
	}

	/**
	* Many online services just provide OHLCV format. This method is included to only download OHLCV information.
	* @param OHLCVProvider UNIT_* constant $unit
	* @param DateTime $fromDate
	* @param DateTime $toDate | null
	* @return array 
	*/
	public function downloadOHLCV($unit, $fromDate, $toDate=null)
	{
		return [];
	}


}