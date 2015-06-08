<?php
namespace Icicle\Psr7Bridge\Stream;

use Icicle\Loop;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\SeekableStreamInterface;
use Icicle\Stream\StreamInterface;
use Icicle\Stream\WritableStreamInterface;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use RuntimeException;

class Stream implements PsrStreamInterface
{
    /**
     * @var \Icicle\Stream\StreamInterface
     */
    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    // @todo Implement other interface methods.

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable');
        }

        $promise = $this->stream->read($length);

        while ($promise->isPending()) {
            if (Loop\isEmpty()) {
                throw new RuntimeException('Loop emptied without resolving the promise');
            }

            Loop\tick(true);
        }

        $result = $promise->getResult();

        if ($promise->isRejected()) {
            throw new RuntimeException('Error reading from stream', 0, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable');
        }

        $promise = $this->stream->write($data);

        while ($promise->isPending()) {
            if (Loop\isEmpty()) {
                throw new RuntimeException('Loop emptied without resolving the promise');
            }

            Loop\tick(true);
        }

        $result = $promise->getResult();

        if ($promise->isRejected()) {
            new RuntimeException('Error writing to stream', 0, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        // TODO: Implement __toString() method.
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->stream->close();
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        // TODO: Implement detach() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        // TODO: Implement getSize() method.
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        // TODO: Implement tell() method.
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        // TODO: Implement eof() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return ($this->stream instanceof SeekableStreamInterface);
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }

        // TODO: Implement seek() method.
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        // TODO: Implement rewind() method.
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return ($this->stream instanceof WritableStreamInterface);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return ($this->stream instanceof ReadableStreamInterface);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        // TODO: Implement getContents() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }
}
