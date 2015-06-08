<?php

namespace Icicle\Tests\Psr7Bridge;

use Icicle\Promise\PromiseInterface;
use Icicle\Loop;
use Icicle\Psr7Bridge\Stream\Stream;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\WritableStreamInterface;
use PHPUnit_Framework_TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

class StreamTest extends PHPUnit_Framework_TestCase
{
    public function testReadReturnsDataFromAsyncStream()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn('ABCDEFGHIJ');
        $promise->isRejected()->willReturn(false);

        $readableStream->read(10)->willReturn($promise->reveal());

        $stream = new Stream($readableStream->reveal());
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
        /** @var ObjectProphecy|WritableStreamInterface $readableStream */
        $readableStream = $this->prophesize(WritableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(10);
        $promise->isRejected()->willReturn(false);

        $readableStream->write('ABCDEFGHIJ')->willReturn($promise->reveal());

        $stream = new Stream($readableStream->reveal());
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
