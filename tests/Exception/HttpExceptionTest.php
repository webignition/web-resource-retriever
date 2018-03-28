<?php

namespace webignition\Tests\WebResource\Exception;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use webignition\WebResource\Exception\HttpException;

class HttpExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param string $expectedMessage
     * @param int $expectedCode
     */
    public function testCreate(
        RequestInterface $request,
        ResponseInterface $response,
        $expectedMessage,
        $expectedCode
    ) {
        $exception = new HttpException($request, $response);

        $this->assertEquals($request, $exception->getRequest());
        $this->assertEquals($response, $exception->getResponse());

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($expectedCode, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $request = \Mockery::mock(RequestInterface::class);

        $notFoundResponse = \Mockery::mock(ResponseInterface::class);
        $notFoundResponse
            ->shouldReceive('getReasonPhrase')
            ->andReturn('Not Found');

        $notFoundResponse
            ->shouldReceive('getStatusCode')
            ->andReturn(404);

        return [
            '404 Not Found' => [
                'request' => $request,
                'response' => $notFoundResponse,
                'expectedMessage' => 'Not Found',
                'expectedCode' => 404,
            ],
        ];
    }
}
