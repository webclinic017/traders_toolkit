<?php

namespace App\Tests\PriceHistory;

// use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\PriceHistory\Exchange_NYSE;
use App\Service\Holidays;
// use App\Service\Yasumi;

class PriceHistoryTest extends KernelTestCase
{
    private $SUT;

    /**
     * Details on how to access services in tests:
     * https://symfony.com/blog/new-in-symfony-4-1-simpler-service-testing
     */
	protected function setUp(): void
    {
        self::bootKernel();
        $container = self::$container;
        // $container = self::$kernel->getContainer();
        // $container = self::$container->get('test.service_container');
        // var_dump($container->has('test.service_container')); exit();

        $holidaysService = $container->get(Holidays::class);
        // exit();
        $this->SUT = new Exchange_NYSE($holidaysService);
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Test');
    	$this->assertTrue(true);
    }

    public function test10()
    {
        $date = new \DateTime('next Saturday');
        $this->assertFalse($this->SUT->isTradingDay($date));
        
        $date = new \DateTime('next Monday');
        $this->assertTrue($this->SUT->isTradingDay($date));
    }

    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }
}