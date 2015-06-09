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
            throw new RuntimeException('Error writing to stream', 0, $result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {

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
        if ($this->stream instanceof SeekableStreamInterface) {
            return $this->stream->getLength();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if ($this->stream instanceof SeekableStreamInterface) {
            return $this->stream->tell();
        }
        return null;
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

        $promise = $this->stream->seek($offset, $whence);

        while ($promise->isPending()) {
            Loop\tick(true);
        }

        if ($promise->isRejected()) {
            $result = $promise->getResult();
            throw new RuntimeException('Error seeking stream', 0, $result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
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
        if (null === $key) {
            return [];
        }
        return null;
    }
}
