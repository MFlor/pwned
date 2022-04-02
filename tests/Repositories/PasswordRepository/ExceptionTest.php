<?php

namespace MFlor\Pwned\Tests\Repositories\PasswordRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class ExceptionTest extends RepositoryTestCase
{
    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     * @param int $statusCode
     * @param string $reasonPhrase
     * @throws GuzzleException
     */
    public function testSearchCanHandleBadResponse(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $repository = $this->getRepository($response);

        try {
            $repository->search('abc12');
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     * @param int $statusCode
     * @param string $reasonPhrase
     */
    public function testOccurrencesCanHandleBadResponse(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $repository = $this->getRepository($response);

        try {
            $repository->occurrences('password1');
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @throws GuzzleException
     */
    public function testUnexpectedExceptionThrowsTheException(): void
    {
        $response = new Response(402, [], 'Payment required');

        $repository = $this->getRepository($response);

        try {
            $repository->search('abc12');
        } catch (\RuntimeException $exception) {
            $this->assertNotInstanceOf(AbstractException::class, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    private function getRepository(Response $response): PasswordRepository
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        return new PasswordRepository($client);
    }
}
