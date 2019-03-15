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
     */
    public function testAllBreachesCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);
        try {
            $repository->getAll();
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     */
    public function testGetByDomainBreachesCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);

        try {
            $repository->byDomain('adobe.com');
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
            return;
        }

        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     */
    public function testGetByNameBreachesCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);

        try {
            $repository->byName('Adobe');
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     */
    public function testGetByAccountBreachesCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);

        try {
            $repository->byAccount('test@example.com');
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @dataProvider badResponseProvider
     *
     * @param Response $response
     * @param string $expectedException
     */
    public function testGetDataClassesCanHandleBadResponses(Response $response, string $expectedException)
    {
        $repository = $this->getRepository($response);

        try {
            $repository->getDataClasses();
        } catch (\Exception $exception) {
            $this->assertInstanceOf($expectedException, $exception);
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
