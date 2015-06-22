<?php

namespace Icicle\Tests\Psr7Bridge;

use Exception;
use Icicle\Promise\PromiseInterface;
use Icicle\Loop;
use Icicle\Psr7Bridge\Stream;
use Icicle\Socket\Stream\WritableStream;
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
        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->will(function () use (&$pending) {
            if ($pending) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$pending) {
                    $pending = false;
                });
            }
            return $pending;
        });
        $promise->getResult()->willReturn('ABCDEFGHIJ');
        $promise->isRejected()->willReturn(false);

        $readableStream = $this->prophesize(ReadableStreamInterface::class);
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

    public function testReadThrowsExceptionWhenLoopIsEmptyWithoutResolvingPromise()
    {
        /** @var ObjectProphecy|ReadableStreamInterface $readableStream */
        $readableStream = $this->prophesize(ReadableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(true);

        $readableStream->read(10)->willReturn($promise->reveal());

        $stream = new Stream($readableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->read(10);
    }

    public function testWriteSendsDataToAsyncStream()
    {
        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->will(function () use (&$pending) {
            if ($pending) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$pending) {
                    $pending = false;
                });
            }
            return $pending;
        });

        $promise->getResult()->willReturn(10);
        $promise->isRejected()->willReturn(false);

        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);

        $writableStream->write('ABCDEFGHIJ')->willReturn($promise->reveal());

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

    public function testWriteThrowsExceptionWhenLoopIsEmptyWithoutResolvingPromise()
    {
        /** @var ObjectProphecy|WritableStreamInterface $writableStream */
        $writableStream = $this->prophesize(WritableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(true);

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

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->will(function () use (&$pending) {
            if ($pending) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$pending) {
                    $pending = false;
                });
            }
            return $pending;
        });

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

    public function testSeekThrowsExceptionWhenLoopIsEmptyWithoutResolvingPromise()
    {
        /** @var ObjectProphecy|SeekableStreamInterface $seekableStream */
        $seekableStream = $this->prophesize(SeekableStreamInterface::class);

        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $promise->isPending()->willReturn(true);

        $seekableStream->seek(99, SEEK_SET)->willReturn($promise->reveal());

        $stream = new Stream($seekableStream->reveal());
        $this->setExpectedException(RuntimeException::class);
        $stream->seek(99);
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
        $wrapped = $this->prophesize(WritableStream::class);
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
        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->will(function () use (&$pending) {
            if ($pending) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$pending) {
                    $pending = false;
                });
            }
            return $pending;
        });
        $promise->getResult()->willReturn('ABCDEFGHIJ');
        $promise->isRejected()->willReturn(false);

        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->willReturn($promise->reveal());
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$readable) {
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
        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->will(function () use (&$pending) {
            if ($pending) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$pending) {
                    $pending = false;
                });
            }
            return $pending;
        });
        $promise->getResult()->willReturn('ABCDEFGHIJ');
        $promise->isRejected()->willReturn(false);

        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->willReturn($promise->reveal());
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$readable) {
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
        /** @var ObjectProphecy|PromiseInterface $promise */
        $promise = $this->prophesize(PromiseInterface::class);
        $pending = true;
        $promise->isPending()->willReturn(false);
        $promise->getResult()->willReturn(new Exception());
        $promise->isRejected()->willReturn(true);

        $readableStream = $this->prophesize(ReadableStreamInterface::class);
        $readableStream->read(stream::CHUNK_SIZE)->willReturn($promise->reveal());
        $readable = true;
        $readableStream->isReadable()->will(function () use (&$readable) {
            if ($readable) {
                // Schedule a function to simulate an event that resolves the promise.
                Loop\schedule(function () use (&$readable) {
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
