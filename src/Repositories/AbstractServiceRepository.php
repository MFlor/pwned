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
        return $this->send('GET', $uri, [
            RequestOptions::QUERY => $this->normalizeQuery($query),
        ]);
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
        return $this->send('GET', $uri, [
            RequestOptions::QUERY => $this->normalizeQuery($query),
            RequestOptions::HEADERS => $this->getAuthenticationHeaders(),
        ]);
    }

    /**
     * @param string $uri
     * @param array<string, string> $body Sent as JSON
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
    protected function postAuthenticatedResponse(string $uri, array $body): ResponseInterface
    {
        return $this->send('POST', $uri, [
            RequestOptions::JSON => $body,
            RequestOptions::HEADERS => $this->getAuthenticationHeaders(),
        ]);
    }

    /**
     * Sends a request, converting API error responses into this library's exceptions
     *
     * @param string $method
     * @param string $uri
     * @param array{
     *     query?: array<string, string>,
     *     headers?: array<string, string>,
     *     json?: array<string, string>
     * } $options Guzzle request options
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
    protected function send(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->client->request($method, $uri, $options);
        } catch (RequestException $exception) {
            throw $this->handleRequestException($exception);
        }
    }

    /**
     * @param ResponseInterface $response
     * @param bool $associative Decode JSON objects as associative arrays instead of stdClass
     *
     * @return mixed The decoded body, or null if it isn't valid JSON
     */
    protected function decodeJson(ResponseInterface $response, bool $associative = false): mixed
    {
        try {
            return json_decode((string) $response->getBody(), $associative, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return null;
        }
    }

    /**
     * Decodes a JSON array of strings, e.g. ["netflix.com", "spotify.com"]
     *
     * @param ResponseInterface $response
     *
     * @return string[]|null Null if the body isn't a valid JSON array
     */
    protected function decodeStringList(ResponseInterface $response): ?array
    {
        $data = $this->decodeJson($response, true);
        if (!is_array($data)) {
            return null;
        }

        return array_values(array_filter($data, 'is_string'));
    }

    /**
     * Decodes a JSON object of string arrays, e.g. {"alias1": ["Adobe"], "alias2": ["Adobe", "Gawker"]}
     *
     * @param ResponseInterface $response
     *
     * Note that numeric keys like "123" become integers, as in any PHP array.
     *
     * @return array<int|string, string[]>|null Null if the body isn't a valid JSON object
     */
    protected function decodeStringListMap(ResponseInterface $response): ?array
    {
        $data = $this->decodeJson($response, true);
        if (!is_array($data)) {
            return null;
        }

        $result = [];
        foreach ($data as $key => $values) {
            if (is_array($values)) {
                $result[$key] = array_values(array_filter($values, 'is_string'));
            }
        }

        return $result;
    }

    /**
     * Decodes a JSON array of objects, e.g. [{"Name": "Adobe"}, {"Name": "Gawker"}]
     *
     * @param ResponseInterface $response
     *
     * @return \stdClass[]|null Null if the body isn't a valid JSON array
     */
    protected function decodeObjectList(ResponseInterface $response): ?array
    {
        $data = $this->decodeJson($response);
        if (!is_array($data)) {
            return null;
        }

        return array_values(array_filter($data, function ($item): bool {
            return $item instanceof \stdClass;
        }));
    }

    /**
     * Without an API key the header is omitted, and the API responds with 401 Unauthorized.
     * (Guzzle 8 rejects null header values, so the header can't be sent empty.)
     *
     * @return array<string, string>
     */
    private function getAuthenticationHeaders(): array
    {
        if ($this->apiKey === null) {
            return [];
        }

        return ['hibp-api-key' => $this->apiKey];
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
        $apiMessage = $this->getApiMessage($response);
        switch ($response->getStatusCode()) {
            case 400:
                return new BadRequestException($reasonPhrase, $apiMessage);
            case 401:
                return new UnauthorizedException($reasonPhrase, $apiMessage);
            case 403:
                return new ForbiddenException($reasonPhrase, $apiMessage);
            case 404:
                return new NotFoundException($reasonPhrase, $apiMessage);
            case 429:
                return new TooManyRequestsException($reasonPhrase, $this->getRetryAfter($response), $apiMessage);
            case 503:
                return new ServiceUnavailableException($reasonPhrase, $apiMessage);
            default:
                return $exception;
        }
    }

    /**
     * Extracts the explanation the API includes in error responses, which is either
     * JSON like {"statusCode": 401, "message": "Access denied due to invalid hibp-api-key."}
     * or plain text.
     */
    private function getApiMessage(ResponseInterface $response): ?string
    {
        $body = trim((string) $response->getBody());
        if ($body === '') {
            return null;
        }

        try {
            $data = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
            if ($data instanceof \stdClass && isset($data->message) && is_string($data->message)) {
                return $data->message;
            }
            return null;
        } catch (\JsonException $exception) {
        }

        // Accept short plain-text explanations, but not HTML error pages (e.g. from Cloudflare)
        if ($body[0] !== '<' && strlen($body) <= 500) {
            return $body;
        }

        return null;
    }

    /**
     * The retry-after header is normally a number of seconds, but may also be an HTTP date
     */
    private function getRetryAfter(ResponseInterface $response): ?int
    {
        $value = trim($response->getHeaderLine('Retry-After'));
        if ($value === '') {
            return null;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return max(0, $timestamp - time());
    }
}
