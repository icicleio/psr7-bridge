<?php
namespace Icicle\Psr7Adaptor\Stream;

use Icicle\Http\Exception\LogicException;
use Icicle\Loop;
use Icicle\Stream\ReadableStreamInterface;
use Icicle\Stream\WritableStreamInterface;

class Stream implements \Psr\Http\Message\StreamInterface
{
    /**
     * @var \Icicle\Stream\ReadableStreamInterface
     */
    private $stream;

    public function __construct(ReadableStreamInterface $stream)
    {
        $this->stream = $stream;
    }

    // @todo Implement other interface methods.

    /**
     * @inheritdoc
     */
    public function read($length)
    {
        $promise = $this->stream->read($length);

        while ($promise->isPending()) {
            Loop\tick(true);
        }

        $result = $promise->getResult();

        if ($promise->isRejected()) {
            throw $result; // May need to wrap Exception to be PSR-7 compliant?
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function write($data)
    {
        if (!$this->stream instanceof WritableStreamInterface) {
            throw new LogicException('Stream is not writable.'); // Can this throw? What else could it do?
        }

        $promise = $this->stream->write($data);

        while ($promise->isPending()) {
            Loop\tick(true);
        }

        $result = $promise->getResult();

        if ($promise->isRejected()) {
            throw $result; // May need to wrap Exception to be PSR-7 compliant?
        }

        return $result;
    }
}
