<?php

namespace webignition\Tests\WebResource\Exception;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Mockery\MockInterface;
use Psr\Http\Message\RequestInterface;
use webignition\GuzzleHttp\Exception\CurlException\Exception as CurlException;
use webignition\WebResource\Exception\TransportException;

class TransportExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param RequestInterface $request
     * @param RequestException $requestException
     * @param string $expectedMessage
     * @param int $expectedCode
     * @param bool $expectedIsCurlException
     * @param bool $expectedIsTooManyRedirectsException
     */
    public function testCreate(
        RequestInterface $request,
        RequestException $requestException,
        $expectedMessage,
        $expectedCode,
        $expectedIsCurlException,
        $expectedIsTooManyRedirectsException
    ) {
        $exception = new TransportException($request, $requestException);

        $this->assertEquals($request, $exception->getRequest());

        $this->assertEquals($expectedMessage, $exception->getMessage());
        $this->assertEquals($expectedCode, $exception->getCode());
        $this->assertEquals($expectedCode, $exception->getTransportErrorCode());
        $this->assertEquals($expectedIsCurlException, $exception->isCurlException());
        $this->assertEquals($expectedIsTooManyRedirectsException, $exception->isTooManyRedirectsException());

        $previousException = $exception->getPrevious();

        if ($expectedIsCurlException) {
            /* @var CurlException $previousException */
            $this->assertInstanceOf(CurlException::class, $previousException);
            $this->assertEquals($expectedMessage, $previousException->getMessage());
            $this->assertEquals($expectedCode, $previousException->getCurlCode());
        } else {
            $this->assertEquals($requestException, $previousException);
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
        $tooManyRedirectsExceptionMessage = 'Will not follow more than 5 redirects';

        $curl28ConnectException = new ConnectException('cURL error 28: ' . $curlErrorMessage, $request);
        $genericConnectException = new ConnectException('foo', $request);

        $tooManyRedirectsException = new TooManyRedirectsException(
            $tooManyRedirectsExceptionMessage,
            $request
        );

        return [
            'cURL 28 time out' => [
                'request' => $request,
                'connectException' => $curl28ConnectException,
                'expectedMessage' => $curlErrorMessage,
                'expectedCode' => 28,
                'expectedIsCurlException' => true,
                'expectedIsTooManyRedirectsException' => false,
            ],
            'too many redirects exception' => [
                'request' => $request,
                'connectException' => $tooManyRedirectsException,
                'expectedMessage' => $tooManyRedirectsExceptionMessage,
                'expectedCode' => 0,
                'expectedIsCurlException' => false,
                'expectedIsTooManyRedirectsException' => true,
            ],
            'generic connect exception' => [
                'request' => $request,
                'connectException' => $genericConnectException,
                'expectedMessage' => 'foo',
                'expectedCode' => 0,
                'expectedIsCurlException' => false,
                'expectedIsTooManyRedirectsException' => false,
            ],
        ];
    }
}
