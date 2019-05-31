<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\OHLCV_Yahoo;
use Faker\Factory;
use App\Service\Exchange_Equities;
use App\Entity\OHLCVHistory;
use App\Entity\Instrument;
use App\Entity\OHLCVQuote;
use App\Exception\PriceHistoryException;

class OHLCV_YahooTest extends KernelTestCase
{
    private $SUT;

    private $faker;

    private $equities;

    private $instrument;

    private $em;

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

        $this->em = $container->get('doctrine')->getManager();
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

        // make sure $fromDate is older than $toDate
        $firstElement = array_shift($history);
        $lastElement = array_pop($history);
        $this->assertGreaterThan($firstElement->getTimestamp()->format('U'), $lastElement->getTimestamp()->format('U'));
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
        $toDate = new \DateTime('2019-05-19');
        $fromDate = new \DateTime('2019-05-18');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * Check for a long weekend
     * My code supposed to return PriceHistoryException
     */
    public function test100()
    {
        $toDate = new \DateTime('2019-05-25');
        $fromDate = new \DateTime('2019-05-27');
        $options = ['interval' => 'P1D'];

        $this->expectException(PriceHistoryException::class);

        $history = $this->SUT->downloadHistory($this->instrument, $fromDate, $toDate, $options);
    }

    /**
     * Test addHistory:
     * Simulated downloaded 3 records partially overlap original values
     * Check that only new values are overwritten
     */
    public function test110()
    {
        // will commit to db temporarily to perform the test
        // $this->em->getConnection()->beginTransaction();

        // store 5 records for a week
        $startDate1 = new \DateTime('2018-05-14'); // Monday
        $interval = new \DateInterval('P1D');
        $instrument = $this->createMockHistory(clone $startDate1, $numberOfRecords = 5, $interval);

        // simulate downloaded 3 records with different values
        $startDate2 = new \DateTime('2018-05-16'); // Wednesday
        $addedHistory = $this->createSimulatedDownload($instrument, clone $startDate2, $numberOfRecords = 3, $interval);


        // add to history
        $this->SUT->addHistory($instrument, $addedHistory);
        
        // $this->em->getConnection()->commit();

        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate1)
            // ->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC')
        ;

        $query = $qb->getQuery();

        $result = $query->getResult();

        $this->assertCount(5, $result);

        $this->assertSame($startDate1->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        $this->assertSame($startDate2->format('Y-m-d'), $result[2]->getTimestamp()->format('Y-m-d'));


        for ($i = 2; $i <= 4; $i++) {
            $this->assertEquals($this->computeControlSum($addedHistory[$i-2]), $this->computeControlSum($result[$i]));
        }

        // rollback db storage
        // $this->em->getConnection()->rollBack();
    }

    /**
     * Test addHistory:
     * Simulated downloaded 3 records do not overlap original values
     * Check that all original values are intact
     */
    public function test120()
    {
        // store 5 records for a week
        $startDate1 = new \DateTime('2018-05-14'); // Monday
        $interval = new \DateInterval('P1D');
        $instrument = $this->createMockHistory(clone $startDate1, $numberOfRecords = 5, $interval);

        // simulate downloaded 3 records with different values
        $startDate2 = new \DateTime('2018-05-28'); // Monday
        $addedHistory = $this->createSimulatedDownload($instrument, clone $startDate2, $numberOfRecords = 3, $interval);


        // add to history
        $this->SUT->addHistory($instrument, $addedHistory);
        
        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate1)
            // ->andWhere('o.timestamp <= :toDate')->setParameter('toDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC')
        ;

        $query = $qb->getQuery();

        $result = $query->getResult();

        $this->assertCount(8, $result);

        $this->assertSame($startDate1->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        $this->assertSame($startDate2->format('Y-m-d'), $result[5]->getTimestamp()->format('Y-m-d'));

        for ($i = 5; $i <= 7; $i++) {
            $this->assertEquals($this->computeControlSum($addedHistory[$i-5]), $this->computeControlSum($result[$i]));
        }
    }

