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
	public $tickerTapeResource;

	/**
	* Resource which stores tick data relative to project root
	* @var resource handle
	*/
	public $OHLCVResource;

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
	* History headers
	* @var array
	*/
	public $historyHeaders;


	/**
	* Constructor will set historyHeaders to the default ones provided in the OHLCVProvider. You can change them later if you need to, via simple assignment to the 
	*  public prop $historyHeaders.
	* @param OHLCVProvider $OHLCVProvider object
	* @param string $OHLCVLocation path/with/filename
	* @param TickerTapeProvider $tickerTapeProvider object
	* @param string $tickerTapeLocation path/with/filename
	* @param string $symbol
	*/	
	public function __construct(OHLCVProviderInterface $OHLCVProvider, $OHLCVLocation, TickerTapeProviderInterface $tickerTapeProvider, $tickerTapeLocation, $symbol=null)
	{
		$this->OHLCVProvider = $OHLCVProvider;
		$this->historyHeaders = [
			$OHLCVProvider::COLUMN_0, 
			$OHLCVProvider::COLUMN_1, 
			$OHLCVProvider::COLUMN_2, 
			$OHLCVProvider::COLUMN_3,
			$OHLCVProvider::COLUMN_4,
			$OHLCVProvider::COLUMN_5
		];
		$this->OHLCVResource = $this->createResourceFromLocation($OHLCVLocation);

		$this->tickerTapeProvider = $tickerTapeProvider;
		$this->tickerTapeResource = $this->createResourceFromLocation($tickerTapeLocation);

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
	* @param string $sortColumn
	* @param integer $sortOrder SORT_ASC or SORT_DESC native PHP constants
	* @return void. Works on the $history array by reference
	*/
	public function sortHistory(&$history, $sortColumn, $sortOrder=SORT_ASC)
	{
		$column = array_column($history, $sortColumn);

		array_multisort($column, $sortOrder, SORT_REGULAR, $history);
	}

	/**
	* Saves data with given headers as a csv file. Will truncate existing file, then write
	* @param array $data 
	* @param array $headers
	* @return array map of file (<line_number> => <cumulative_length>, ...)
	*/
	public function saveData($data, $headers = [], $handle)
	{
		ftruncate ($handle , 0);
		$map = [];
		$cumulativeLength = 0;

		if (!empty($headers)) {
			$cumulativeLength += fwrite($handle, implode(',', $headers).PHP_EOL);
			$map[] = $cumulativeLength;
		}

		foreach ($data as $fields) {
			$cumulativeLength += fwrite($handle, implode(',', $fields).PHP_EOL);
			$map[] = $cumulativeLength;
		}

		return $map;
	}

	/**
	* Appends OHLCV history to $OHLCVResource. 
	* @param array $history OHLCV history by reference
	* @return boolean true on success, false on failure
	*/
	public function appendData($data, $handle)
	{
	}

	/**
	* Given a record number, updates line in the history file with new line
	* @param integer $recordNumber
	* @param array $data
	*/
	public function updateData($recordNumber, $data, $handle) 
	{

	}

	public function deleteData($recordNumber, $handle)
	{

	}

	public function insertData($insertAfterRecordNumber, $data, $handle)
	{

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

	/**
	* @param string Fully Qualified Name (FQN) as in path/to/resource/file.ext
	* @return resource handle
	*/
	private function createResourceFromLocation($FQN)
	{
		if (!is_dir($dirName = pathinfo($FQN, PATHINFO_DIRNAME))) throw new PriceDataException(sprintf('`%s` is not a directory', $dirName));

		// Open the file for reading and writing. If the file does not exist, it is created. If it exists, it is neither truncated, nor the call to this function fails. The file pointer is positioned on the beginning of the file.
		if (!$resource = @fopen($FQN, 'c+')) throw new PriceDataException(sprintf('Failed to open resource at %s', $FQN));

		return $resource;
	}
}