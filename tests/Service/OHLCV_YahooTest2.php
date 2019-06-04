<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\OHLCV_Yahoo;


class OHLCV_YahooTest2 extends KernelTestCase
{
    private $SUT;


	protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container->get('test.service_container');
        
        $this->SUT = $container->get(OHLCV_Yahoo::class);
    }

    /**
     * Test saveQuote
     */
    public function test10()
    {
        $_SERVER['TODAY'] = '2019-05-20 09:30:01'; // Monday, May 20, 2019
        // $date = new \DateTime($_SERVER['TODAY']);
        // $interval = new \DateInterval('P1D');
        $period = 'P1D';

        $data = [
            'timestamp' => $_SERVER['TODAY'],
            'timeinterval' => $period,
            'open' => rand(100,1000),
            'high' => rand(100,1000),
            'low'  => rand(100,1000),
            'close' => rand(100,1000),
            'volume' => rand(100,1000),
        ];

        $this->SUT->saveQuote('BKS', $data);
    }

    protected function tearDown(): void
    {

    }

}