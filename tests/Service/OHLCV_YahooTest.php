<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\OHLCV_Yahoo;
use Faker\Factory;
use App\Service\Exchange_Equities;
use App\Entity\OHLCVHistory;
use App\Exception\PriceHistoryException;

class OHLCV_YahooTest extends KernelTestCase
{
    private $SUT;

    private $faker;

    private $equities;

    private $instrument;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
	protected function setUp(): void
    {
        self::bootKernel();
        // $container = self::$container;
        // $container = self::$kernel->getContainer();
        $container = self::$container->get('test.service_container');

        $this->SUT = $container->get(OHLCV_Yahoo::class);

        $this->faker = Factory::create();

        $this->equities = $container->get(Exchange_Equities::class);

        $exchanges = ['NYSE', 'NASDAQ'];
        $exchange = $this->faker->randomElement($exchanges);
        
        $instruments = $this->equities->getTradedInstruments($exchange);
        $this->instrument = $this->faker->randomElement($instruments);
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Testing OHLCV_Yahoo');
    	$this->assertTrue(true);
    }

    /**
     * test downloadHistory:
     * Check if downloads at least 4 historical records for an instrument
     */
    public function test10()
    {
        $fromDate = new \DateTime('1 week ago');
        $toDate = new \DateTime();
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $this->assertGreaterThanOrEqual(4, count($history));

        foreach ($history as $item) {
            $this->assertInstanceOf(OHLCVHistory::class, $item);
        }
    }

    /**
     * Today is a trading day, $toDate is set for today, 
     * Check if downloads history with the last date as last T from today
     */
    public function test20()
    {
        $_SERVER['TODAY'] = '2019-05-20'; // Monday, May 20, 2019 is a T
        $toDate = new \DateTime($_SERVER['TODAY']);
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }

    /**
     * today is not a trading day, $toDate is set for today, 
     * check if downloads history with the last date as last T from today
     */
    public function test30()
    {
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $toDate = new \DateTime($_SERVER['TODAY']);
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));        
    }

    /**
     * $toDate is set for some day in the past, and it is a T
     *  check that history downloads including $toDate
     */
    public function test40()
    {
        $toDate = new \DateTime('2019-05-15'); // Wednesday, May 15, 2019
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-15', $latestItem->getTimestamp()->format('Y-m-d'));
    }    

    /**
     * $toDate is set for some date in the past, and it is not a T
     * check that history downloads including up to $toDate
     */
    public function test50()
    {
        $toDate = new \DateTime('2019-04-19'); // Friday, April 19, 2019 Good Friday
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P1W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        // var_dump($latestItem); exit();
        $this->assertSame('2019-04-18', $latestItem->getTimestamp()->format('Y-m-d'));
    }    

    /**
     * $toDate is set for future
     * today is a trading day
     * check if downloads history with the last date as last T from today
     */
    public function test60()
    {
        $toDate = new \DateTime('2019-05-20');
        $toDate->add(new \DateInterval('P1W'));
        $_SERVER['TODAY'] = '2019-05-20'; // Monday, May 20, 2019 is a T
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P2W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }    


    /**
     * $toDate is set for future
     * today is not a trading day
     * check if downloads history with the last date as last T from today
     */
    public function test70 ()
    {
        $toDate = new \DateTime('2019-05-20');
        $toDate->add(new \DateInterval('P1W'));
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $fromDate = clone $toDate;
        $fromDate->sub(new \DateInterval('P2W'));
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);  // pop element off the end (last element) of array
        
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));    
    }


    /**
     * $fromDate = $toDate
     * will throw PriceHistoryException
     */
    public function test80()
    {
        $toDate = new \DateTime('2019-05-20');
        $fromDate = new \DateTime('2019-05-20');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }
    

    /**
     * When $fromDate is a Saturday, $toDate is a Sunday Yahoo API will return error
     * My code supposed to return PriceHistoryException
     */
    public function test90()
    {
        // $toDate = new \DateTime('2019-05-19');
        // $fromDate = new \DateTime('2019-05-18');
        // $options = ['interval' => 'P1D'];

        // $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        // var_dump($history); 
    }

    // see if $fromDate = $toDate, both are in the past
    // downloads one record for $toDate?? What if it's not a trading day?

    // see if $fromDate = $toDate, both are in the future
    // returns null, or empty history.??


    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }

}