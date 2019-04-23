<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotes;
use SgCsv\CsvMappedReader;

/**
 * Tests base functions of the class GetQuotes.
 * This test does not require internet connection.
 * Uses sample array of historical OHLCV data downloaded from yahoo.
 */
class GetQuotesBaseTest10 extends TestCase
{
    /**
     * object that stores the class under test
     */
    protected $CUT;
    /**
     * array sample historical quote array that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVArray1 = array(
        'query' => array(
            'count' => 4,
            'created' => '2016-08-03T20:35:30Z',
            'lang' => 'en-US',
            'results' => array(
                    'quote' => array(
                            0 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-02',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),

                            1 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-01',
                                    'Open' => '38.18',
                                    'High' => '38.889999',
                                    'Low' => '38.099998',
                                    'Close' => '38.799999',
                                    'Volume' => '9390600',
                                    'Adj_Close' => '38.799999',
                                ),

                            2 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-29',
                                    'Open' => '38.470001',
                                    'High' => '38.52',
                                    'Low' => '38.080002',
                                    'Close' => '38.189999',
                                    'Volume' => '13173300',
                                    'Adj_Close' => '38.189999',
                                ),

                            3 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-28',
                                    'Open' => '38.580002',
                                    'High' => '38.639999',
                                    'Low' => '38.23',
                                    'Close' => '38.52',
                                    'Volume' => '7404800',
                                    'Adj_Close' => '38.52',
                                ),
                            ),
                        ),
                    ),
            );
    /**
     * array sample historical quote array that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVArray2 = array(
        'query' => array(
            'count' => 1,
            'created' => '2016-08-03T20:35:30Z',
            'lang' => 'en-US',
            'results' => array(
                'quote' => array(
                        0 => array(
                            'Symbol' => 'TST',
                            'Date' => '2016-08-02',
                            'Open' => '38.599998',
                            'High' => '38.669998',
                            'Low' => '38.259998',
                            'Close' => '38.57',
                            'Volume' => '7501600',
                            'Adj_Close' => '38.57',
                            ),
                        ),
                    ),
                ),
            );


    use MGWebGroup\tests\ServiceFunctions;

    protected function setUp() 
    {
        $symbol = 'TST';
        $this->CUT = new GetQuotes($symbol, new DateTimeZone('America/Denver'));

    }

    /**
     * Tests creation of the file name against a symbol
     */
    public function testCreateNameOfOHLCVFile()
    {   
        fwrite(STDOUT, "\n");
        $fileName = GetQuotes::createNameOfOHLCVFile($this->CUT->getSymbol());
        fwrite(STDOUT, 'Expected name of the OHLCV file: ' . $fileName . "\n");
        //$this->assertRegExp('/^[A-Z\.]+_d_[[:digit:]]{8}\.csv/', $fileName);
        $this->assertRegExp('/^[A-Z\.]+_d\.csv/', $fileName);
        return $fileName;
    }

    /**
     * Tests if the list of holidays is created
     */
    public function testHolidays()
    {
        fwrite(STDOUT, "\nTesting holidays:\n");
        $holidaysList = ['newYearsDay',
            'martinLutherKingDay',
            'washingtonsBirthday',
            'goodFriday',
            'memorialDay',
            'independenceDay',
            'labourDay',
            'thanksgivingDay',
            'christmasDay'];
        $actual = $this->CUT->getHolidays();
        foreach ($holidaysList as $expected) {
            $this->assertArrayHasKey($expected, $actual);
        }
    }

