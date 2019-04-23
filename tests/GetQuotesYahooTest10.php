<?php
use PHPUnit\Framework\TestCase;
use MGWebGroup\GetQuotes;
use SgCsv\CsvMappedReader;
use Yasumi\Yasumi, Yasumi\Holiday, Yasumi\Filters\BankHolidaysFilter;
use Faker\Factory;

/**
 * Tests download of current quotes from provider yahoo. Requies internet connection.
 */
class GetQuotesYahooTest10 extends TestCase
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
     * stores fields that must be returned for the current and historical quotes
     */
    protected $fields;


    use MGWebGroup\tests\ServiceFunctions;


    protected function setUp()
    {
        if (!$fh = @fopen(self::PATH_TO_Y_UNIVERSE, 'r')) $this->markTestSkipped('File ' . self::PATH_TO_Y_UNIVERSE . ' does not exist');
        fclose($fh);

        $symbol = $this->pickASymbol();

        $this->CUT = new GetQuotes($symbol, new DateTimeZone('America/Denver'));

        $this->fields = ['Symbol','Date','Open','High','Low','Close','Volume','Adj_Close'];

    }

    /**
     * Tests download of current quote
     */
    public function testGetCurrentQuote()
    {
        $symbol = $this->CUT->getSymbol();
        fwrite(STDOUT,"\nTesting download of the current quote from provider yahoo. Symbol=" . $symbol . "\n");
        $currentQuote = $this->CUT->getCurrentQuote(self::PROVIDER);
        print_r($currentQuote);
        foreach ($this->fields as $key){
            $this->assertArrayHasKey($key,$currentQuote);
            $this->assertNotNull($currentQuote[$key]);
            $this->assertInternalType('string',$currentQuote[$key]);
        }
    }

    /**
     * Tests download of historical OHLCV data from GetQuotes::STARTDATE to T-1
     */
    public function testDownloadOHLCV1()
    {
        $symbol = $this->CUT->getSymbol();
        fwrite(STDOUT,"\nTesting download of historical quotes from provider yahoo. Symbol=" . $symbol);
        $startDate = $this->CUT->createDate('start');
        $today = $this->CUT->createDate('now');
        $endDate = $this->CUT->calcPrevT($today);
        fwrite(STDOUT, " prevT=" . $endDate->format('Y-m-d') . "\n");
        $OHLCV = $this->CUT->downloadOHLCV($startDate, $endDate, self::PROVIDER);

        $this->assertSame($symbol, $OHLCV['query']['results']['quote'][0]['Symbol'], "Picked symbol in the setUp does not match downloaded symbol\n");

        $index = 0;
        foreach ($this->fields as $key) {
            $this->assertArrayHasKey($key,$OHLCV['query']['results']['quote'][$index]);
            $this->assertNotNull($OHLCV['query']['results']['quote'][$index][$key]);
            $this->assertInternalType('string', $OHLCV['query']['results']['quote'][$index][$key]);
        }
        $this->assertSame($endDate->format('Y-m-d'),$OHLCV['query']['results']['quote'][$index]['Date']);

        $index = count($OHLCV['query']['results']['quote']) - 1;
        foreach ($this->fields as $key) {
            $this->assertArrayHasKey($key,$OHLCV['query']['results']['quote'][$index]);
            $this->assertNotNull($OHLCV['query']['results']['quote'][$index][$key]);

        }
        $this->assertSame($startDate->format('Y-m-d'),$OHLCV['query']['results']['quote'][$index]['Date']);


    }

    /**
     * Tests download of historical OHLCV data from T-1 to today (either T or not-T)
     */
    public function testDownloadOHLCV2()
    {
        $symbol = $this->CUT->getSymbol();
        fwrite(STDOUT,"\nTesting download of historical quotes from provider yahoo. Symbol=" . $symbol);
        $today = $this->CUT->createDate('now');
        $startDate = $this->CUT->calcPrevT($today);
        $endDate = $today;
        fwrite(STDOUT, " startDate=" . $startDate->format('Y-m-d') . " endDate=" . $endDate->format('Y-m-d') . "\n");
        $OHLCV = $this->CUT->downloadOHLCV($startDate, $endDate, self::PROVIDER);

        print_r($OHLCV);
        $this->assertSame($symbol, $OHLCV['query']['results']['quote'][0]['Symbol'], "Picked symbol in the setUp does not match downloaded symbol\n");
        $this->assertSame(1, $OHLCV['query']['count']);

        foreach ($this->fields as $key) {
            $this->assertArrayHasKey($key,$OHLCV['query']['results']['quote'][0]);
            $this->assertNotNull($OHLCV['query']['results']['quote'][0][$key]);
            $this->assertInternalType('string', $OHLCV['query']['results']['quote'][0][$key]);
        }
        $this->assertSame($startDate->format('Y-m-d'),$OHLCV['query']['results']['quote'][0]['Date']);

    }


    protected function tearDown()
    {
        unset($this->CUT);
    }

}