<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\OHLCV_Yahoo;

class OHLCV_YahooTest extends KernelTestCase
{
    private $SUT;

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
        // var_dump($container->has('test.service_container')); exit();

        // $this->SUT = $container->get('app.exchange.nyse');
        $this->SUT = $container->get(OHLCV_Yahoo::class);
        // $this->doctrine = $container->get('doctrine');
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Testing OHLCV_Yahoo');
    	$this->assertTrue(true);
    }

    /**
     * test downloadHistory
     */
    public function test10()
    {
        $exchanges = ['NYSE', 'NASDAQ'];
        

    }


    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }

}