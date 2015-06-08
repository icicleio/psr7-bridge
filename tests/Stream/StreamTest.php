<?php

namespace Icicle\Tests\Psr7Bridge;

use Exception;
use Icicle\Promise\PromiseInterface;
use Icicle\Loop;
use Icicle\Psr7Bridge\Stream\Stream;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\SeekableStreamInterface;
use Icicle\Stream\StreamInterface;
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

    public function testReadThrowsExceptionWhenReadFails()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(new Exception());
        $promise->isRejected()->willReturn(true);

        $readableStream->read(10)->willReturn($promise->reveal());

        $stream = new Stream($readableStream->reveal());
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

    public function testWriteThrowsExceptionWhenWriteFails()
    {
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(new Exception());
        $promise->isRejected()->willReturn(true);

        $writableStream->write('ABCDEFGHIJ')->willReturn($promise->reveal());

        $stream = new Stream($writableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->write('ABCDEFGHIJ');
    }

    public function testCloseClosesStream()
    {
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);
        $writableStream->close()->shouldBeCalled();
        $stream = new Stream($writableStream->reveal());
        $stream->close();
    }

    public function testGetSizeReturnsStreamLength()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);
        $seekableStream->getLength()->willReturn(250);
        $stream = new Stream($seekableStream->reveal());
        $this->assertEquals(250, $stream->getSize());
    }

    public function testGetSizeReturnsNullWhenStreamIsNotSeekable()
    {
        /** @var ObjectProphecy|StreamInterface $nonSeekableStream */
        $nonSeekableStream = $this->prophesize(StreamInterface::class);
        $stream = new Stream($nonSeekableStream->reveal());
        $this->assertNull($stream->getSize());
    }

    public function tellReturnsPointerPosition()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);
        $seekableStream->tell()->willReturn(125);
        $stream = new Stream($seekableStream->reveal());
        $this->assertEquals(125, $stream->tell());
    }

    public function testTellReturnsNullWhenStreamIsNotSeekable()
    {
        /** @var ObjectProphecy|StreamInterface $nonSeekableStream */
        $nonSeekableStream = $this->prophesize(StreamInterface::class);
        $stream = new Stream($nonSeekableStream->reveal());
        $this->assertNull($stream->tell());
    }

    public function testSeekReturnsSeeksAsyncStream()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(null);
        $promise->isRejected()->willReturn(false);

        $seekableStream->seek(10, SEEK_SET)->willReturn($promise->reveal());

        $stream = new Stream($seekableStream->reveal());
        $stream->seek(10);
    }

    public function testSeekThrowsExceptionWhenStreamIsNotSeekable()
    {
        /** @var ObjectProphecy|StreamInterface $nonSeekableStream */
        $nonSeekableStream = $this->prophesize(StreamInterface::class);

        $stream = new Stream($nonSeekableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->seek(110);
    }

    public function testSeekThrowsExceptionWhenSeekFails()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(new Exception());
        $promise->isRejected()->willReturn(true);

        $seekableStream->seek(110, SEEK_SET)->willReturn($promise->reveal());

        $stream = new Stream($seekableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->seek(110);
    }

    public function testRewindUsesSeeksToResetPointer()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(null);
        $promise->isRejected()->willReturn(false);

        $seekableStream->seek(0, SEEK_SET)->willReturn($promise->reveal());

        $stream = new Stream($seekableStream->reveal());
        $stream->rewind(0);
    }

    public function testGetMetadataReturnsEmptyArrayIfNoKeyIsGiven()
    {
        /** @var ObjectProphecy|WritableStreamInterface $readableStream */
        $readableStream = $this->prophesize(WritableStreamInterface::class);
        $stream = new Stream($readableStream->reveal());
        $this->assertEquals([], $stream->getMetadata());
    }

    public function testGetMetadataReturnsNullArrayIfKeyIsGiven()
    {
        /** @var ObjectProphecy|WritableStreamInterface $readableStream */
        $readableStream = $this->prophesize(WritableStreamInterface::class);
        $stream = new Stream($readableStream->reveal());
        $this->assertEquals(null, $stream->getMetadata('uri'));
    }
}
