<?php

namespace MFlor\Pwned\Tests\Repositories\BreachRepository;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
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
     * @throws GuzzleException
     */
    public function testAllBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
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
     * @throws GuzzleException
     */
    public function testGetByDomainBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
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
     * @throws GuzzleException
     */
    public function testGetByNameBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
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
     * @throws GuzzleException
     */
    public function testGetByAccountBreachesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
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
     * @throws GuzzleException
     */
    public function testGetDataClassesCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $repository = $this->getRepository($response);

        try {
            $repository->getDataClasses();
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
            $repository->getAll();
        } catch (\Exception $exception) {
            $this->assertNotInstanceOf(AbstractException::class, $exception);
            return;
        }
        $this->fail('Failed throwing an exception!');
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testRequestExceptionIsForwarded(): void
    {
        $response = new RequestException('Error Communicating with Server', new Request('GET', 'test'));

        $repository = $this->getRepository($response);

        $this->expectException(RequestException::class, 'Error Communicating with Server');

        $repository->getAll();
    }

    /**
     * @param Request|RequestException $response
     * @return BreachRepository
     */
    private function getRepository($response): BreachRepository
    {
        $mock = new MockHandler([$response]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        return new BreachRepository($client);
    }
}
