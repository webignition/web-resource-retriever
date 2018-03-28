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

    /**
     * @param ResponseInterface $response
     * @param RequestInterface|null $request
     */
    public function __construct(RequestInterface $request, ResponseInterface $response)
    {
        $this->response = $response;

        $reasonPhrase = $response->getReasonPhrase();
        $statusCode = $response->getStatusCode();

        parent::__construct($request, $reasonPhrase, $statusCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }
}
