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
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class RepositoryTestCase extends TestCase
{
    protected array $requestContainer;

    /**
     * @return array[]
     */
    public function badResponseProvider(): array
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
            'Service Unavailable' => [
                new Response(503, [], 'Service is unavailable'),
                ServiceUnavailableException::class,
                503,
                'Service Unavailable'
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function authenticatedBadResponseProvider(): array
    {
        $responses = $this->badResponseProvider();
        $responses['Unauthorized'] = [
            new Response(401, [], 'Unauthorized'),
            UnauthorizedException::class,
            401,
            'Unauthorized'
        ];
        return $responses;
    }

    protected function assertException(
        string $expected,
        AbstractException $actual,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $this->assertInstanceOf($expected, $actual);
        $this->assertSame($statusCode, $actual->getStatusCode());
        $this->assertSame($reasonPhrase, $actual->getReasonPhrase());
    }

    protected function getClientWithResponse(string $data = ''): Client
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $data),
        ]);
        $this->requestContainer = [];
        $history = Middleware::history($this->requestContainer);
        $handler = HandlerStack::create($mock);
        $handler->push($history);
        return new Client([
            'handler' => $handler,
            'headers' => [
                'User-Agent' => 'mflor-pwned-php-library/2.0'
            ]
        ]);
    }

    /**
     * @param string $file
     * @return false|string
     */
    protected function getTestData(string $file)
    {
        return file_get_contents(sprintf('%s/data/%s', dirname(__DIR__), $file));
    }

    protected function assertRequest(string $requestTarget, string $query = '', ?array $headers = null): void
    {
        $this->assertCount(1, $this->requestContainer);
        /** @var Request $request */
        $request = $this->requestContainer[0]['request'];
        $uri = $request->getUri();
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($requestTarget, $uri->getPath());
        $this->assertSame($query, $uri->getQuery());
        if ($headers) {
            foreach ($headers as $key => $value) {
                $this->assertArrayHasKey($key, $request->getHeaders());
                $this->assertSame($value, $request->getHeaderLine($key));
            }
        } else {
            $this->assertEquals([
                'User-Agent' => [
                    'mflor-pwned-php-library/2.0'
                ]
            ], $request->getHeaders());
        }
    }
}
