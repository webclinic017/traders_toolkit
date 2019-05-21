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
	 * @param App\Entity\Instrument $instrument
	 * @param DateTime $fromDate
	 * @param DateTime $toDate
	 * @param array $options (example: ['interval' => 'P1D'])
	 * @return array with price history compatible with chosen storage format (Doctrine Entities, csv records, etc.)
	 */
	public function downloadHistory($instrument, $fromDate, $toDate, $options);

 	public function addHistory($instrument, $history);
 
 	public function exportHistory($history, $path);
 
 	public function retrieveHistory($instrument, $fromDate, $toDate);
 
 	/**
 	 * Quotes are downloaded when a market is open
 	 * @param App\Entity\Instrument $instrument
 	 * @return App\Entity\Quote when market is open or null if market is closed.
  	 */
 	public function downloadQuote($instrument);
 
 	public function saveQuote($quote);
 
 	public function addQuoteToHistory($quote, $history);
 
	public function retrieveQuote($instrument);

	/**
	 * Closing Prices are downloaded when market is closed and will return values
	 * for the closing price on last known trading day.
	 * @param App\Entity\Instrument $instrument
	 * @return App\Entity\History when market is closed or null if market is open.
	 */
	public function downloadClosingPrice($instrument);

}