<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\OHLCV_Yahoo;
use Faker\Factory;
use App\Service\Exchange_Equities;
use App\Entity\OHLCVHistory;

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
        $fromDate = new \DateTime('1 week ago');
        $options = ['interval' => 'P1D'];

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);

        $latestItem = array_pop($history);
        // var_dump($latestItem); exit();
        $this->assertSame('2019-05-17', $latestItem->getTimestamp()->format('Y-m-d'));
    }


        // today is not a trading day, $toDate is set for today, 
        // check if downloads history with the last date as last T from today

        // $toDate is set for some day in the past, and it is a T
        // check that history downloads including $toDate

        // $toDate is set for some date in the past, and it is not a T
        // check that history downloads including $toDate

        // $toDate is set for future, check that if:
        // today is a trading day
        // check if downloads history with the last date as last T from today

        // $toDate is set for future, check that if:
        // today is not a trading day
        // check if downloads history with the last date as last T from today



        // Decide on these outcomes:

        // see if $fromDate = $toDate, both are today
        // ??

        // see if $fromDate = $toDate, both are in the past
        // downloads one record for $toDate?? What if it's not a trading day?

        // see if $fromDate = $toDate, both are in the future
        // returns null, or empty history.??


    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }

}