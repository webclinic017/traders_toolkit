<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotes;
use SgCsv\CsvMappedReader;
use Yasumi\Yasumi, Yasumi\Holiday, Yasumi\Filters\BankHolidaysFilter;
use Faker\Factory;

/**
 * Test case: timeOfInquiry = Trading Day (T) Fri-05-Aug-2016 0000-0930, timeOfLastUpdateOfTheQuoteFile = T-2 Wed-03-Aug-2016
 * Mock historical data is used. No internet connection required.
 */

class GetQuotesYahooTest70 extends TestCase
{
    /**
     * object that stores the class under test
     */
    protected $CUT;
    /**
     * Name of the quotes provider
     */
    const PROVIDER = 'yahoo';
    /**
     * array sample historical quote file that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVFile = array(
        'query' => array(
            'count' => 6,
            'created' => '2016-08-04T16:00:00Z',
            'lang' => 'en-US',
            'results' => array(
                    'quote' => array(
                            0 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-03',
                                    'Open' => '38.599998',
                                    'High' => '38.669998',
                                    'Low' => '38.259998',
                                    'Close' => '38.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '38.57',
                                ),
                            1 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-02',
                                    'Open' => '37.599998',
                                    'High' => '37.669998',
                                    'Low' => '37.259998',
                                    'Close' => '37.57',
                                    'Volume' => '7501600',
                                    'Adj_Close' => '37.57',
                                ),

                            2 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-08-01',
                                    'Open' => '36.18',
                                    'High' => '36.889999',
                                    'Low' => '36.099998',
                                    'Close' => '36.799999',
                                    'Volume' => '9390600',
                                    'Adj_Close' => '36.799999',
                                ),

                            3 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-29',
                                    'Open' => '35.470001',
                                    'High' => '35.52',
                                    'Low' => '35.080002',
                                    'Close' => '35.189999',
                                    'Volume' => '13173300',
                                    'Adj_Close' => '35.189999',
                                ),

                            4 => array(
                                    'Symbol' => 'TST',
                                    'Date' => '2016-07-28',
                                    'Open' => '34.580002',
                                    'High' => '34.639999',
                                    'Low' => '34.23',
                                    'Close' => '34.52',
                                    'Volume' => '7404800',
                                    'Adj_Close' => '34.52',
                                ),
                            ),
                        ),
                    ),
            );

    /**
     * array sample historical quote array that can be downloaded from the yahoo finance api
     */
    protected $testOHLCVArray = array(
        'query' => array(
            'count' => 1,
            'created' => '2016-08-04T20:00:00Z',
            'lang' => 'en-US',
            'results' => array(
                'quote' => array(
                    0 => array(
                        'Symbol' => 'TST',
                        'Date' => '2016-08-04',
                        'Open' => '40.599998',
                        'High' => '40.669998',
                        'Low' => '40.259998',
                        'Close' => '40.57',
                        'Volume' => '7501600',
                        'Adj_Close' => '40.57',                                
                        ),
                    1 => array(
                        'Symbol' => 'TST',
                        'Date' => '2016-08-03',
                        'Open' => '35.599998',
                        'High' => '35.669998',
                        'Low' => '35.259998',
                        'Close' => '35.57',
                        'Volume' => '1000000',
                        'Adj_Close' => '35.57',
                    ),
                ),
            ),
        ),
    );
    /**
     * array sample current quote array that can be downloaded from the yahoo web page
     */    
    protected $testCurrentQuote = array(
        'Symbol' => 'TST',
        'Date' => '2016-08-05',
        'Open' => '86.63',
        'High' => '84.48',
        'Low' => '83.58',
        'Close' => '82.79',
        'Volume' => '565956',
        'Adj_Close' => '-1',
    );

    use MGWebGroup\tests\ServiceFunctions;


    protected function setUp() 
    {
 
        $symbol = 'TST';

        $pathSpec = GetQuotes::PATH_TO_OHLCV_FILES . '/' . GetQuotes::createNameOfOHLCVFile($symbol);

        if (file_exists($pathSpec)) unlink($pathSpec);

        $timeZoneNYC = new DateTimeZone('America/New_York');
        $fileSystemTimeZone = new DateTimeZone('America/Denver');

        $this->CUT = $this->getMockBuilder(GetQuotes::class)
                ->setMockClassName('GetQuotes')
                ->setConstructorArgs([$symbol, $fileSystemTimeZone])
                ->setMethods(['createDate','getCurrentQuote','downloadOHLCV'])
                ->getMock();

        $now = new DateTime('2016-08-05 00:00:00', $timeZoneNYC);
        $randomOffsetInSeconds = rand(0,9*3600+29*60);
        $interval = new DateInterval('PT' . $randomOffsetInSeconds . 'S');
        $now->add($interval);
        $start = new DateTime('2016-07-28', $timeZoneNYC);
        $modTime = new DateTime('2016-08-04 15:00:00', $timeZoneNYC);

        $map = [
            ['now', $now],
            ['start', $start],
            ['modtime', $modTime],
        ];        
        $this->CUT->expects($this->any())
                ->method('createDate')
                ->will($this->returnValueMap($map));

        $this->CUT->expects($this->never())
                ->method('getCurrentQuote')
                ->will($this->returnValue($this->testCurrentQuote));

        $this->CUT->expects($this->once())
                ->method('downloadOHLCV')
                ->will($this->returnValue($this->testOHLCVArray));


    }

    /**
     * Tests that the saved OHLCV file is for the given symbol and is from STARTDATE to T-1.
     * Also tests a random line inside the file to match downloaded quote.
     */
    public function testOHLCV()
    {
        fwrite(STDOUT,"\nTest case: timeOfInquiry = Trading Day (T) Fri-05-Aug-2016 0000-0930, timeOfLastUpdateOfTheQuoteFile = T-2 Thu-03-Aug-2016. Inquiry time: ");

        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->CUT->OHLCVArrayToFile($this->testOHLCVFile, $pathSpec, 'yahoo');
        $this->assertFileExists($pathSpec);

        $currentQuote = $this->CUT->updateQuotes(self::PROVIDER);
        
        fwrite(STDOUT, $this->CUT->getDate('query')->format(DateTime::W3C) . "\n");

        $pathSpec = $this->CUT->getOHLCVPathSpec();
        $this->assertFileExists($pathSpec);

        $csv = new CsvMappedReader($pathSpec);

        $lastLineIndexCSV = $csv->count() - 1;
        $lastLineIndexOHLCVFile = count($this->testOHLCVFile['query']['results']['quote']) - 1;
        $csv->seek($lastLineIndexCSV);
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVFile['query']['results']['quote'][$lastLineIndexOHLCVFile]);

        $csv->rewind();
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray['query']['results']['quote'][0]);

        $csv->next();
        $line = $csv->current();
        $this->assertArraySubset($line, $this->testOHLCVArray['query']['results']['quote'][1]);        

    }


    protected function tearDown()
    {
        unset($this->CUT);
    }


}