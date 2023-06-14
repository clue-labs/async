<?php

namespace React\Tests\Async;

use React;
use React\EventLoop\Loop;

class DelayTest extends TestCase
{
    /** @return void */
    public function testDelayBlocksForGivenPeriod()
    {
        $time = microtime(true);
        React\Async\delay(0.02);
        $time = microtime(true) - $time;

        if (method_exists($this, 'assertEqualsWithDelta')) {
            // PHPUnit 7+
            $this->assertEqualsWithDelta(0.02, $time, 0.01);
        } else {
            // @phpstan-ignore-next-line legacy PHPUnit
            $this->assertEquals(0.02, $time, '', 0.01);
        }
    }

    /** @return void */
    public function testDelaySmallPeriodBlocksForCloseToZeroSeconds()
    {
        $time = microtime(true);
        React\Async\delay(0.000001);
        $time = microtime(true) - $time;

        $this->assertLessThan(0.01, $time);
    }

    /** @return void */
    public function testDelayNegativePeriodBlocksForCloseToZeroSeconds()
    {
        $time = microtime(true);
        React\Async\delay(-1);
        $time = microtime(true) - $time;

        $this->assertLessThan(0.01, $time);
    }

    /** @return void */
    public function testDelayRunsOtherEventsWhileWaiting()
    {
        $buffer = 'a';
        Loop::addTimer(0.001, function () use (&$buffer) {
            $buffer .= 'c';
        });
        $buffer .= 'b';
        React\Async\delay(0.002);
        $buffer .= 'd';

        $this->assertEquals('abcd', $buffer);
    }
}
