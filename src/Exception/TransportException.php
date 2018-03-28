<?php

namespace webignition\WebResource\Exception;

use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\GuzzleHttp\Exception\CurlException\Factory as CurlExceptionFactory;
use webignition\WebResourceInterfaces\RetrieverTransportExceptionInterface;

class TransportException extends AbstractException implements RetrieverTransportExceptionInterface
{
    /**
     * @param RequestInterface|null $request
     * @param ConnectException $connectException
     */
    public function __construct(RequestInterface $request, ConnectException $connectException)
    {
        $message = $connectException->getMessage();
        $code = $connectException->getCode();
        $previous = $connectException;

        if (CurlExceptionFactory::isCurlException($connectException)) {
            $curlException = CurlExceptionFactory::fromConnectException($connectException);
            $message = $curlException->getMessage();
            $code = $curlException->getCurlCode();
            $previous = $curlException;
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
}
