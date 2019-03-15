<?php

namespace MFlor\Pwned\Tests\Repositories\PasteRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Repositories\PasteRepository;
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
     */
    public function testGetByAccountCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);

        try {
            $result = $repository->byAccount('test@example.com');
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
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
            $this->assertNotInstanceOf(AbstractException::class, $exception);
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
