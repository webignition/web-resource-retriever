<?php

namespace webignition\WebResource\Exception;

use Psr\Http\Message\RequestInterface;
use webignition\WebResourceInterfaces\RetrieverExceptionInterface;

abstract class AbstractException extends \Exception implements RetrieverExceptionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface|null $request
     * @param string $message
     * @param int $code
     * @param \Exception|null $previous
     */
    public function __construct(RequestInterface $request, $message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }
}
