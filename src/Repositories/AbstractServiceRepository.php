<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractServiceRepository
{
    private Client $client;
    private ?string $apiKey;

    public function __construct(Client $client, ?string $apiKey = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $uri
     * @param array<string, string> $query
     *
     * @return ResponseInterface
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws GuzzleException
     * @throws NotFoundException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    protected function getResponse(string $uri, array $query = []): ResponseInterface
    {
        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $query
            ]);
        } catch (RequestException $exception) {
            throw $this->handleRequestException($exception);
        }
    }

    /**
     * @param string $uri
     * @param array<string, string|bool> $query
     *
     * @return ResponseInterface
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    protected function getAuthenticatedResponse(string $uri, array $query = []): ResponseInterface
    {
        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $query,
                RequestOptions::HEADERS => [
                    'hibp-api-key' => $this->apiKey
                ]
            ]);
        } catch (RequestException $exception) {
            throw $this->handleRequestException($exception);
        }
    }

    /**
     * @param RequestException $exception
     *
     * @return \RuntimeException
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    protected function handleRequestException(RequestException $exception): \RuntimeException
    {
        if (!$response = $exception->getResponse()) {
            return $exception;
        }
        $reasonPhrase = $response->getReasonPhrase();
        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestException($reasonPhrase);
            case 401:
                return new UnauthorizedException($reasonPhrase);
            case 403:
                return new ForbiddenException($reasonPhrase);
            case 404:
                return new NotFoundException($reasonPhrase);
            case 429:
                return new TooManyRequestsException($reasonPhrase);
            case 503:
                return new ServiceUnavailableException($reasonPhrase);
            default:
                return $exception;
        }
    }
}
