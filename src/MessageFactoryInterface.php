<?php

namespace Icicle\Psr7Bridge;

use Icicle\Http\Message\RequestInterface as IcicleRequest;
use Icicle\Http\Message\ResponseInterface as IcicleResponse;
use Icicle\Http\Message\UriInterface as IcicleUri;
use Zend\Diactoros\Request as PsrRequest;
use Zend\Diactoros\Uri as PsrUri;

interface MessageFactoryInterface
{
    /**
     * @param IcicleUri $icicleUri
     * @return PsrUri
     */
    public function createUri(IcicleUri $icicleUri);

    /**
     * @param IcicleRequest $request
     * @return PsrRequest
     */
    public function createRequest(IcicleRequest $request);

    public function createResponse(IcicleResponse $response);
}
