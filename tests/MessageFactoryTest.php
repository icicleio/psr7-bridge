<?php

namespace Icicle\Tests\Psr7Bridge;

use Icicle\Http\Message\Request as IcicleRequest;
use Icicle\Http\Message\Uri as IcicleUri;
use Icicle\Psr7Bridge\MessageFactory;
use Icicle\Stream\ReadableStreamInterface;
use PHPUnit_Framework_TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface as PsrStream;
use Psr\Http\Message\UriInterface;

class MessageFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MessageFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new MessageFactory();
    }

    public function testCreateUri()
    {
        $stringUri = "http://johndoe:PWD@www.website.net/some/path/";
        $icicleUri = new IcicleUri($stringUri);
        $psrUri = $this->factory->createUri($icicleUri);
        $this->assertInstanceOf(UriInterface::class, $psrUri);
        $this->assertEquals($stringUri, $psrUri->__toString());
    }

    public function testCreateRequest()
    {
        $stringUri = "http://johndoe:PWD@www.website.net/some/path/";
        $headers = [
            'Host' => ['www.website.net'],
            'X-Forwarded-For' => ['100.100.100.100,192.168.1.123'],
            'Connection' => ['keep-alive'],
            'Cache-Control' => ['max-age=0']
        ];
        $icicleStream = $this->prophesize(ReadableStreamInterface::class);
        $icicleRequest = new IcicleRequest("GET", new IcicleUri($stringUri), $headers, $icicleStream->reveal());
        $psrRequest = $this->factory->createRequest($icicleRequest);

        $this->assertInstanceOf(RequestInterface::class, $psrRequest);
        $this->assertEquals("GET", $psrRequest->getMethod());
        $this->assertEquals($headers, $psrRequest->getHeaders());
        $this->assertInstanceOf(PsrStream::class, $psrRequest->getBody());
    }
}
