<?php

namespace React\Tests\Async;

use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /** @return callable */
    protected function expectCallableOnce()
    {
        $mock = $this->createCallableMock();
        $mock->expects($this->once())->method('__invoke');
        assert(is_callable($mock));

        return $mock;
    }

    /**
     * @param mixed $value
     * @return callable
     */
    protected function expectCallableOnceWith($value)
    {
        $mock = $this->createCallableMock();
        $mock->expects($this->once())->method('__invoke')->with($value);
        assert(is_callable($mock));

        return $mock;
    }

    /** @return callable */
    protected function expectCallableNever()
    {
        $mock = $this->createCallableMock();
        $mock->expects($this->never())->method('__invoke');
        assert(is_callable($mock));

        return $mock;
    }

    /** @return \PHPUnit\Framework\MockObject\MockObject */
    protected function createCallableMock()
    {
        if (method_exists('PHPUnit\Framework\MockObject\MockBuilder', 'addMethods')) {
            // @phpstan-ignore-next-line PHPUnit 9+
            return $this->getMockBuilder('stdClass')->addMethods(array('__invoke'))->getMock();
        } else {
            // legacy PHPUnit 4 - PHPUnit 8
            return $this->getMockBuilder('stdClass')->setMethods(array('__invoke'))->getMock();
        }
    }
}
