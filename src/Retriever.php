<?php

namespace webignition\WebResource;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\JsonDocument\JsonDocument;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResourceInterfaces\RetrieverInterface;
use webignition\WebResourceInterfaces\WebResourceInterface;

class Retriever implements RetrieverInterface
{
    const DEFAULT_WEB_RESOURCE_MODEL = WebResource::class;
    const DEFAULT_ALLOWED_CONTENT_TYPES = [];
    const DEFAULT_ALLOW_UNKNOWN_RESOURCE_TYPES = true;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var string[]
     */
    private $allowedContentTypes = self::DEFAULT_ALLOWED_CONTENT_TYPES;

    /**
     * @var bool
     */
    private $allowUnknownResourceTypes = self::DEFAULT_ALLOW_UNKNOWN_RESOURCE_TYPES;

    public function __construct(
        HttpClient $httpClient = null,
        array $allowedContentTypes = self::DEFAULT_ALLOWED_CONTENT_TYPES,
        bool $allowUnknownResourceTypes = self::DEFAULT_ALLOW_UNKNOWN_RESOURCE_TYPES
    ) {
        if (empty($httpClient)) {
            $httpClient = new HttpClient();
        }

        $this->setHttpClient($httpClient);
        $this->setAllowedContentTypes($allowedContentTypes);
        $this->setAllowUnknownResourceTypes($allowUnknownResourceTypes);
    }

    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowedContentTypes($allowedContentTypes = [])
    {
        $this->allowedContentTypes = $allowedContentTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowUnknownResourceTypes($allowUnknownResourceTypes = true)
    {
        $this->allowUnknownResourceTypes = $allowUnknownResourceTypes;
    }

    /**
     * @param RequestInterface $request
     *
     * @return WebResourceInterface
     *
     * A RetrieverExceptionInterface instance MUST be thrown when a resource
     * is retrieved with a status code other than 200.
     *
     * An InvalidContentTypeExceptionInterface instance MUST  be thrown when the content type of a resource
     * does not match one of those provided by setAllowedContentTypes() and when unknown resource types are not allowed.
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws TransportException
     * @throws InvalidResponseContentTypeException
     */
    public function retrieve(RequestInterface $request): WebResourceInterface
    {
        if (!$this->allowUnknownResourceTypes) {
            $headRequest = $request->withMethod('HEAD');

            $this->preVerifyContentType($headRequest);
        }

        $requestUri = $request->getUri();
        $response = null;

        try {
            $response = $this->httpClient->send($request, [
                'on_stats' => function (TransferStats $stats) use (&$requestUri) {
                    if ($stats->hasResponse()) {
                        $requestUri = $stats->getEffectiveUri();
                    }
                },
            ]);
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();
        } catch (RequestException $requestException) {
            throw new TransportException($request, $requestException);
        } catch (GuzzleException $guzzleException) {
            throw new TransportException(
                $request,
                new RequestException($guzzleException->getMessage(), $request, null, $guzzleException)
            );
        }

        $responseStatusCode = $response->getStatusCode();
        $isSuccessResponse = $responseStatusCode >= 200 && $responseStatusCode < 300;

        if (!$isSuccessResponse) {
            throw new HttpException($request, $response);
        }

        $modelClassName = $this->getModelClassNameFromContentTypeWithContentTypeVerification($request, $response);

        return new $modelClassName(WebResourceProperties::create([
            WebResourceProperties::ARG_URI => $requestUri,
            WebResourceProperties::ARG_RESPONSE => $response,
        ]));
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     *
     * @return string
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidResponseContentTypeException
     */
    private function getModelClassNameFromContentTypeWithContentTypeVerification(
        RequestInterface $request,
        ResponseInterface $response
    ): string {
        $contentType = $this->getContentTypeFromResponse($response);
        $modelClassName = $this->getModelClassNameForContentType($contentType);

        $isAllowedContentType = in_array($contentType->getTypeSubtypeString(), $this->allowedContentTypes);

        if (!$isAllowedContentType && !$this->allowUnknownResourceTypes) {
            throw new InvalidResponseContentTypeException($contentType, $request, $response);
        }

        return $modelClassName;
    }

    private function getModelClassNameForContentType(InternetMediaTypeInterface $contentType): string
    {
        if (WebPage::models($contentType)) {
            return WebPage::class;
        }

        if (JsonDocument::models($contentType)) {
            return JsonDocument::class;
        }

        return WebResource::class;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidResponseContentTypeException
     */
    private function preVerifyContentType(RequestInterface $request): ?bool
    {
        try {
            $response = $this->httpClient->send($request);
        } catch (RequestException $requestException) {
            return null;
        } catch (GuzzleException $guzzleException) {
            return null;
        }

        $this->getModelClassNameFromContentTypeWithContentTypeVerification($request, $response);

        return true;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return InternetMediaTypeInterface
     *
     * @throws InternetMediaTypeParseException
     */
    private function getContentTypeFromResponse(ResponseInterface $response): InternetMediaTypeInterface
    {
        $mediaTypeParser = new InternetMediaTypeParser();
        $mediaTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);
        $mediaTypeParser->setIgnoreInvalidAttributes(true);

        $contentTypeHeader = $response->getHeader('content-type');
        $contentTypeString = empty($contentTypeHeader)
            ? ''
            : $contentTypeHeader[0];

        return $mediaTypeParser->parse($contentTypeString);
    }
}
