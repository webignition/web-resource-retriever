<?php

namespace webignition\Tests\WebResource\Exception;

use GuzzleHttp\Exception\ConnectException;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\WebResource\Exception\TransportException;

class TransportExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param RequestInterface $request
     * @param ConnectException $connectException
     * @param string $expectedMessage
     * @param int $expectedCode
     * @param bool $expectedIsCurlException
     */
    public function testCreate(
        RequestInterface $request,
        ConnectException $connectException,
        $expectedMessage,
        $expectedCode,
        $expectedIsCurlException
    ) {
        $exception = new TransportException($request, $connectException);

        $this->assertEquals($request, $exception->getRequest());

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($expectedCode, $exception->getCode());
        $this->assertEquals($expectedCode, $exception->getTransportErrorCode());
        $this->assertEquals($expectedIsCurlException, $exception->isCurlException());

        $previousException = $exception->getPrevious();

        if ($expectedIsCurlException) {
            /* @var CurlException $previousException */
            $this->assertInstanceOf(CurlException::class, $previousException);
            $this->assertEquals($expectedMessage, $previousException->getMessage());
            $this->assertEquals($expectedCode, $previousException->getCurlCode());
        } else {
            $this->assertEquals($connectException, $previousException);
        }
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        /* @var RequestInterface|MockInterface $request */
        $request = \Mockery::mock(RequestInterface::class);

        $curlErrorMessage = 'Resolving timed out after 4 milliseconds';

        $curl28ConnectException = new ConnectException('cURL error 28: ' . $curlErrorMessage, $request);
        $genericConnectException = new ConnectException('foo', $request);

        return [
            'cURL 28 time out' => [
                'request' => $request,
                'connectException' => $curl28ConnectException,
                'expectedMessage' => $curlErrorMessage,
                'expectedCode' => 28,
                'expectedIsCurlException' => true,
            ],
            'generic connect exception' => [
                'request' => $request,
                'connectException' => $genericConnectException,
                'expectedMessage' => 'foo',
                'expectedCode' => 0,
                'expectedIsCurlException' => false,
            ],
        ];
    }
}