    /**
     * Test retieveHistory
     */
    public function test130()
    {
        // store 5 records for a week
        $startDate = new \DateTime('2018-05-14'); // Monday
        $endDate = clone $startDate; // this one will be changed inside createMockHistory, and when done will have $endDate
        $interval = 'P1D';
        $options = ['interval' => $interval];
        $interval = new \DateInterval($interval);
        $instrument = $this->createMockHistory($endDate, $numberOfRecords = 5, $interval);

        // retrieve history
        $history = $this->SUT->retrieveHistory($instrument, $startDate, $endDate, $options);

        // check
        $OHLCVRepository = $this->em->getRepository(OHLCVHistory::class);

        $qb = $OHLCVRepository->createQueryBuilder('o');
        $qb->where('o.instrument = :instrument')
            ->andWhere('o.timeinterval = :interval')
            ->setParameters(['instrument' => $instrument, 'interval' => $interval])
            ->andWhere('o.timestamp >= :fromDate')->setParameter('fromDate', $startDate)
            ->andWhere('o.timestamp <= :endDate')->setParameter('endDate', $endDate)
            ->andWhere('o.provider = :provider')->setParameter('provider', $this->SUT::PROVIDER_NAME)
            ->orderBy('o.timestamp', 'ASC')
        ;

        $query = $qb->getQuery();

        $result = $query->getResult();

        $this->assertCount(5, $result);

        $this->assertSame($startDate->format('Y-m-d'), $result[0]->getTimestamp()->format('Y-m-d'));
        // $this->assertSame($endDate->format('Y-m-d'), $result[4]->getTimestamp()->format('Y-m-d'));

        for ($i = 0; $i <= 4; $i++) {
            $this->assertEquals($this->computeControlSum($result[$i]), $this->computeControlSum($history[$i]));
        }
    }

    /**
     * Test downloadQuote
     */
    public function test140()
    {
        // market is open:
        // a quote is downloaded
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $quote = $this->SUT->downloadQuote($this->instrument);

        $this->assertInstanceOf(OHLCVQuote::class, $quote);

        // market is closed:
        // null is returned for quote
        $_SERVER['TODAY'] = '2019-05-19'; // Sunday, May 19, 2019
        $quote = $this->SUT->downloadQuote($this->instrument);

        $this->assertNull($quote);
    }

    /**
     * Test saveQuote
     */
    public function test150()
    {
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $date = new \DateTime($_SERVER['TODAY']);
        $interval = new \DateInterval('P1D');
        $OHLCVQuoteRepository = $this->em->getRepository(OHLCVQuote::class);
        // Quote is not already saved. 
        // New quote gets saved in in storage
        $newQuote = $this->SUT->downloadQuote($this->instrument);
        $this->SUT->saveQuote($newQuote);

        $results = $OHLCVQuoteRepository->findBy(['instrument' => $this->instrument]);

        $this->assertCount(1, $results);

        $this->assertSame($newQuote->getTimestamp()->format('Y-m-d'), $results[0]->getTimestamp()->format('Y-m-d'));

        $this->em->remove($newQuote);
        $this->em->flush();

        // Quote is already saved.  Only one quote supposed to remain in storage. Existing quote must be removed, and new one returned.
        // Simulate saving of existing quote
        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($date);
        $quote->setTimeinterval($interval);
        $quote->setOpen(rand(100,10000)/100);
        $quote->setHigh(rand(100,10000)/100);
        $quote->setLow(rand(100,10000)/100);
        $quote->setClose(rand(100,10000)/100);
        $quote->setVolume(rand(100,10000));

        $this->em->persist($quote);
        $this->em->flush();
        $quoteId = $quote->getId();
        // download actual quote
        $newQuote = $this->SUT->downloadQuote($this->instrument);
        $this->SUT->saveQuote($newQuote);

        // check that the old one is removed
        // var_dump($OHLCVQuoteRepository->find($quote->getId()));
        $this->assertNull($OHLCVQuoteRepository->find($quoteId));

        $results = $OHLCVQuoteRepository->findBy(['instrument' => $this->instrument]);

        $this->assertCount(1, $results);

        $this->assertSame($newQuote->getTimestamp()->format('Y-m-d'), $results[0]->getTimestamp()->format('Y-m-d'));
    }    
    
