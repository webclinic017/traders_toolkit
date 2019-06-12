<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\PriceHistory;

/**
 * Price provider can be any style of price data: OHLCV, ticks, japanese hoopla, etc.
 */
interface PriceProviderInterface
{
	/**
	 * Downloads historical price information from a provider. Historical means prices
	 * from a given date and including last trading day before today. If today is a
	 * trading day, it will not be included. Use downloadQuote (for open trading hours),
	 * and downloadClosingPrice(for past trading hours).
	 * Downloaded history must be sorted from earliest date (the first element) to the
	 *  latest (the last element).
	 * @param App\Entity\Instrument $instrument
	 * @param DateTime $fromDate
	 * @param DateTime $toDate
	 * @param array $options (example: ['interval' => 'P1D'])
	 * @throws PriceHistoryException 
	 * @return array with price history compatible with chosen storage format (Doctrine Entities, csv records, etc.)
	 */
	public function downloadHistory($instrument, $fromDate, $toDate, $options);

	/**
	 * Will add new history to the stored history.
	 * All records in old history which start from the earliest date in $history will be deleted, with the new
	 *  records from $history written in.
	 * @param App\Entity\Instrument $instrument
	 * @param array $history with price history compatible with chosen storage format (Doctrine Entities, csv records, etc.)
	 */
 	public function addHistory($instrument, $history);
	
	/**
	  * Will export history from storage into file system
	  * @param array $history
	  * @param string $path
	  * @param array $options
	  */
 	public function exportHistory($history, $path, $options);
 
 	/**
 	 * Retrieves price history for an instrument from a storage
 	 * @param App\Entity\Instrument $instrument
 	 * @param DateTime $fromDate
 	 * @param DateTime $toDate
 	 * @param array $options (example: ['interval' => 'P1D'])
 	 * @return array with price history compatible with chosen storage format (Doctrine Entities, csv records, etc.)
 	 */
 	public function retrieveHistory($instrument, $fromDate, $toDate, $options);
 
 	/**
 	 * Quotes are downloaded when a market is open
 	 * @param App\Entity\Instrument $instrument
 	 * @return App\Entity\Quote when market is open or null if market is closed.
  	 */
 	public function downloadQuote($instrument);
 
 	/**
 	 * Saves given quote in storage. For any given instrument, only one quote supposed to be saved in storage.
 	 * If this function is called with existing quote already in storage, existing quote will be reomoved and
 	 * new one saved.
 	 * @param App\Entity\Instrument
 	 * @param App\Entity\Quote compatible with the implementation of this PriceInterface
 	 */
 	public function saveQuote($instrument, $quote);
 
 	/**
 	 * Adds a quote object to array of history. No gaps allowed, i.e. if quote date would skip at least one trading day in history,
 	 *   no addition will be performed.
 	 * @param App\Entity\Quote compatible with the implementation of this PriceInterface
 	 * @param array $history with elements compatible with chosen storage format (Doctrine Entities, csv records, etc.)
 	 *  OR null. If null then quote will be added directly to db history storage.
 	 *  If no history storage exists, nothing will be done.
 	 * @return modified $history | true (history is in storage) on success, null if nothing was added, false if gap was determined
 	 */
 	public function addQuoteToHistory($quote, $history);
 
 	/**
 	 * Retrieves quote from storage. Only one quote per instrument is supposed to be in storage. See saveQuote above
 	 * App\Entity\Instrument $instrument
 	 */
	public function retrieveQuote($instrument);

	/**
	 * Closing Prices are downloaded when market is closed and will return values for the closing price on last known trading day.
	 * This function is intended to be used same way as downloadQuote, EXCEPT it returns values when market is closed.
	 * @param App\Entity\Instrument $instrument
	 * @return App\Entity\Quote when market is closed or null if market is open.
	 */
	public function downloadClosingPrice($instrument);

	/**
	 * Retrieves latest closing price from price history
	 * @param App\Entity\Instrument
	 * @return $history item compatible with chosen storage format (Doctrine Entities, csv records, etc. I.e. App\Entity\OHLCVHistory)
	 */
	public function retrieveClosingPrice($instrument);

	/**
	 * @see addQuoteToHistory()
	 */
	public function addClosingPriceToHistory($closingPrice, $history);

}