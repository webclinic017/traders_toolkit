<?php

namespace App\Tests\PriceHistory;

use PHPUnit\Framework\TestCase;

class PriceHistoryTest extends TestCase
{
	protected function setUp(): void
    {
        // $this->stack = [];
    }

    public function testIntro()
    {
    	fwrite(STDOUT, 'Test');
    	$this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        // fwrite(STDOUT, __METHOD__ . "\n");
    }
}