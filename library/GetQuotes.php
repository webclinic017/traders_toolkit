<?php
/**
 * This file is part of the 121021_traders_toolkit package
 * 
 * @author Alex Kay <alex110504@gmail.com>
 * @copyright 2016 MGWebGroup
 * @uses Scheb\YahooFinanceApi\ApiClient (https://github.com/scheb/yahoo-finance-api.git)
 * @uses Yasumi (https://github.com/azuyalabs/yasumi.git)
 * @uses csv\SgCsv (https://github.com/sgmarketplace/csv.git)
 */

namespace MGWebGroup;

use DateTime, DateTimeZone, DateInterval;
use Exception;
use Yasumi\Yasumi, Yasumi\Holiday, Yasumi\Filters\BankHolidaysFilter;
use SgCsv\CsvMappedReader;
use Scheb\YahooFinanceApi\ApiClient;

/**
 * Updates the quotes (OHLCV) file.
 */
class GetQuotes
{
    /**
     * Starting date for which all new quote files must be downloaded
     * NOTE: Quotes that start more than 1 year ago may not be returned by Yahoo API. The Scheb's api will throw ApiException "Yahoo Finance API did not return a result."
     */
    const STARTDATE = '07/01/2015';
    /**
     * Path to the quotes files
     */
    const PATH_TO_OHLCV_FILES = 'data/source/ohlcv';
    /**
     * @var DateTimeZone object that stores file system's time zone
     */
    private $timeZone;
    /**
     * @var DateTime date of the last record in the quotes file
     */
    protected $lastDateInOHLCV;
    /**
     * @var DateTime date of the first record in the quotes file
     */
    protected $firstDateInOHLCV;    
    /**
     * @var DateTime date and time of the last update of the quotes file in NYC time zone (uses file modification time) 
     */
    protected $modTimeOfOHLCV;
    /**
     * @var DateTime date and time query for quotes was formulated to the quotes provider
     */    
    private $queryDateTime;
    /**
     * @var DateTime previous trading day (T-1)
     */    
    protected $prevT;
    /**
     * @var string path to the OHLCV file including file name
     */    
    protected $pathToOHLCVFile;
    /**
     * @var string stock symbol we are working with
     */        
    protected $symbol;
    /**
     * @var NYSE holidays object stores all NYSE holidays
     */        
    protected $NYSEHolidays;

    /**
     * Initializes data fields of the class but does not download quotes.
     *
     * @param string $symbol stock symbol we are working with.
     * @param DateTimeZone $timeZone in which file system runs.
     */        
    public function __construct($symbol, $timeZone) 
    {
        $this->symbol = $symbol;
        $this->timeZone = $timeZone;
        $this->pathToOHLCVFile = self::PATH_TO_OHLCV_FILES . '/' . $this->createNameOfOHLCVFile($symbol);

    }

    /**
     * Makes sure the OHLCV file exists and is up to date with the latest quote
     * @param string provider where the quotes are downloaded from
     * @return array $currentQuote
     */
    public function updateQuotes($provider = 'yahoo')
    {
        $this->getHolidays();
        $this->queryDateTime = $this->createDate('now');
        $this->prevT = $this->calcPrevT($this->queryDateTime);
        $timeZoneNYC = new DateTimeZone('America/New_York');

        $currentQuote = null;

        if (!file_exists($this->pathToOHLCVFile)) {
            $this->firstDateInOHLCV = $this->createDate('start');
            
            $OHLCVArray = $this->downloadOHLCV($this->firstDateInOHLCV, $this->prevT, $provider);
            $this->OHLCVArrayToFile($OHLCVArray, $this->pathToOHLCVFile, $provider);

            // $this->lastDateInOHLCV = $this->prevT;
            // $this->modTimeOfOHLCV = $this->createDate('modtime');
 
        } else {
            $patchStartDate = $this->createDate('last');
            $patchOHLCV = $this->downloadOHLCV($patchStartDate, $this->queryDateTime, $provider);
            // print_r($patchOHLCV); exit();
            $existOHLCV = $this->readOHLCV($this->pathToOHLCVFile, $provider);
            unset($existOHLCV['query']['results']['quote'][0]);
            // print_r($existOHLCV); exit();
            $patchOHLCV['query']['results']['quote'] = array_merge($patchOHLCV['query']['results']['quote'], $existOHLCV['query']['results']['quote']);
            // print_r($patchOHLCV); exit();
            $OHLCVArray = $patchOHLCV;
            $this->OHLCVArrayToFile($OHLCVArray, $this->pathToOHLCVFile, $provider);

            // $this->lastDateInOHLCV = new DateTime($patchOHLCV['query']['results']['quote'][0]['Date'], $timeZoneNYC);
            // $this->modTimeOfOHLCV = $this->createDate('modtime');
        }

        if ($this->NYSEHolidays->isWorkingDay($this->queryDateTime) && ($this->queryDateTime->format('G') * 3600 + $this->queryDateTime->format('i') * 60) > 34200 ) {
            $currentQuote = $this->getCurrentQuote($provider);

            switch ($provider) {
                case 'yahoo':
                    array_unshift($OHLCVArray['query']['results']['quote'], $currentQuote);
                    $this->lastDateInOHLCV = new DateTime($currentQuote['Date'], $timeZoneNYC);
                    break;
            } 

            $this->OHLCVArrayToFile($OHLCVArray, $this->pathToOHLCVFile, $provider);
            // $this->modTimeOfOHLCV = $this->createDate('modtime');

        }

        return $currentQuote;

    }

