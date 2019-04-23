<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotes;
use SgCsv\CsvMappedReader;
use Yasumi\Yasumi, Yasumi\Holiday, Yasumi\Filters\BankHolidaysFilter;
use Faker\Factory;

/**
 * Tests the class GetQuotes for when OHLCV quote file does not exist and needs to be downloaded. 
 * Time of the inquiry is assumed to be T: Wed-03-Aug-2016 0930-2359
 * Uses simulated yahoo api as OHLCV provider in $testOHLCVArray
 */

class GetQuotesYahooTest3 extends TestCase
{
    /**
     * object that stores the class under test
     */
    protected $CUT;
    /**
     * path to the y_universe.src file. This file contains all of the symbols with the header line
     */
    const PATH_TO_Y_UNIVERSE = 'data/source/ohlcv/y_universe.src';    
    /**
     * Name of the quotes provider
     */
    const PROVIDER = 'yahoo';
    /**
     * array sample historical quote array that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVArray = array(
        'query' => array(
            'count' => 4,
            'created' => '2016-08-03T20:35:30Z',
            'lang' => 'en-US',
            'results' => array(
                    'quote' => array(
                            0 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-02',
                                    'Open' => 38.599998,
                                    'High' => 38.669998,
                                    'Low' => 38.259998,
                                    'Close' => 38.57,
                                    'Volume' => 7501600,
                                    'Adj_Close' => 38.57,
                                ),

                            1 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-01',
                                    'Open' => 38.18,
                                    'High' => 38.889999,
                                    'Low' => 38.099998,
                                    'Close' => 38.799999,
                                    'Volume' => 9390600,
                                    'Adj_Close' => 38.799999,
                                ),

                            2 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-29',
                                    'Open' => 38.470001,
                                    'High' => 38.52,
                                    'Low' => 38.080002,
                                    'Close' => 38.189999,
                                    'Volume' => 13173300,
                                    'Adj_Close' => 38.189999,
                                ),

                            3 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-28',
                                    'Open' => 38.580002,
                                    'High' => 38.639999,
                                    'Low' => 38.23,
                                    'Close' => 38.52,
                                    'Volume' => 7404800,
                                    'Adj_Close' => 38.52,
                                ),
                            ),
                        ),
                    ),
            );
 

    use MGWebGroup\tests\ServiceFunctions;


    protected function setUp() 
    {
        if (!$fh = @fopen(self::PATH_TO_Y_UNIVERSE, 'r')) $this->markTestSkipped('File ' . self::PATH_TO_Y_UNIVERSE . ' does not exist');
        fclose($fh);

        // Two lines below will work either with a random symbol, or a mock one
        $symbol = $this->pickASymbol();
        //$symbol = 'TST';

        $pathSpec = GetQuotes::PATH_TO_OHLCV_FILES . '/' . GetQuotes::createNameOfOHLCVFile($symbol);
        fwrite(STDOUT, "\nQuotes will be downloaded into file: " . $pathSpec . "\n");

        if (file_exists($pathSpec)) unlink($pathSpec);

        $this->CUT = new GetQuotes($symbol, new DateTimeZone('America/Denver'));

        // Uncomment the code below to enable mock
        // $timeZoneNYC = new DateTimeZone('America/New_York');

        // $this->CUT = $this->getMockBuilder(GetQuotes::class)
        //         ->setMockClassName('GetQuotes')
        //         ->setConstructorArgs([$symbol, new DateTimeZone('America/Denver')])
        //         ->setMethods(['createDate','downloadOHLCV'])
        //         ->getMock();

        // $faker = Faker\Factory::create();
        // $now = $faker->dateTimeBetween($startDate = '2016-08-06 00:00:00', $endDate = '2016-08-07 23:59:59', $timezone = 'America/New_York');
        // $start = new DateTime('2016-07-28', $timeZoneNYC);
        // $map = [
        //     ['now', $now],
        //     ['start', $start]
        // ];        
        // $this->CUT->expects($this->any())
        //         ->method('createDate')
        //         ->will($this->returnValueMap($map));

        // $this->CUT->expects($this->any())
        //         ->method('downloadOHLCV')
        //         ->will($this->returnValue($this->testOHLCVArray));

    }

    /**
     * Tests that the saved OHLCV file is for the given symbol and is from STARTDATE to T.
     * Also tests a random line inside the file to match downloaded quote.
     */
    public function testOHLCV()
    {

        fwrite(STDOUT, "\n");

        $currentQuote = $this->CUT->updateQuotes(self::PROVIDER);
        if ($this->CUT->getDate('query')->format('B') < 604 ) $this->markTestSkipped('Test will be skipped as time of the query is earlier that 09:30am.');
        
        fwrite(STDOUT,"Current quote: " . "\n");
        print_r($currentQuote);
        fwrite(STDOUT,"\n");
        
        $symbol = $this->CUT->getSymbol();
        $startDate = $this->CUT->getDate('start');
        $endDate = $this->CUT->getDate('prevt');
        $quotes = $this->CUT->downloadOHLCV($startDate, $endDate, self::PROVIDER);

        $this->assertSame($symbol, $quotes['query']['results']['quote'][0]['Symbol'], "Picked symbol in the setUp does not match downloaded symbol\n");
        
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->assertFileExists($pathSpec);


        $csv = new CsvMappedReader($pathSpec);

        $lastLineIndex = $csv->count() - 1;
        $csv->seek($lastLineIndex);
        $line = $csv->current();
        $this->assertArraySubset($line, $quotes['query']['results']['quote'][$lastLineIndex - 1]);

        $csv->rewind();
        $line = $csv->current();
        $this->assertArraySubset($line, $currentQuote);
        $timeStampOfFirstLineInFile = strtotime($line['Date']);
        
        $csv->seek(1);
        $line = $csv->current();
        $timeStampOfSecondLineInFile = strtotime($line['Date']);
        $this->assertGreaterThan($timeStampOfSecondLineInFile, $timeStampOfFirstLineInFile);

        $randomLineNumber = rand(1,$lastLineIndex);
        $csv->seek($randomLineNumber);
        $line = $csv->current();
        $this->assertArraySubset($line, $quotes['query']['results']['quote'][$randomLineNumber - 1]);

        //print_r($currentQuote);

        
    }

}