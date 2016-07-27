<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotesFromYahoo;

class GetQuotesFromYahooTest extends TestCase
{
    protected $quotes;

    protected function setUp() 
    {
        $this->quotes = new GetQuotesFromYahoo;

    }

    public function testCheckTimeZoneOfCurrentDateTimeObject()
    {
        $expect = 'America/New_York';
        $obj = $this->quotes->createCurrentDateTimeObject();
        $actual = $obj->getTimezone()->getName();
        $this->assertSame($expect, $actual);
    }

    public function testQuoteFileExists()
    {
        
    }

}