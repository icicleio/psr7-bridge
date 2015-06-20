<?php

namespace Icicle\Psr7Bridge;

use Icicle\Http\Message\RequestInterface as IcicleRequest;
use Icicle\Http\Message\ResponseInterface as IcicleResponse;
use Icicle\Http\Message\UriInterface as IcicleUri;
use Icicle\Psr7Bridge\Stream\Stream;
use Zend\Diactoros\Uri as PsrUri;
use Zend\Diactoros\Request as PsrRequest;
use Zend\Diactoros\Response as PsrResponse;

final class MessageFactory implements MessageFactoryInterface
{
    /**
     * @param IcicleUri $icicleUri
     * @return PsrUri
     */
    public function createUri(IcicleUri $icicleUri)
    {
        return new PsrUri($icicleUri->__toString());
    }

    /**
     * @param IcicleRequest $icicleRequest
     * @return PsrRequest
     */
    public function createRequest(IcicleRequest $icicleRequest)
    {
        $request = new PsrRequest(
            $this->createUri($icicleRequest->getUri()),
            $icicleRequest->getMethod(),
            new Stream($icicleRequest->getBody()),
            $icicleRequest->getHeaders()
        );

        $request = $request->withProtocolVersion($icicleRequest->getProtocolVersion());

        return $request;
    }

    public function createResponse(IcicleResponse $icicleResponse)
    {
        $response = new PsrResponse(
            new Stream($icicleResponse->getBody()),
            $icicleResponse->getStatusCode(),
            $icicleResponse->getHeaders()
        );

        $response = $response->withProtocolVersion($icicleResponse->getProtocolVersion());

        return $response;
    }
}
