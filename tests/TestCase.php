<?php

namespace Quill\Limiter\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Quill\Http\Request;
use Quill\Http\HttpResponse;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER = [];
        $_GET = [];
        $_POST = [];
    }

    protected function createRequest(string $method = "GET", string $uri = "/", array $headers = []): Request
    {
        $_SERVER["REQUEST_METHOD"] = $method;
        $_SERVER["REQUEST_URI"] = $uri;
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        
        foreach ($headers as $key => $value) {
            $serverKey = "HTTP_" . strtoupper(str_replace("-", "_", $key));
            $_SERVER[$serverKey] = $value;
        }
        
        return new Request();
    }

    protected function getNextHandler(int $status = 200, mixed $data = ["success" => true]): callable
    {
        return function (Request $request) use ($status, $data) {
            return new HttpResponse($data, $status);
        };
    }
}
