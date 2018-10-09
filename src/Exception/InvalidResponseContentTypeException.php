<?php

namespace webignition\WebResource\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;
use webignition\WebResourceInterfaces\RetrieverContentExceptionInterface;

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

    public function __construct(
        InternetMediaTypeInterface $contentType,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $this->contentType = $contentType;
        $this->response = $response;

        parent::__construct($request, sprintf(self::MESSAGE, $contentType->getTypeSubtypeString()), self::CODE);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getContentType(): InternetMediaTypeInterface
    {
        return $this->contentType;
    }
}
