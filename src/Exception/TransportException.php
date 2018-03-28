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
    /**
     * @param RequestInterface|null $request
     * @param RequestException $requestException
     */
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

    /**
     * @return int
     */
    public function getTransportErrorCode()
    {
        return $this->getCode();
    }

    /**
     * @return bool
     */
    public function isCurlException()
    {
        return $this->getPrevious() instanceof CurlException;
    }

    /**
     * @return bool
     */
    public function isTooManyRedirectsException()
    {
        return $this->getPrevious() instanceof TooManyRedirectsException;
    }
}
