<?php

namespace webignition\WebResource\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;
use webignition\WebResourceInterfaces\RetrieverContentExceptionInterface;
use webignition\WebResourceInterfaces\RetrieverHttpExceptionInterface;

class InvalidResponseContentTypeException extends AbstractException implements
    RetrieverContentExceptionInterface,
    InvalidContentTypeExceptionInterface
{
    const MESSAGE = 'Invalid content type "%s"';
    const CODE = 0;

    /**
     * @var InternetMediaTypeInterface
     */
    private $contentType;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param InternetMediaTypeInterface $contentType
     * @param RequestInterface|null $request
     * @param ResponseInterface $response
     */
    public function __construct(
        InternetMediaTypeInterface $contentType,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->contentType = $contentType;
        $this->response = $response;

        parent::__construct($request, sprintf(self::MESSAGE, $contentType->getTypeSubtypeString()), self::CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}
