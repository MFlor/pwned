<?php

namespace MFlor\Pwned\Tests\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use PHPUnit\Framework\TestCase;

class RepositoryTestCase extends TestCase
{
    /** @var array */
    protected $requestContainer;

    public function badResponseProvider()
    {
        return [
            'Bad request' => [
                new Response(400, [], 'Bad request'),
                BadRequestException::class,
                400,
                'Bad Request'
            ],
            'Forbidden' => [
                new Response(403, [], 'Forbidden'),
                ForbiddenException::class,
                403,
                'Forbidden'
            ],
            'Not found' => [
                new Response(404, [], 'Not found'),
                NotFoundException::class,
                404,
                'Not Found'
            ],
            'Too many requests' => [
                new Response(429, [], 'Too many requests'),
                TooManyRequestsException::class,
                429,
                'Too Many Requests'
            ],
        ];
    }

    protected function assertException(
        string $expected,
        AbstractException $actual,
        int $statusCode,
        string $reasonPhrase
    ) {
        $this->assertInstanceOf($expected, $actual);
        $this->assertSame($statusCode, $actual->getStatusCode());
        $this->assertSame($reasonPhrase, $actual->getReasonPhrase());
    }

    protected function getClientWithResponse(string $data): Client
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $data),
        ]);
        $this->requestContainer = [];
        $history = Middleware::history($this->requestContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        return new Client(['handler' => $handler]);
    }

    protected function getTestData(string $file)
    {
        return file_get_contents(sprintf('%s/data/%s', dirname(__DIR__), $file));
    }

    protected function assertRequest(string $requestTarget, string $query = '')
    {
        $this->assertCount(1, $this->requestContainer);
        /** @var Request $request */
        $request = $this->requestContainer[0]['request'];
        $uri = $request->getUri();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($requestTarget, $uri->getPath());
        $this->assertSame($query, $uri->getQuery());
    }
}
