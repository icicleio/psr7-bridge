<?php
namespace Icicle\Psr7Adaptor\Message;

use Icicle\Http\Message\MessageInterface;
use Icicle\Psr7Adaptor\Stream\Stream;
use Psr\Http\Message\StreamInterface;

abstract class Message implements \Psr\Http\Message\MessageInterface
{
    /**
     * @var \Icicle\Http\Message\MessageInterface
     */
    private $message;

    /**
     * @var \Psr\Http\Message\StreamInterface
     */
    private $body;

    /**
     * @param   \Icicle\Http\Message\MessageInterface $message
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
        $this->body = new Stream($message->getBody());
    }

    // @todo Implement other interface methods.

    /**
     * @inheritdoc
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $stream)
    {
        $new = clone $this;
        $new->body = $stream;
        return $new;
    }
}