    /**
     * Test retrieveQuote
     */
    public function test160()
    {
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        $date = new \DateTime($_SERVER['TODAY']);
        $interval = new \DateInterval('P1D');
        $OHLCVQuoteRepository = $this->em->getRepository(OHLCVQuote::class);

        // Simulate saving of existing quote
        $quote = new OHLCVQuote();
        $quote->setInstrument($this->instrument);
        $quote->setProvider($this->SUT::PROVIDER_NAME);
        $quote->setTimestamp($date);
        $quote->setTimeinterval($interval);
        $quote->setOpen(rand(100,10000)/100);
        $quote->setHigh(rand(100,10000)/100);
        $quote->setLow(rand(100,10000)/100);
        $quote->setClose(rand(100,10000)/100);
        $quote->setVolume(rand(100,10000));

        $this->em->persist($quote);
        $this->em->flush();
        $quoteId = $quote->getId();

        $savedQuote = $this->SUT->retrieveQuote($this->instrument);
        var_dump($savedQuote); 
    }    

    private function createMockHistory($startDate, $numberOfRecords, $interval)
    {
        $instrument = new Instrument();
        $instrument->setSymbol('TEST');
        $instrument->setName('Instrument for testing purposes');
        $instrument->setExchange('NYSE');

        $this->em->persist($instrument);

        // $interval = new \DateInterval('P1D');
        for ($i = 0; $i < $numberOfRecords; $i++, $startDate->add($interval)) {
            $record = new OHLCVHistory();
            $record->setInstrument($instrument);
            $record->setProvider($this->SUT::PROVIDER_NAME);
            $record->setTimestamp(clone $startDate);
            $record->setTimeinterval($interval);
            $record->setOpen(rand(100,10000)/100);
            $record->setHigh(rand(100,10000)/100);
            $record->setLow(rand(100,10000)/100);
            $record->setClose(rand(100,10000)/100);
            $record->setVolume(rand(100,10000));

            $this->em->persist($record);
        }

        $this->em->flush();

        return $instrument;
    }

    private function createSimulatedDownload($instrument, $startDate, $numberOfRecords, $interval)
    {
        // $instrument = new Instrument();
        // $instrument->setSymbol('TEST');
        // $instrument->setName('Instrument for testing purposes');
        // $instrument->setExchange('NYSE');

        // $interval = new \DateInterval('P1D');
        $out = [];
        for ($i = 0; $i < $numberOfRecords; $i++, $startDate->add($interval)) {
            $record = new OHLCVHistory();
            $record->setInstrument($instrument);
            $record->setProvider($this->SUT::PROVIDER_NAME);
            $record->setTimestamp(clone $startDate);
            $record->setTimeinterval($interval);
            $record->setOpen(rand(100,10000)/100);
            $record->setHigh(rand(100,10000)/100);
            $record->setLow(rand(100,10000)/100);
            $record->setClose(rand(100,10000)/100);
            $record->setVolume(rand(100,10000));
            $out[] = $record;
        }

        return $out;
    }

    private function computeControlSum(OHLCVHistory $ohlcvHistory)
    {
        return $ohlcvHistory->getOpen() + $ohlcvHistory->getHigh() + $ohlcvHistory->getLow() + $ohlcvHistory->getClose() + $ohlcvHistory->getVolume();
    }

    protected function tearDown(): void
    {
        $instrumentRepository = $this->em->getRepository(Instrument::class);
        $qb = $instrumentRepository->createQueryBuilder('i');
        $qb->delete()->where('i.symbol = :symbol')->setParameter('symbol', 'TEST');
        $query = $qb->getQuery();
        $query->execute();

        $quoteRepository = $this->em->getRepository(OHLCVQuote::class);
        // $instrumentId = $this->instrument->getId();
        $qb = $quoteRepository->createQueryBuilder('q');
        $qb->delete()->where('q.instrument = :instrument')->setParameter('instrument', $this->instrument);
        $qb->getQuery()->execute();

        $this->em->close();
        $this->em = null;
    }

}