    /**
     * Creates name for the quote file that will be looked up on disk according the convention described for $nameOfOHLCVFile
     */
    public static function createNameOfOHLCVFile($symbol)
    {
        return sprintf('%s_d.csv',$symbol);
    }

    /**
     * @return array that contains holiday names as keys and holiday dates as values
     */
    public function getHolidays()
    {
        $this->NYSEHolidays = Yasumi::create('NYSE', $this->createDate('now')->format('Y'));
        $holidays = new BankHolidaysFilter($this->NYSEHolidays->getIterator());
        return $holidays->getArrayCopy();
    }

    /**
     * Figures out the previous trading day, prior to the given date
     * @uses $this->getHolidays()
     * @param DateTime object for which to calculate the previous trading day (T-1)
     * @return DateTime first previous trading day (T-1) from current date 
     */
    public function calcPrevT($dateTime)
    {
        $prevT = clone $dateTime;
        $this->getHolidays();
        $dayInterval = new DateInterval('P1D');
        do {
            $prevT->sub($dayInterval);
        } while (!$this->NYSEHolidays->isWorkingDay($prevT));
        return $prevT;
    }

    /**
     * Accesses private DateTime objects
     * @param string $kind = "[query|start|prevt]"
     * @return DateTime object that contains Date, Time and Timezone 
     */
    public function getDate($kind)
    {
        switch ($kind) {
            case 'query':
                $obj = $this->queryDateTime;
                break;
            case 'start':
                $obj = $this->firstDateInOHLCV;
                break;
            case 'prevt':
                $obj = $this->prevT;
                break;
            case 'modtime':
                $obj = $this->modTimeOfOHLCV;
                break;
            case 'last':
                $obj = $this->lastDateInOHLCV;
                break;
        }
        return $obj;
    }
    public function createDate($kind)
    {
        $out = null;
        $timeZoneNYC = new DateTimeZone('America/New_York');
        switch ($kind) {
            case 'start':
                $out = new DateTime(self::STARTDATE, $timeZoneNYC);
                break;
            case 'now':
                $out = new DateTime(null, $timeZoneNYC);
                break;
            case 'modtime':
                if (file_exists($this->pathToOHLCVFile)) {
                    $out = new DateTime(null, $this->timeZone);
                    $out->setTimeStamp(filemtime($this->pathToOHLCVFile));
                    $out->setTimeZone($timeZoneNYC);
                }
                break;
            case 'last':
                if (file_exists($this->pathToOHLCVFile)) {
                    $csv = new CsvMappedReader($this->pathToOHLCVFile);
                    $csv->rewind();
                    $line = $csv->current();
                    $out = new DateTime($line['Date'], $timeZoneNYC);
                }
        }
        return $out;
    }

