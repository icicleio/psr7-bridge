<?php
namespace Icicle\Psr7Bridge\Stream;

use Icicle\Loop;
use Icicle\Socket\Socket;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\SeekableStreamInterface;
use Icicle\Stream\StreamInterface;
use Icicle\Stream\WritableStreamInterface;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;
use RuntimeException;

class Stream implements PsrStreamInterface
{
    const CHUNK_SIZE = 8192;

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
        try {
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (null !== $this->stream) {
            $this->stream->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $stream = $this->stream;
        $this->stream = null;

        if ($stream instanceof Socket) {
            return $stream->getResource();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if ($this->isSeekable()) {
            return $this->stream->getLength();
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable');
        }
        return $this->stream->tell();
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return $this->isReadable() && $this->stream->isReadable();
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
            if (Loop\isEmpty()) {
                throw new RuntimeException('Loop emptied without resolving the promise');
            }
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
        $contents = '';

        while (!$this->eof()) {
            $contents .= $this->read(self::CHUNK_SIZE);
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if ($this->stream instanceof Socket) {
            $resource = $this->stream->getResource();

            if (get_resource_type($resource) === 'stream') {
                $metadata = stream_get_meta_data($resource);
                if (null === $key) {
                    return $metadata;
                }
                return isset($metadata[$key]) ? $metadata[$key] : null;
            }
        }

        if (null === $key) {
            return [];
        }
        return null;
    }
}
