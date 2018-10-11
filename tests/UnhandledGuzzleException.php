<?php

namespace webignition\Tests\WebResource;

use GuzzleHttp\Exception\GuzzleException;

/**
 * @method string getMessage()
 * @method null getPrevious()
 * @method mixed getCode()
 * @method string getFile()
 * @method int getLine()
 * @method array getTrace()
 * @method string getTraceAsString()
 */
class UnhandledGuzzleException extends \Exception implements GuzzleException
{
    public function __call($name, $arguments)
    {
    }
}
