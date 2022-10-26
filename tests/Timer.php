<?php

namespace React\Tests\Async;

class Timer
{
    /** @var TestCase */
    private $testCase;

    /** @var float */
    private $start;

    /** @var float */
    private $stop;

    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /** @return void */
    public function start()
    {
        $this->start = microtime(true);
    }

    /** @return void */
    public function stop()
    {
        $this->stop = microtime(true);
    }

    /** @return float */
    public function getInterval()
    {
        return $this->stop - $this->start;
    }

    /**
     * @param float $milliseconds
     * @return void
     */
    public function assertLessThan($milliseconds)
    {
        $this->testCase->assertLessThan($milliseconds, $this->getInterval());
    }

    /**
     * @param float $milliseconds
     * @return void
     */
    public function assertGreaterThan($milliseconds)
    {
        $this->testCase->assertGreaterThan($milliseconds, $this->getInterval());
    }

    /**
     * @param float $minMs
     * @param float $maxMs
     * @return void
     */
    public function assertInRange($minMs, $maxMs)
    {
        $this->assertGreaterThan($minMs);
        $this->assertLessThan($maxMs);
    }
}
