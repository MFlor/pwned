<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
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
     * @param array<string, string|bool> $query
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
                RequestOptions::QUERY => $this->normalizeQuery($query)
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
        // Without an API key the header is omitted, and the API responds with 401 Unauthorized.
        // (Guzzle 8 rejects null header values, so the header can't be sent empty.)
        $headers = [];
        if ($this->apiKey !== null) {
            $headers['hibp-api-key'] = $this->apiKey;
        }

        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $this->normalizeQuery($query),
                RequestOptions::HEADERS => $headers,
            ]);
        } catch (RequestException $exception) {
            throw $this->handleRequestException($exception);
        }
    }

    /**
     * The HIBP API only understands the literal strings "true" and "false" for boolean
     * parameters, but Guzzle (via http_build_query) serialises booleans as "1" and "0",
     * which the API silently ignores.
     *
     * @param array<string, string|bool> $query
     *
     * @return array<string, string>
     */
    private function normalizeQuery(array $query): array
    {
        return array_map(static function ($value): string {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            return $value;
        }, $query);
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
        // Only BadResponseException (4xx/5xx) carries a response in both Guzzle 7 and 8;
        // Guzzle 8 removed getResponse() from RequestException itself.
        if (!$exception instanceof BadResponseException) {
            return $exception;
        }
        $response = $exception->getResponse();
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
