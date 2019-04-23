<?php
/**
 * This file is part of the 121021_traders_toolkit package
 * 
 * @author Alex Kay <alex110504@gmail.com>
 * @copyright 2016 MGWebGroup
 */

namespace MGWebGroup\tests;

use SgCsv\CsvMappedReader;

/**
 * Contains service functions shared between all tests
 */
trait ServiceFunctions
{
    /**
     * Outputs $message=<...>: <formatted_date>
     */
    public function printDateExplanation($message, $dateTime)
    {
        fwrite(STDOUT, $message . ": " . $dateTime->format(\DateTime::RFC1036));
    }

    /**
     * Picks a random stock symbol from Y_Universe
     */
    protected function pickASymbol()
    {
        $csv = new CsvMappedReader(self::PATH_TO_Y_UNIVERSE);
        $randomLine = rand(0,$csv->count());
        $csv->seek($randomLine);
        $record = $csv->current();
        return $record['Symbol'];

    }

}