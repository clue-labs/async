<?php

namespace React\Tests\Async;

use PHPUnit\Framework\Attributes\DataProvider;
use React\Async\FiberMap;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Async\async;
use function React\Async\await;
use function React\Promise\reject;
use function React\Promise\resolve;

class AwaitTest extends TestCase
{
    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsExceptionWhenPromiseIsRejectedWithException(callable $await)
    {
        $promise = new Promise(function () {
            throw new \Exception('test');
        });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('test');
        $await($promise);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsExceptionWithoutRunningLoop(callable $await)
    {
        $now = true;
        Loop::futureTick(function () use (&$now) {
            $now = false;
        });

        $promise = new Promise(function () {
            throw new \Exception('test');
        });

        try {
            $await($promise);
        } catch (\Exception $e) {
            $this->assertTrue($now);
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsExceptionImmediatelyWhenPromiseIsRejected(callable $await)
    {
        $deferred = new Deferred();

        $ticks = 0;
        Loop::futureTick(function () use (&$ticks) {
            ++$ticks;
            Loop::futureTick(function () use (&$ticks) {
                ++$ticks;
            });
        });

        Loop::futureTick(fn() => $deferred->reject(new \RuntimeException()));

        try {
            $await($deferred->promise());
        } catch (\RuntimeException $e) {
            $this->assertEquals(1, $ticks);
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitAsyncThrowsExceptionImmediatelyWhenPromiseIsRejected(callable $await)
    {
        $deferred = new Deferred();

        $ticks = 0;
        Loop::futureTick(function () use (&$ticks) {
            ++$ticks;
            Loop::futureTick(function () use (&$ticks) {
                ++$ticks;
            });
        });

        Loop::futureTick(fn() => $deferred->reject(new \RuntimeException()));

        $promise = async(function () use ($deferred, $await) {
            return $await($deferred->promise());
        })();

        try {
            $await($promise);
        } catch (\RuntimeException $e) {
            $this->assertEquals(1, $ticks);
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsExceptionImmediatelyInCustomFiberWhenPromiseIsRejected(callable $await)
    {
        $fiber = new \Fiber(function () use ($await) {
            $promise = new Promise(function ($resolve) {
                throw new \RuntimeException('Test');
            });

            return $await($promise);
        });

        try {
            $fiber->start();
        } catch (\RuntimeException $e) {
            $this->assertTrue($fiber->isTerminated());
            $this->assertEquals('Test', $e->getMessage());
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsUnexpectedValueExceptionWhenPromiseIsRejectedWithFalse(callable $await)
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        $promise = new Promise(function ($_, $reject) {
            $reject(false);
        });

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Promise rejected with unexpected value of type bool');
        $await($promise);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsUnexpectedValueExceptionWhenPromiseIsRejectedWithNull(callable $await)
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        $promise = new Promise(function ($_, $reject) {
            $reject(null);
        });

        try {
            $await($promise);
        } catch (\UnexpectedValueException $exception) {
            $this->assertInstanceOf(\UnexpectedValueException::class, $exception);
            $this->assertEquals('Promise rejected with unexpected value of type NULL', $exception->getMessage());
            $this->assertEquals(0, $exception->getCode());
            $this->assertNull($exception->getPrevious());
            $this->assertNotEquals('', $exception->getTraceAsString());
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitThrowsErrorWhenPromiseIsRejectedWithError(callable $await)
    {
        $promise = new Promise(function ($_, $reject) {
            throw new \Error('Test', 42);
        });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Test');
        $this->expectExceptionCode(42);
        $await($promise);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitReturnsValueWhenPromiseIsFullfilled(callable $await)
    {
        $promise = new Promise(function ($resolve) {
            $resolve(42);
        });

        $this->assertEquals(42, $await($promise));
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitReturnsValueImmediatelyWithoutRunningLoop(callable $await)
    {
        $now = true;
        Loop::futureTick(function () use (&$now) {
            $now = false;
        });

        $promise = new Promise(function ($resolve) {
            $resolve(42);
        });

        $this->assertEquals(42, $await($promise));
        $this->assertTrue($now);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitReturnsValueImmediatelyWhenPromiseIsFulfilled(callable $await)
    {
        $deferred = new Deferred();

        $ticks = 0;
        Loop::futureTick(function () use (&$ticks) {
            ++$ticks;
            Loop::futureTick(function () use (&$ticks) {
                ++$ticks;
            });
        });

        Loop::futureTick(fn() => $deferred->resolve(42));

        $this->assertEquals(42, $await($deferred->promise()));
        $this->assertEquals(1, $ticks);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitAsyncReturnsValueImmediatelyWhenPromiseIsFulfilled(callable $await)
    {
        $deferred = new Deferred();

        $ticks = 0;
        Loop::futureTick(function () use (&$ticks) {
            ++$ticks;
            Loop::futureTick(function () use (&$ticks) {
                ++$ticks;
            });
        });

        Loop::futureTick(fn() => $deferred->resolve(42));

        $promise = async(function () use ($deferred, $await) {
            return $await($deferred->promise());
        })();

        $this->assertEquals(42, $await($promise));
        $this->assertEquals(1, $ticks);
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitReturnsValueImmediatelyInCustomFiberWhenPromiseIsFulfilled(callable $await)
    {
        $fiber = new \Fiber(function () use ($await) {
            $promise = new Promise(function ($resolve) {
                $resolve(42);
            });

            return $await($promise);
        });

        $fiber->start();

        $this->assertTrue($fiber->isTerminated());
        $this->assertEquals(42, $fiber->getReturn());
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitShouldNotCreateAnyGarbageReferencesForResolvedPromise(callable $await)
    {
        if (class_exists('React\Promise\When')) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API');
        }

        gc_collect_cycles();

        $promise = new Promise(function ($resolve) {
            $resolve(42);
        });
        $await($promise);
        unset($promise);

        $this->assertEquals(0, gc_collect_cycles());
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitShouldNotCreateAnyGarbageReferencesForRejectedPromise(callable $await)
    {
        if (class_exists('React\Promise\When')) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API');
        }

        gc_collect_cycles();

        $promise = new Promise(function () {
            throw new \RuntimeException();
        });
        try {
            $await($promise);
        } catch (\Exception $e) {
            // no-op
        }
        unset($promise, $e);

        $this->assertEquals(0, gc_collect_cycles());
    }

    #[DataProvider('provideAwaiters')]
    public function testAwaitShouldNotCreateAnyGarbageReferencesForPromiseRejectedWithNullValue(callable $await)
    {
        if (!interface_exists('React\Promise\CancellablePromiseInterface')) {
            $this->markTestSkipped('Promises must be rejected with a \Throwable instance since Promise v3');
        }

        if (class_exists('React\Promise\When')) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API');
        }

        gc_collect_cycles();

        $promise = new Promise(function ($_, $reject) {
            $reject(null);
        });
        try {
            $await($promise);
        } catch (\Exception $e) {
            // no-op
        }
        unset($promise, $e);

        $this->assertEquals(0, gc_collect_cycles());
    }

    #[DataProvider('provideAwaiters')]
    public function testAlreadyFulfilledPromiseShouldNotSuspendFiber(callable $await)
    {
        for ($i = 0; $i < 6; $i++) {
            $this->assertSame($i, $await(resolve($i)));
        }
    }

    #[DataProvider('provideAwaiters')]
    public function testNestedAwaits(callable $await)
    {
        $this->assertTrue($await(new Promise(function ($resolve) use ($await) {
            $resolve($await(new Promise(function ($resolve) use ($await) {
                $resolve($await(new Promise(function ($resolve) use ($await) {
                    $resolve($await(new Promise(function ($resolve) use ($await) {
                        $resolve($await(new Promise(function ($resolve) use ($await) {
                            Loop::addTimer(0.01, function () use ($resolve) {
                                $resolve(true);
                            });
                        })));
                    })));
                })));
            })));
        })));
    }

    #[DataProvider('provideAwaiters')]
    public function testResolvedPromisesShouldBeDetached(callable $await)
    {
        $await(async(function () use ($await): int {
            $fiber = \Fiber::getCurrent();
            $await(new Promise(function ($resolve) {
                Loop::addTimer(0.01, fn() => $resolve(null));
            }));
            $this->assertNull(FiberMap::getPromise($fiber));

            return time();
        })());
    }

    #[DataProvider('provideAwaiters')]
    public function testRejectedPromisesShouldBeDetached(callable $await)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Boom!');

        $await(async(function () use ($await): int {
            $fiber = \Fiber::getCurrent();
            try {
                $await(reject(new \Exception('Boom!')));
            } catch (\Throwable $throwable) {
                throw $throwable;
            } finally {
                $this->assertNull(FiberMap::getPromise($fiber));
            }

            return time();
        })());
    }

    public static function provideAwaiters(): iterable
    {
        yield 'await' => [static fn (PromiseInterface $promise): mixed => await($promise)];
        yield 'async' => [static fn (PromiseInterface $promise): mixed => await(async(static fn(): mixed => $promise)())];
    }
}
