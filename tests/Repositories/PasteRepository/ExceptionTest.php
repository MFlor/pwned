<?php

namespace MFlor\Pwned\Tests\Repositories\PasteRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Repositories\PasteRepository;
use MFlor\Pwned\Tests\Repositories\RepositoryTestCase;

class ExceptionTest extends RepositoryTestCase
{
    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     */
    public function testGetByAccountCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);

        try {
            $result = $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
            return;
        }

        if ($expectedException === NotFoundException::class) {
            $this->assertNull($result);
            return;
        }
        $this->fail('Failed to throw exception!');
    }

    public function testUnexpectedExceptionThrowsTheException()
    {
        $response = new Response(402, [], 'Payment required');

        $repository = $this->getRepository($response);

        try {
            $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->assertNotInstanceOf(BadRequestException::class, $exception);
            $this->assertNotInstanceOf(ForbiddenException::class, $exception);
            $this->assertNotInstanceOf(NotFoundException::class, $exception);
            $this->assertNotInstanceOf(TooManyRequestsException::class, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    private function getRepository(Response $response): PasteRepository
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        return new PasteRepository($client);
    }
}
