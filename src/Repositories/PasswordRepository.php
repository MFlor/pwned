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
use MFlor\Pwned\Models\Password;
use Psr\Http\Message\ResponseInterface;

class PasswordRepository
{
    private Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Search for passwords in the HaveIBeenPwned database,
     * by the passwords first five characters, when hashed with SHA-1
     * Returns an array of Password models
     * @see Password
     * @see https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange
     *
     * @param string $prefix
     * @param bool $pad
     * @return Password[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function search(string $prefix, bool $pad = true): ?array
    {
        $response = $this->getResponse(sprintf('range/%s', $prefix), $pad);

        $data = preg_split('/\n/', $response->getBody()->getContents(), -1, PREG_SPLIT_NO_EMPTY);

        if ($data) {
            return array_map(function (string $string) use ($prefix) {
                return (new Password())->fromString($string, $prefix);
            }, $data);
        }
        return null;
    }

    /**
     * Returns the occurrence count for the given password.
     * The count is how many times it appears in leaks, known by HaveIBeenPwned.
     *
     * @param string $password
     * @param bool $pad
     * @return int
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     */
    public function occurrences(string $password, bool $pad = true): int
    {
        $hash = hash('sha1', $password);
        if ($passwords = $this->search(substr($hash, 0, 5), $pad)) {
            foreach ($passwords as $pswrd) {
                if ($pswrd->getHash() === $hash) {
                    return $pswrd->getoccurrences();
                }
            }
        }

        return 0;
    }

    /**
     * @param string $uri
     * @param bool $pad
     *
     * @return ResponseInterface
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    private function getResponse(string $uri, bool $pad = true): ResponseInterface
    {
        try {
            $headers = [];
            if ($pad) {
                $headers = [
                    'Add-Padding' => 'true'
                ];
            }
            return $this->client->get($uri, [
                RequestOptions::HEADERS => $headers
            ]);
        } catch (RequestException $exception) {
            if (!$response = $exception->getResponse()) {
                throw $exception;
            }
            $reasonPhrase = $response->getReasonPhrase();
            switch ($response->getStatusCode()) {
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
}
