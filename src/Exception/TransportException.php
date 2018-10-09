<?php

namespace webignition\WebResource\Exception;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Psr\Http\Message\RequestInterface;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\WebResourceInterfaces\RetrieverTransportExceptionInterface;

class TransportException extends AbstractException implements RetrieverTransportExceptionInterface
{
    public function __construct(RequestInterface $request, RequestException $requestException)
    {
        $message = $requestException->getMessage();
        $code = $requestException->getCode();
        $previous = $requestException;

        if ($requestException instanceof ConnectException) {
            if (CurlExceptionFactory::isCurlException($requestException)) {
                $curlException = CurlExceptionFactory::fromConnectException($requestException);
                $message = $curlException->getMessage();
                $code = $curlException->getCurlCode();
                $previous = $curlException;
            }
        }

        parent::__construct($request, $message, $code, $previous);
    }

    public function getTransportErrorCode(): int
    {
        return $this->getCode();
    }

    public function isCurlException(): bool
    {
        return $this->getPrevious() instanceof CurlException;
    }

    public function isTooManyRedirectsException(): bool
    {
        return $this->getPrevious() instanceof TooManyRedirectsException;
    }
}
