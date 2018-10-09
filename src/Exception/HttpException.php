<?php

namespace webignition\WebResource\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\WebResourceInterfaces\RetrieverHttpExceptionInterface;

class HttpException extends AbstractException implements RetrieverHttpExceptionInterface
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->response = $response;

        $reasonPhrase = $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();

        parent::__construct($request, $reasonPhrase, $statusCode);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
