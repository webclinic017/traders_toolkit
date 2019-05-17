<?php

interface PriceProviderInterface
{
	public function downloadHistory($instrument, $fromDate, $toDate, $options);

 	public function saveHistory($history);
 
 	public function mergeHistory($instrument, $history);
 
 	public function retrieveHistory($instrument, $fromDate, $toDate);
 
 	/**
 	 * Quotes are downloaded when a market is open
 	 */
 	public function downloadQuote($instrument);
 
 	public function saveQuote($quote);
 
 	public function addQuoteToHistory($quote, $history);
 
	public function retrieveQuote($instrument);

	/**
	 * Closing prices are downloaded when market is closed
	 * They are different from history as Closing Price is 
	 *   only for one day.
	 */
	public function downloadClosingPrice($instrument, $date);

}