<?php

namespace webignition\Tests\WebResource;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use webignition\InternetMediaType\Parser\ParseException as InternetMediaTypeParseException;
use webignition\WebResource\Exception\InvalidContentTypeException;
use webignition\WebResource\Exception\HttpException;
use webignition\WebResource\Exception\TransportException;
use webignition\WebResource\JsonDocument;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResource\WebResource;
use webignition\WebResourceInterfaces\InvalidContentTypeExceptionInterface;
use webignition\WebResourceInterfaces\RetrieverExceptionInterface;

class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        new Retriever();
    }

    /**
     * @dataProvider throwsHttpExceptionDataProvider
     *
     * @param array $allowedContentTypes
     * @param bool $allowUnknownResourceTypes
     * @param array $httpFixtures
     * @param $expectedExceptionMessage
     * @param $expectedExceptionCode
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws TransportException
     */
    public function testThrowsHttpException(
        array $allowedContentTypes,
        $allowUnknownResourceTypes,
        array $httpFixtures,
        $expectedExceptionMessage,
        $expectedExceptionCode
    ) {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $request = new Request('GET', 'http://example.com');

        $retriever = new Retriever($httpClient, $allowedContentTypes, $allowUnknownResourceTypes);

        try {
            $retriever->retrieve($request);
            $this->fail(HttpException::class . ' not thrown');
        } catch (HttpException $webResourceException) {
            $this->assertEquals($expectedExceptionMessage, $webResourceException->getMessage());
            $this->assertEquals($expectedExceptionCode, $webResourceException->getCode());
        }

        $this->assertEquals(0, $mockHandler->count());
    }

    /**
     * @return array
     */
    public function throwsHttpExceptionDataProvider()
    {
        return [
            'http 404' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(404),
                ],
                'expectedExceptionMessage' => 'Not Found',
                'expectedExceptionCode' => 404,
            ],
            'http 404 with content-type pre-verification' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(404),
                    new Response(404),
                ],
                'expectedExceptionMessage' => 'Not Found',
                'expectedExceptionCode' => 404,
            ],
            'http 500' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(500),
                ],
                'expectedExceptionMessage' => 'Internal Server Error',
                'expectedExceptionCode' => 500,
            ],
            'http 100' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(100)
                ],
                'expectedExceptionMessage' => 'Continue',
                'expectedExceptionCode' => 100,
            ],
            'http 301' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(301),
                ],
                'expectedExceptionMessage' => 'Moved Permanently',
                'expectedExceptionCode' => 301,
            ],
        ];
    }

    /**
     * @dataProvider throwsCurlTransportExceptionDataProvider
     *
     * @param bool $allowUnknownResourceTypes
     * @param array $httpFixtures
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCurlCode
     *
     * @throws HttpException
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeException
     */
    public function testThrowsCurlTransportException(
        $allowUnknownResourceTypes,
        array $httpFixtures,
        $expectedExceptionMessage,
        $expectedExceptionCurlCode
    ) {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $request = new Request('GET', 'http://example.com');

        $retriever = new Retriever($httpClient, [], $allowUnknownResourceTypes);

        try {
            $retriever->retrieve($request);
            $this->fail(TransportException::class . ' not thrown');
        } catch (TransportException $transportException) {
            $this->assertTrue($transportException->isCurlException());
            $this->assertEquals($expectedExceptionMessage, $transportException->getMessage());
            $this->assertEquals($expectedExceptionCurlCode, $transportException->getTransportErrorCode());
        }

        $this->assertEquals(0, $mockHandler->count());
    }

    /**
     * @return array
     */
    public function throwsCurlTransportExceptionDataProvider()
    {
        $operationTimedOutConnectException =  new ConnectException(
            'cURL error 28: operation timed out',
            new Request('GET', 'http://example.com/')
        );

        return [
            'operation timed out, fails post-verify' => [
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    $operationTimedOutConnectException,
                ],
                'expectedExceptionMessage' => 'operation timed out',
                'expectedExceptionCurlCode' => 28,
            ],
            'operation timed out, fails pre-verify' => [
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    $operationTimedOutConnectException,
                    $operationTimedOutConnectException,
                ],
                'expectedExceptionMessage' => 'operation timed out',
                'expectedExceptionCurlCode' => 28,
            ],
        ];
    }

    /**
     * @dataProvider throwsConnectTransportExceptionDataProvider
     *
     * @param array $httpFixtures
     * @param string $expectedExceptionMessage
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws RetrieverExceptionInterface
     */
    public function testThrowsConnectTransportException(
        array $httpFixtures,
        $expectedExceptionMessage
    ) {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $request = new Request('GET', 'http://example.com');

        $retriever = new Retriever($httpClient);

        try {
            $retriever->retrieve($request);
            $this->fail(TransportException::class . ' not thrown');
        } catch (TransportException $transportException) {
            $this->assertFalse($transportException->isCurlException());
            $this->assertEquals($expectedExceptionMessage, $transportException->getMessage());
        }

        $this->assertEquals(0, $mockHandler->count());
    }

    /**
     * @return array
     */
    public function throwsConnectTransportExceptionDataProvider()
    {
        $connectException =  new ConnectException(
            'foo',
            new Request('GET', 'http://example.com/')
        );

        return [
            'operation timed out' => [
                'httpFixtures' => [
                    $connectException,
                ],
                'expectedExceptionMessage' => 'foo',
            ],
        ];
    }

    /**
     * @dataProvider getInvalidContentTypeDataProvider
     *
     * @param array $allowedContentTypes
     * @param bool $allowUnknownResourceTypes
     * @param array $httpFixtures
     * @param string $expectedExceptionMessage
     * @param string $expectedExceptionResponseContentType
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws RetrieverExceptionInterface
     */
    public function testGetInvalidContentType(
        array $allowedContentTypes,
        $allowUnknownResourceTypes,
        array $httpFixtures,
        $expectedExceptionMessage,
        $expectedExceptionResponseContentType
    ) {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $request = new Request('GET', 'http://example.com');

        $retriever = new Retriever($httpClient, $allowedContentTypes, $allowUnknownResourceTypes);

        try {
            $retriever->retrieve($request);
            $this->fail(InvalidContentTypeException::class . ' not thrown');
        } catch (InvalidContentTypeExceptionInterface $invalidContentTypeException) {
            $this->assertEquals($expectedExceptionMessage, $invalidContentTypeException->getMessage());
            $this->assertEquals(InvalidContentTypeException::CODE, $invalidContentTypeException->getCode());

            $this->assertEquals(
                $expectedExceptionResponseContentType,
                (string)$invalidContentTypeException->getContentType()
            );
        }

        $this->assertEquals(0, $mockHandler->count());
    }

    /**
     * @return array
     */
    public function getInvalidContentTypeDataProvider()
    {
        return [
            'no allowed content types; fails pre-verification' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(),
                ],
                'expectedExceptionMessage' => 'Invalid content type ""',
                'expectedExceptionResponseContentType' => '',
            ],
            'disallowed content type; fails pre-verification' => [
                'allowedContentTypes' => [
                    'text/html',
                ],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(200, [
                        'Content-Type' => 'text/plain',
                    ]),
                ],
                'expectedExceptionMessage' => 'Invalid content type "text/plain"',
                'expectedExceptionResponseContentType' => 'text/plain',
            ],
            'disallowed content type; 500 on pre-verification, fails post-verification' => [
                'allowedContentTypes' => [
                    'text/html',
                ],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(500),
                    new Response(200, ['Content-Type' => 'text/plain']),
                ],
                'expectedExceptionMessage' => 'Invalid content type "text/plain"',
                'expectedExceptionResponseContentType' => 'text/plain',
            ],
            'no defined allowed content types; fails post-verification' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(404),
                    new Response(200),
                ],
                'expectedExceptionMessage' => 'Invalid content type ""',
                'expectedExceptionResponseContentType' => '',
            ],
        ];
    }

    /**
     * @dataProvider getSuccessDataProvider
     *
     * @param array $allowedContentTypes
     * @param bool $allowUnknownResourceTypes
     * @param array $httpFixtures
     * @param string $expectedResourceClassName
     * @param string $expectedResourceContent
     *
     * @throws InternetMediaTypeParseException
     * @throws InvalidContentTypeExceptionInterface
     * @throws RetrieverExceptionInterface
     */
    public function testGetSuccess(
        array $allowedContentTypes,
        $allowUnknownResourceTypes,
        array $httpFixtures,
        $expectedResourceClassName,
        $expectedResourceContent
    ) {
        $mockHandler = new MockHandler($httpFixtures);
        $httpClient = new HttpClient([
            'handler' => HandlerStack::create($mockHandler),
        ]);

        $request = new Request('GET', 'http://example.com');

        $retriever = new Retriever($httpClient, $allowedContentTypes, $allowUnknownResourceTypes);
        $resource = $retriever->retrieve($request);

        $this->assertInstanceOf($expectedResourceClassName, $resource);
        $this->assertEquals($expectedResourceContent, $resource->getContent());

        $this->assertEquals(0, $mockHandler->count());
    }

    /**
     * @return array
     */
    public function getSuccessDataProvider()
    {
        return [
            'text/plain no mapped resource type' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'text/plain'], 'Foo'),
                ],
                'expectedResourceClassName' => WebResource::class,
                'expectedResourceContent' => 'Foo',
            ],
            'text/html' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'text/html'], '<!doctype><html>'),
                ],
                'expectedResourceClassName' => WebPage::class,
                'expectedResourceContent' => '<!doctype><html>',
            ],
            'application/json' => [
                'allowedContentTypes' => [],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'application/json'], '[]'),
                ],
                'expectedResourceClassName' => JsonDocument::class,
                'expectedResourceContent' => '[]',
            ],
            'text/html with content-type pre-verification' => [
                'allowedContentTypes' => [
                    'text/html',
                ],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'text/html']),
                    new Response(200, ['Content-Type' => 'text/html'], '<!doctype><html>'),
                ],
                'expectedResourceClassName' => WebPage::class,
                'expectedResourceContent' => '<!doctype><html>',
            ],
            'non-modelled content type; allow unknown resource types=true' => [
                'allowedContentTypes' => [
                    'text/css',
                ],
                'allowUnknownResourceTypes' => true,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'text/css'], 'body { color: #ff0000 }'),
                ],
                'expectedResourceClassName' => WebResource::class,
                'expectedResourceContent' => 'body { color: #ff0000 }',
            ],
            'non-modelled content type; allow unknown resource types=false' => [
                'allowedContentTypes' => [
                    'text/css',
                ],
                'allowUnknownResourceTypes' => false,
                'httpFixtures' => [
                    new Response(200, ['Content-Type' => 'text/css']),
                    new Response(200, ['Content-Type' => 'text/css'], 'body { color: #ff0000 }'),
                ],
                'expectedResourceClassName' => WebResource::class,
                'expectedResourceContent' => 'body { color: #ff0000 }',
            ],
        ];
    }
}