    /**
     * Displays the previous trading days for the current day, the closest weekend and all holidays.
     * @depends testHolidays
     */
    public function testCalcPrevT()
    {
        fwrite(STDOUT, "\nTesting dates calculated for T-1:");
        $timeZone = new DateTimeZone('America/New_York');
        $queryDateTime = new DateTime(null, $timeZone);

        fwrite(STDOUT,"\n");
        $prevT = $this->CUT->calcPrevT($queryDateTime);
        $this->printDateExplanation('Today\'s date', $queryDateTime);
        $this->printDateExplanation(' T-1=', $prevT); 
        fwrite(STDOUT,"\n");
        
        $message = 'Saturday this week';
        $queryDateTime->modify($message);
        $prevT = $this->CUT->calcPrevT($queryDateTime);
        $this->printDateExplanation($message, $queryDateTime);
        $this->printDateExplanation(' T-1=', $prevT); 
        fwrite(STDOUT,"\n");

        $message = 'Sunday this week';
        $queryDateTime->modify($message);
        $prevT = $this->CUT->calcPrevT($queryDateTime);
        $this->printDateExplanation($message, $queryDateTime);
        $this->printDateExplanation(' T-1=', $prevT); 
        fwrite(STDOUT,"\n");

        foreach ($this->CUT->getHolidays() as $message => $dateString) {
            $queryDateTime->modify($dateString);
            $prevT = $this->CUT->calcPrevT($queryDateTime);
            $this->printDateExplanation($message, $queryDateTime);
            $this->printDateExplanation(' T-1=', $prevT); 
            fwrite(STDOUT,"\n");
        }
        
    }

    /**
     * Tests if file modification time is read in NYC time zone
     */
    public function testReadModTime()
    {
        fwrite(STDOUT, "\n");
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $fh = fopen($pathSpec, 'w');
        $this->assertFileExists($pathSpec);
        fclose($fh);

        $fileModTime = $this->CUT->createDate('modtime');
        $this->printDateExplanation('Test OHLCV file modtime',$fileModTime);
        $actual = $this->CUT->createDate('now')->diff($fileModTime);
        $this->assertLessThan(1, $actual->format('i'));
    }

    /**
     * Tests if array with OHLCV data is written to disk and is written properly. Array of OHLCV data contains more than one quote.
     */
    public function testOHLCVArrayToFile1()
    {
        fwrite(STDOUT, "\nTesting file write capability\n");
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->CUT->OHLCVArrayToFile($this->testOHLCVArray1, $pathSpec, 'yahoo');
        $this->assertFileExists($pathSpec);

        $csv = new CsvMappedReader($pathSpec);

        $lastLineIndex = $csv->count() - 1;
        $csv->seek($lastLineIndex);
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray1['query']['results']['quote'][$lastLineIndex]);

        $csv->rewind();
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray1['query']['results']['quote'][0]);

    }

    /**
     * Tests if OHLCV file can be read from disk and put into a provier format
     */
    public function testReadOHLCV1()
    {
        fwrite(STDOUT, "\nTesting OHLCV file read capability\n");
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $loadedOHLCV = $this->CUT->readOHLCV($pathSpec, 'yahoo');
        $lastLineIndex = count($this->testOHLCVArray1) - 1;
        $this->assertArraySubset($loadedOHLCV['query']['results']['quote'][0], $this->testOHLCVArray1['query']['results']['quote'][0]);
        $this->assertArraySubset($loadedOHLCV['query']['results']['quote'][$lastLineIndex], $this->testOHLCVArray1['query']['results']['quote'][$lastLineIndex]);

    }

    /**
     * Tests if array with OHLCV data is written to disk and is written properly. Array of OHLCV data contains more than one quote.
     */
    public function testOHLCVArrayToFile2()
    {
        fwrite(STDOUT, "\nTesting file write capability\n");
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->CUT->OHLCVArrayToFile($this->testOHLCVArray2, $pathSpec, 'yahoo');
        $this->assertFileExists($pathSpec);

        $csv = new CsvMappedReader($pathSpec);

        $lastLineIndex = $csv->count() - 1;
        $csv->seek($lastLineIndex);
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray2['query']['results']['quote'][$lastLineIndex]);

        $csv->rewind();
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray2['query']['results']['quote'][0]);

    }

    /**
     * Tests if OHLCV file can be read from disk and put into a provier format
     */
    public function testReadOHLCV2()
    {
        fwrite(STDOUT, "\nTesting OHLCV file read capability\n");
        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $loadedOHLCV = $this->CUT->readOHLCV($pathSpec, 'yahoo');
        $lastLineIndex = count($this->testOHLCVArray2) - 1;
        $this->assertArraySubset($loadedOHLCV['query']['results']['quote'][0], $this->testOHLCVArray2['query']['results']['quote'][0]);
        $this->assertArraySubset($loadedOHLCV['query']['results']['quote'][$lastLineIndex], $this->testOHLCVArray2['query']['results']['quote'][$lastLineIndex]);

    }

    protected function tearDown()
    {
        unset($this->CUT);
    }


}