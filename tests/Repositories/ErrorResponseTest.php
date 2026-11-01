<?php

namespace MFlor\Pwned\Tests\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Repositories\BreachRepository;
use PHPUnit\Framework\TestCase;

/**
 * Error details (API message, retry-after) are handled in AbstractServiceRepository,
 * so they're tested through one concrete repository.
 */
class ErrorResponseTest extends TestCase
{
    public function testApiMessageIsReadFromJsonBody(): void
    {
        $exception = $this->getException(new Response(
            401,
            ['Content-Type' => 'application/json'],
            '{ "statusCode": 401, "message": "Access denied due to invalid hibp-api-key." }'
        ));

        $this->assertInstanceOf(UnauthorizedException::class, $exception);
        $this->assertSame('Access denied due to invalid hibp-api-key.', $exception->getApiMessage());
        $this->assertSame(
            '401: Unauthorized (Access denied due to invalid hibp-api-key.)',
            $exception->getMessage()
        );
    }

    public function testApiMessageIsReadFromPlainTextBody(): void
    {
        $exception = $this->getException(new Response(401, [], "Access denied due to missing hibp-api-key.\n"));

        $this->assertSame('Access denied due to missing hibp-api-key.', $exception->getApiMessage());
    }

    /**
     * @dataProvider bodiesWithoutMessageProvider
     */
    public function testApiMessageIsNullWhenBodyHasNoMessage(string $body): void
    {
        $exception = $this->getException(new Response(503, [], $body));

        $this->assertNull($exception->getApiMessage());
        $this->assertSame('503: Service Unavailable', $exception->getMessage());
    }

    /**
     * @return array<string, string[]>
     */
    public function bodiesWithoutMessageProvider(): array
    {
        return [
            'empty body' => [''],
            'HTML error page' => ['<!DOCTYPE html><html><body>Cloudflare error</body></html>'],
            'JSON without message' => ['{"statusCode": 503}'],
            'very long text' => [str_repeat('x', 501)],
        ];
    }

    public function testRetryAfterIsReadFromHeader(): void
    {
        $exception = $this->getException(new Response(
            429,
            ['retry-after' => '2'],
            '{"statusCode": 429, "message": "Rate limit is exceeded. Try again in 2 seconds."}'
        ));

        $this->assertInstanceOf(TooManyRequestsException::class, $exception);
        $this->assertSame(2, $exception->getRetryAfter());
        $this->assertSame('Rate limit is exceeded. Try again in 2 seconds.', $exception->getApiMessage());
    }

    public function testRetryAfterCanBeAnHttpDate(): void
    {
        $date = gmdate('D, d M Y H:i:s \G\M\T', time() + 60);
        $exception = $this->getException(new Response(429, ['Retry-After' => $date]));

        $this->assertInstanceOf(TooManyRequestsException::class, $exception);
        $this->assertGreaterThanOrEqual(55, $exception->getRetryAfter());
        $this->assertLessThanOrEqual(60, $exception->getRetryAfter());
    }

    /**
     * @dataProvider invalidRetryAfterProvider
     *
     * @param array<string, string> $headers
     */
    public function testRetryAfterIsNullWhenMissingOrInvalid(array $headers): void
    {
        $exception = $this->getException(new Response(429, $headers));

        $this->assertInstanceOf(TooManyRequestsException::class, $exception);
        $this->assertNull($exception->getRetryAfter());
    }

    /**
     * @return array<string, array<array<string, string>>>
     */
    public function invalidRetryAfterProvider(): array
    {
        return [
            'missing' => [[]],
            'not a number or date' => [['Retry-After' => 'soon']],
        ];
    }

    private function getException(Response $response): AbstractException
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([$response]))]);
        $repository = new BreachRepository($client);

        try {
            $repository->getAll();
        } catch (AbstractException $exception) {
            return $exception;
        }

        $this->fail('Failed throwing an exception!');
    }
}