    /**
     * Downloads current quote from a quote provider. A quote is different than the OHLCV data.
     * @param string $provider
     * @return $out = array('Date' => '<Y-m-d>', 'Open' => '<%f.2>', 'High' => '<%f.2>', 'Low' => '<%f.2>', 'Close' => '<%f.2>', 'Volume' => '<%u>', 'Adj_Close' => '-1' )
     */
    public function getCurrentQuote($provider)
    {
        $out = null;
        switch ($provider) {
            case 'yahoo':
                $quotes = new ApiClient();
                $quote = $quotes->getLatestQuote($this->symbol);
                $out['Symbol'] = $this->symbol;
                $date = new DateTime($quote['query']['created'], new DateTimeZone('America/New_York'));
                $out['Date'] = $date->format('Y-m-d');
                $out['Open'] = sprintf('%.2f', $quote['query']['results']['tr'][0]['td'][1]['content']);

                $daysRange = $quote['query']['results']['tr'][4]['td'][1]['content'];
                $bufferArray = explode(' - ', $daysRange);
                $out['High'] = sprintf('%.2f', $bufferArray[1]);
                $out['Low'] = sprintf('%.2f', $bufferArray[0]);

                $bufferArray = explode(' x ', $quote['query']['results']['tr'][2]['td'][1]['content']);
                $bid = $bufferArray[0];
                $bufferArray = explode(' x ', $quote['query']['results']['tr'][3]['td'][1]['content']);
                $ask = $bufferArray[0];
                $out['Close'] = sprintf('%.2f', ($bid + $ask) / 2);

                $out['Volume'] = sprintf('%u', str_replace(',', '', $quote['query']['results']['tr'][10]['td'][1]['content']));
                $out['Adj_Close'] = '-1';
                break;
        }

        return $out;

    }

    /**
     * Downloads OHLCV data from an OHLCV provider
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @param string $provider
     * @return array with OHLCV data
     */
    public function downloadOHLCV($startDate, $endDate, $provider)
    {
        switch ($provider) {
            case 'yahoo':
                $quotes = new ApiClient();
                $buffer = $quotes->getHistoricalData($this->symbol, $startDate, $endDate);
                if (1 == $buffer['query']['count']) { 
                    $out = $buffer;
                    unset($out['query']['results']['quote']);
                    $out['query']['results']['quote'][0] = $buffer['query']['results']['quote'];
                } else {
                    $out = $buffer;
                }
                break;
        }

        return $out;
    }

    /**
     * Reads OHLCV data from a csv file and puts it into a format of a provider
     * @param string $pathSpec path specification that includes the file name to read from
     * @param string $provider
     * @return array with OHLCV data. It will be similar to the provider format as if downloaded from them directly
     */
    public function readOHLCV($pathSpec, $provider)
    {
        $out = null;
        $csv = new CsvMappedReader($pathSpec);
        switch ($provider) {
            case 'yahoo':
                $queryDateTimeUTC = $this->createDate('modtime');
                $queryDateTimeUTC->setTimezone(new DateTimeZone('Etc/UTC'));
                $out = array(
                    'query' => array(
                        'count' => null,
                        'created' => $queryDateTimeUTC->format(DateTime::W3C),
                        'lang' => 'en-US',
                        'results' => array(
                            'quote' => array(),
                            ),
                        ),
                    );
                $i = 0;
                while ($csv->valid()) {
                    $line = $csv->current();
                    $line['Adj_Close'] = $line['Close'];
                    $out['query']['results']['quote'][] = array_merge(array('Symbol' => $this->symbol), $line);
                    $i++;
                    $csv->next();
                }
                $out['query']['count'] = $i;

                break;
        }
        return $out;
    }

    /**
     * Writes array of OHLCV data downloaded from a provider to disk
     * @param mixed $quotes OHLCV data downloaded from a provider
     * @param string $pathSpec path specification that includes the file name to write to
     * @param string provider
     */
    public function OHLCVArrayToFile(&$quotes, $pathSpec, $provider)
    {
        $fh = fopen($pathSpec, 'w');
        switch ($provider) {
            case 'yahoo':
                fputcsv($fh, ['Date','Open','High','Low','Close','Volume']);
                foreach ($quotes['query']['results']['quote'] as $record ) {
                    fputcsv($fh, array_slice($record, 1, -1));
                }
                break;
        }
        fclose($fh);
    }

    /**
     * Returns stock symbol with which this class was instantiated
     * @return string stock symbol for which this class was instantiated
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Returns path with the file name of the OHLCV file
     * @return string path/name
     */
    public function getOHLCVPathSpec()
    {
        return $this->pathToOHLCVFile;
    }

}
