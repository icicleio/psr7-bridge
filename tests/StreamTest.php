<?php

namespace Icicle\Tests\Psr7Bridge;

use Exception;
use Icicle\Loop;
use Icicle\Psr7Bridge\Stream;
use Icicle\Stream\Pipe\WritablePipe;
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
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(10)->will(function () {
            yield 'ABCDEFGHIJ';
        });

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
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(10)->will(function () {
            throw new Exception();
            yield;
        });

        $stream = new Stream($readableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->read(10);
    }

    public function testWriteSendsDataToAsyncStream()
    {
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);
        $writableStream->write('ABCDEFGHIJ')->will(function ($args) {
            yield strlen($args[0]);
        });

        $stream = new Stream($writableStream->reveal());
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
        $writableStream->write('ABCDEFGHIJ')->will(function ($data) {
            yield strlen($data);
        });

        $stream = new Stream($writableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->write('ABCDEFGHIJ');
    }

    public function testWriteThrowsExceptionWhenLoopIsEmptyWithoutResolvingPromise()
    {
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);
        $writableStream->write('ABCDEFGHIJ')->will(function ($data) {
            yield strlen($data);
        });

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

    public function testTellReturnsPointerPosition()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);
        $seekableStream->tell()->willReturn(125);
        $stream = new Stream($seekableStream->reveal());
        $this->assertEquals(125, $stream->tell());
    }

    public function testTellThrowsExceptionWhenStreamIsNotSeekable()
    {
        /** @var ObjectProphecy|StreamInterface $nonSeekableStream */
        $nonSeekableStream = $this->prophesize(StreamInterface::class);
        $stream = new Stream($nonSeekableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->tell();
    }

    public function testSeekReturnsSeeksAsyncStream()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);
        $seekableStream->seek(10, SEEK_SET)->will(function ($args) {
            yield $args[0];
        });

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
        $seekableStream->seek(110, SEEK_SET)->will(function ($args) {
            throw new Exception();
            yield;
        });

        $stream = new Stream($seekableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->seek(110);
    }

    public function testRewindUsesSeeksToResetPointer()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);

        $seekableStream->seek(0, SEEK_SET)->will(function ($args) {
            yield $args[0];
        });

        $stream = new Stream($seekableStream->reveal());
        $stream->rewind(0);
    }

    public function testGetMetadataReturnsEmptyArrayIfNoKeyIsGivenForNonSocketStream()
    {
        /** @var ObjectProphecy|WritableStreamInterface $readableStream */
        $readableStream = $this->prophesize(WritableStreamInterface::class);
        $stream = new Stream($readableStream->reveal());
        $this->assertEquals([], $stream->getMetadata());
    }

    public function testGetMetadataReturnsNullArrayIfKeyIsGivenForNonSocketStream()
    {
        /** @var ObjectProphecy|WritableStreamInterface $readableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);
        $stream = new Stream($writableStream->reveal());
        $this->assertEquals(null, $stream->getMetadata('uri'));
    }

    public function testReadingDetachedStreamThrowsException()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $stream = new Stream($readableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->detach();
        $stream->read(10);
    }

    public function testDetachReturnsResourceFromWrappedStream()
    {
        /** @var ObjectProphecy|WritableStream $wrapped */
        $wrapped = $this->prophesize(WritablePipe::class);
        $wrapped->getResource()->willReturn('RESOURCE'); // doesn't need to be real PHP resource for this test
        $stream = new Stream($wrapped->reveal());
        $this->assertEquals('RESOURCE', $stream->detach());
    }

    public function testEofReturnsFalseWhenStreamAndWrappedStreamAreReadable()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->isReadable()->willReturn(true);
        $stream = new Stream($readableStream->reveal());
        $this->assertFalse($stream->eof());
    }

    public function testEofReturnsFalseWhenStreamOrWrappedStreamAreNotReadable()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->isReadable()->willReturn(false);
        $stream = new Stream($readableStream->reveal());
        $this->assertTrue($stream->eof());
    }

    public function testGetContentsReadsUntilEof()
    {
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->will(function () {
            yield 'ABCDEFGHIJ';
        });
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\queue(function () use (&$readable) {
                    $readable = false;
                });
            }
            return $readable;
        });


        $stream = new Stream($readableStream->reveal());
        $result = $stream->getContents();
        $this->assertEquals('ABCDEFGHIJ', $result);
    }


    public function testToStringReadsUntilEof()
    {
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->will(function () {
            yield 'ABCDEFGHIJ';
        });
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\queue(function () use (&$readable) {
                    $readable = false;
                });
            }
            return $readable;
        });


        $stream = new Stream($readableStream->reveal());
        $result = $stream->getContents();
        $this->assertEquals('ABCDEFGHIJ', $result);
    }

    public function testToStringIgnoresExceptions()
    {
        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->will(function () {
            throw new Exception();
            yield;
        });
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\queue(function () use (&$readable) {
                    $readable = false;
                });
            }
            return $readable;
        });

        $stream = new Stream($readableStream->reveal());
        $result = $stream->__toString();
        $this->assertEquals('', $result);
    }

}
