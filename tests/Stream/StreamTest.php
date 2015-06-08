<?php

namespace Icicle\Tests\Psr7Bridge\Stream;

use Icicle\Promise\PromiseInterface;
use Icicle\Loop;
use Icicle\Psr7Bridge\Stream\Stream;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\WritableStreamInterface;
use Icicle\Tests\Psr7Bridge\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

class StreamTest extends TestCase
{
    public function testReadReturnsDataFromAsyncStream()
    {
        $promise = $this->getMock(PromiseInterface::class);

        $promise
            ->expects($this->exactly(2))
            ->method('isPending')
            ->will($this->returnCallback(function () {
                static $pending = true;
                if ($pending) {
                    // Schedule a function to simulate an event that resolves the promise.
                    Loop\schedule(function () use (&$pending) {
                        $pending = false;
                    });
                }
                return $pending;
            }));

        $promise
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue('ABCDEFGHIJ'));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ReadableStreamInterface $readableStream */
        $readableStream = $this->getMock(ReadableStreamInterface::class);

        $readableStream
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue($promise));

        $stream = new Stream($readableStream);
        $result = $stream->read(10);
        $this->assertEquals('ABCDEFGHIJ', $result);
    }

    public function testReadThrowsExceptionWhenStreamIsNotReadable()
    {
        /** @var ObjectProphecy|WritableStreamInterface $notReadableStream */
        $notReadableStream = $this->prophesize(WritableStreamInterface::class);

        $stream = new Stream($notReadableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->read(10);
    }

    public function testWriteSendsDataToAsyncStream()
    {
        $promise = $this->getMock(PromiseInterface::class);

        $promise
            ->expects($this->exactly(2))
            ->method('isPending')
            ->will($this->returnCallback(function () {
                static $pending = true;
                if ($pending) {
                    // Schedule a function to simulate an event that resolves the promise.
                    Loop\schedule(function () use (&$pending) {
                        $pending = false;
                    });
                }
                return $pending;
            }));

        $promise
            ->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue(10));

        /** @var \PHPUnit_Framework_MockObject_MockObject|WritableStreamInterface $writableStream */
        $writableStream = $this->getMock(WritableStreamInterface::class);

        $writableStream
            ->expects($this->once())
            ->method('write')
            ->will($this->returnValue($promise));

        $stream = new Stream($writableStream);
        $result = $stream->write('ABCDEFGHIJ');
        $this->assertEquals(10, $result);
    }

    public function testWriteThrowsExceptionWhenStreamIsNotWritable()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $notWritableStream */
        $notWritableStream = $this->prophesize(ReadableStreamInterface::class);

        $stream = new Stream($notWritableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->write("XYZ");
    }
}
