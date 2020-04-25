<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Client;
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
    /** @var Client */
    private $client;
    /** @var string|null */
    private $apiKey;

    public function __construct(Client $client, string $apiKey = null)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $uri
     * @param array $query
     *
     * @return ResponseInterface|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     */
    protected function getResponse(string $uri, array $query = []): ?ResponseInterface
    {
        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $query
            ]);
        } catch (RequestException $exception) {
            $reasonPhrase = $exception->getResponse()->getReasonPhrase();
            switch ($exception->getResponse()->getStatusCode()) {
                case 400:
                    throw new BadRequestException($reasonPhrase);
                case 403:
                    throw new ForbiddenException($reasonPhrase);
                case 404:
                    throw new NotFoundException($reasonPhrase);
                case 429:
                    throw new TooManyRequestsException($reasonPhrase);
                case 503:
                    throw new ServiceUnavailableException($reasonPhrase);
                default:
                    throw $exception;
            }
        }
    }

    /**
     * @param string $uri
     * @param array $query
     *
     * @return ResponseInterface|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws ServiceUnavailableException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     */
    protected function getAuthenticatedResponse(string $uri, array $query = []): ?ResponseInterface
    {
        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $query,
                RequestOptions::HEADERS => [
                    'hibp-api-key' => $this->apiKey
                ]
            ]);
        } catch (RequestException $exception) {
            $reasonPhrase = $exception->getResponse()->getReasonPhrase();
            switch ($exception->getResponse()->getStatusCode()) {
                case 400:
                    throw new BadRequestException($reasonPhrase);
                case 401:
                    throw new UnauthorizedException($reasonPhrase);
                case 403:
                    throw new ForbiddenException($reasonPhrase);
                case 404:
                    throw new NotFoundException($reasonPhrase);
                case 429:
                    throw new TooManyRequestsException($reasonPhrase);
                case 503:
                    throw new ServiceUnavailableException($reasonPhrase);
                default:
                    throw $exception;
            }
        }
    }
}
