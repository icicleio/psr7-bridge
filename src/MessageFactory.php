<?php

namespace Icicle\Psr7Bridge;

use Icicle\Http\Message\RequestInterface as IcicleRequest;
use Icicle\Http\Message\ResponseInterface as IcicleResponse;
use Icicle\Http\Message\UriInterface as IcicleUri;
use Icicle\Psr7Bridge\Stream\Stream;
use Zend\Diactoros\Uri as PsrUri;
use Zend\Diactoros\Request as PsrRequest;
use Zend\Diactoros\Response as PsrResponse;

class MessageFactory
{
    /**
     * @param IcicleUri $icicleUri
     * @return PsrUri
     */
    public static function createUri(IcicleUri $icicleUri)
    {
        return new PsrUri($icicleUri->__toString());
    }

    /**
     * @param IcicleRequest $request
     * @return PsrRequest
     */
    public static function createRequest(IcicleRequest $request)
    {
        return new PsrRequest(
            static::createUri($request->getUri()),
            $request->getMethod(),
            new Stream($request->getBody()),
            $request->getHeaders()
        );
    }

    public static function createResponse(IcicleResponse $response)
    {
        return new PsrResponse(
            new Stream($response->getBody()),
            $response->getStatusCode(),
            $response->getHeaders()
        );
    }
}
