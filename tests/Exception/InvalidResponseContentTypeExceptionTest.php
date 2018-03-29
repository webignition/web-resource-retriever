<?php

namespace webignition\Tests\WebResource\Exception;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Exception\InvalidResponseContentTypeException;

class InvalidResponseContentTypeExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $contentType = new InternetMediaType();
        $contentType->setType('application');
        $contentType->setSubtype('pdf');

        $request = new Request('GET', 'http://example.com/');
        $response = new Response();

        $exception = new InvalidResponseContentTypeException($contentType, $request, $response);

        $this->assertEquals($contentType, $exception->getContentType());
        $this->assertEquals($request, $exception->getRequest());
        $this->assertEquals($response, $exception->getResponse());
    }
}
