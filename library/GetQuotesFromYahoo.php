<?php
/**
 * @author Alex Kay alex110504@gmail.com
 * @copyright 2016 MGWebGroup
 * @uses Scheb\YahooFinanceApi\ApiClient
 */

namespace MGWebGroup;

class GetQuotesFromYahoo 
{
    protected $queryDate;
    protected $storedOHLCVDate;
    protected $timeZone;
    protected $symbol;

    public function __construct($symbol) 
    {
        $this->timeZone = new \DateTimeZone('America/New_York');
        $this->symbol = $symbol;
    }

    public function getTimeObjectOfStoredOHLCV()
    {

    }

    public function createCurrentDateTimeObject()
    {
        return new \DateTime(null, $this->timeZone);
    }



}
