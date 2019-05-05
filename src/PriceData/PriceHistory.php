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
use MGWebGroup\PriceData\PriceDataException;

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
	private $symbol;


	/**
	* @param OHLCVProvider $OHLCVProvider object
	* @param string $OHLCVLocation path/with/filename
	* @param TickerTapeProvider $tickerTapeProvider object
	* @param string $tickerTapeLocation path/with/filename
	* @param string $symbol
	*/	
	public function __construct(OHLCVProviderInterface $OHLCVProvider, $OHLCVLocation, TickerTapeProviderInterface $tickerTapeProvider, $tickerTapeLocation, $symbol=null)
	{
		$this->OHLCVProvider = $OHLCVProvider;
		$this->tickerTapeProvider = $tickerTapeProvider;

		if ((!$this->OHLCVResource = @fopen($OHLCVLocation, 'r')) || !is_file($OHLCVLocation)) throw new PriceDataException(sprintf('Failed to open OHLCV Resource at %s', $OHLCVLocation));
		if ((!$this->tickerTapeResource = @fopen($tickerTapeLocation, 'r')) || !is_file($tickerTapeLocation)) throw new PriceDataException(sprintf('Failed to open Ticker Tape Resource at %s', $tickerTapeLocation));

		$this->setSymbol($symbol);
	}

	/**
	* @param string $symbol
	* @param DateTime $fromDate
	* @param DateTime | null $toDate
	* @return array(<timestamp> => [price, size])?? To be established when TickerTapeProvider is working.
	*/
	public function downloadTickerTape($fromDate, $toDate=null) 
	{
		if (!($toDate instanceof \DateTime)) $toDate = new \DateTime();

		return $this->tickerTapeProvider->downloadTape($this->symbol, $fromDate, $toDate);
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

		return false;
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
	* @param string which matches one of the OHLCVProviderInterface UNIT_* constants
	* @param DateTime $fromDate
	* @param DateTime $toDate | null
	* @return array 
	*/
	public function downloadOHLCV($unit, $fromDate, $toDate=null, $sortOrder=null)
	{
		if (!($toDate instanceof \DateTime)) $toDate = new \DateTime();

		$result = $this->OHLCVProvider->downloadHistory($this->symbol, $fromDate, $toDate, $unit);

		return $result;
	}

	/**
	* Performs sorting of the OHLCV history by Date
	* @param array $history
	* @param integer $sortOrder SORT_ASC or SORT_DESC native PHP constants
	* @return void. Works on the $history array by reference
	*/
	public function sortHistory(&$history, $sortOrder)
	{
		$date = array_column($history, 'Date');

		array_multisort($date, $sortOrder, SORT_REGULAR, $history);
	}

	public function setSymbol($symbol)
	{
		if (preg_match('/\./', $symbol) == 1) throw new PriceDataException(sprintf('Supplied symbol %s cannot contain dots', $symbol));
		if (!$symbol) throw new PriceDataException('Trying to set symbol for the Price History, but no symbol provided');

		$this->symbol = $symbol;
	}

	public function getSymbol()
	{
		return $this->symbol;
	}
}