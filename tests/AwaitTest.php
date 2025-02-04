<?php

namespace React\Tests\Async;

use React;
use React\EventLoop\Loop;
use React\Promise\Promise;

class AwaitTest extends TestCase
{
    public function testAwaitThrowsExceptionWhenPromiseIsRejectedWithException()
    {
        $promise = new Promise(function () {
            throw new \Exception('test');
        });

        $this->setExpectedException('Exception', 'test');
        React\Async\await($promise);
    }

    public function testAwaitThrowsUnexpectedValueExceptionWhenPromiseIsRejectedWithFalse()
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        $promise = new Promise(function ($_, $reject) {
            $reject(false);
        });

        $this->setExpectedException('UnexpectedValueException', 'Promise rejected with unexpected value of type bool');
        React\Async\await($promise);
    }

    public function testAwaitThrowsUnexpectedValueExceptionWhenPromiseIsRejectedWithNull()
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        $promise = new Promise(function ($_, $reject) {
            $reject(null);
        });

        $this->setExpectedException('UnexpectedValueException', 'Promise rejected with unexpected value of type NULL');
        React\Async\await($promise);
    }

    /**
     * @requires PHP 7
     */
    public function testAwaitThrowsErrorWhenPromiseIsRejectedWithError()
    {
        $promise = new Promise(function ($_, $reject) {
            throw new \Error('Test', 42);
        });

        $this->setExpectedException('Error', 'Test', 42);
        React\Async\await($promise);
    }

    public function testAwaitReturnsValueWhenPromiseIsFullfilled()
    {
        $promise = new Promise(function ($resolve) {
            $resolve(42);
        });

        $this->assertEquals(42, React\Async\await($promise));
    }

    public function testAwaitReturnsValueWhenPromiseIsFulfilledEvenWhenOtherTimerStopsLoop()
    {
        $promise = new Promise(function ($resolve) {
            Loop::addTimer(0.02, function () use ($resolve) {
                $resolve(2);
            });
        });
        Loop::addTimer(0.01, function () {
            Loop::stop();
        });

        $this->assertEquals(2, React\Async\await($promise));
    }

    public function testAwaitShouldNotCreateAnyGarbageReferencesForResolvedPromise()
    {
        if (class_exists('React\Promise\When') && PHP_VERSION_ID >= 50400) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API with PHP 5.4+');
        }

        gc_collect_cycles();

        $promise = new Promise(function ($resolve) {
            $resolve(42);
        });
        React\Async\await($promise);
        unset($promise);

        $this->assertEquals(0, gc_collect_cycles());
    }

    public function testAwaitShouldNotCreateAnyGarbageReferencesForRejectedPromise()
    {
        if (class_exists('React\Promise\When')) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API');
        }

        gc_collect_cycles();

        $promise = new Promise(function () {
            throw new \RuntimeException();
        });
        try {
            React\Async\await($promise);
        } catch (\Exception $e) {
            // no-op
        }
        unset($promise, $e);

        $this->assertEquals(0, gc_collect_cycles());
    }

    public function testAwaitShouldNotCreateAnyGarbageReferencesForPromiseRejectedWithNullValue()
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        if (class_exists('React\Promise\When') && PHP_VERSION_ID >= 50400) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API with PHP 5.4+');
        }

        gc_collect_cycles();

        $promise = new Promise(function ($_, $reject) {
            $reject(null);
        });
        try {
            React\Async\await($promise);
        } catch (\Exception $e) {
            // no-op
        }
        unset($promise, $e);

        $this->assertEquals(0, gc_collect_cycles());
    }

    public function setExpectedException($exception, $exceptionMessage = '', $exceptionCode = null)
    {
        if (method_exists($this, 'expectException')) {
            // PHPUnit 5+
            $this->expectException($exception);
            if ($exceptionMessage !== '') {
                $this->expectExceptionMessage($exceptionMessage);
            }
            if ($exceptionCode !== null) {
                $this->expectExceptionCode($exceptionCode);
            }
        } else {
            // legacy PHPUnit 4
            parent::setExpectedException($exception, $exceptionMessage, $exceptionCode);
        }
    }
}
