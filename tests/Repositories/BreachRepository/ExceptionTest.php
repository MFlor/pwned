<?php

namespace MFlor\Pwned\Tests\Repositories\BreachRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Repositories\BreachRepository;
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
    public function testAllBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);
        try {
            $repository->getAll();
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
    public function testGetByDomainBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);

        try {
            $repository->byDomain('adobe.com');
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
    public function testGetByNameBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);

        try {
            $repository->byName('Adobe');
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider authenticatedBadResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     * @param int $statusCode
     * @param string $reasonPhrase
     */
    public function testGetByAccountBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);

        try {
            $repository->byAccount('test@example.com');
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
    public function testGetDataClassesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ) {
        $repository = $this->getRepository($response);

        try {
            $repository->getDataClasses();
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    public function testUnexpectedExceptionThrowsTheException()
    {
        $response = new Response(402, [], 'Payment required');

        $repository = $this->getRepository($response);

        try {
            $repository->getAll();
        } catch (\Exception $exception) {
            $this->assertNotInstanceOf(AbstractException::class, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    private function getRepository(Response $response): BreachRepository
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        return new BreachRepository($client);
    }
}
