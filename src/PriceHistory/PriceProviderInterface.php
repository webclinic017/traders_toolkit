<?php

interface PriceProviderInterface
{
	public function downloadHistory($instrument, $fromDate, $toDate, $options);

 	public function saveHistory($history);
 
 	public function mergeHistory($instrument, $history);
 
 	public function retrieveHistory($instrument, $fromDate, $toDate);
 
 	public function downloadQuote($instrument);
 
 	public function saveQuote($quote);
 
 	public function addQuoteToHistory($quote, $history);
 
	public function retrieveQuote($instrument);

}