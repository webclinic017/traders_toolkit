<?php

namespace App\Tests\PriceHistory;

use PHPUnit\Framework\TestCase;
use App\PriceHistory\Exchange_NYSE;

class PriceHistoryTest extends TestCase
{
    private $SUT;

	protected function setUp(): void
    {
        $this->SUT = new Exchange_NYSE;
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Test');
    	$this->assertTrue(true);
    }

    public function test10()
    {
        // $date = new \DateTime('today');
        $date = new \DateTime('10-May-2019');
        fwrite( STDOUT, $this->SUT->isTradingDay($date)? 'true' : 'false' );
    }

    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }
}