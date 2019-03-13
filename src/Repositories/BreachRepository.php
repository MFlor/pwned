<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Models\Breach;
use Psr\Http\Message\ResponseInterface;

class BreachRepository
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch all breaches in HaveIBeenPwned's database
     * @return array|null
     *
     * @see https://haveibeenpwned.com/API/v2#AllBreaches
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    public function getAll(): ?array
    {
        return $this->getAllBreaches();
    }

    /**
     * Fetch all breaches in HaveIBeenPwned's database by a domain
     *
     * @see https://haveibeenpwned.com/API/v2#AllBreaches
     *
     * @param string $domain
     *
     * @return array|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    public function byDomain(string $domain): ?array
    {
        return $this->getAllBreaches($domain);
    }

    /**
     * Get a breach by its name
     *
     * @see https://haveibeenpwned.com/API/v2#SingleBreach
     *
     * @param string $name
     *
     * @return Breach|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    public function byName(string $name): ?Breach
    {
        $response = $this->getResponse(sprintf('breach/%s', $name));

        $data = json_decode($response->getBody()->getContents());

        if (json_last_error() === JSON_ERROR_NONE) {
            return new Breach($data);
        }

        return null;
    }

    /**
     * Get all breaches for an account
     *
     * Options:
     * - truncateResponse (bool):true - Returns only the name of the breach.
     * - domain (string):null - Filters the result set to only breaches against the domain specified.
     *      It is possible that one site (and consequently domain), is compromised on multiple occasions.
     * - includeUnverified (bool):false - Returns breaches that have been flagged as "unverified".
     *      By default, only verified breaches are returned when performing a search.
     *
     * @see https://haveibeenpwned.com/API/v2#BreachesForAccount
     *
     * @param string $account
     * @param array $options
     *
     * @return array|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    public function byAccount(string $account, array $options = []): ?array
    {
        $response = $this->getResponse(sprintf('breachedaccount/%s', urlencode($account)), $options);
        $data = json_decode($response->getBody()->getContents());
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($options['truncateResponse']) && $options['truncateResponse'] === false) {
                return $this->mapBreaches($data);
            }

            return $data;
        }

        return null;
    }

    /**
     * A "data class" is an attribute of a record compromised in a breach.
     * For example, many breaches expose data classes such as "Email addresses" and "Passwords".
     * The values returned by this service are ordered alphabetically in a string array and will expand over time
     * as new breaches expose previously unseen classes of data.
     *
     * @see https://haveibeenpwned.com/API/v2#AllDataClasses
     *
     * @return array|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    public function getDataClasses()
    {
        $response = $this->getResponse('dataclasses');

        $data = json_decode($response->getBody()->getContents(), JSON_PRETTY_PRINT);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        }
        return null;
    }

    /**
     * Get all breaches, optionally by domain
     *
     * @param string|null $domain
     *
     * @return array|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     */
    private function getAllBreaches(string $domain = null): ?array
    {
        $query = [];
        if ($domain) {
            $query['domain'] = $domain;
        }

        $response = $this->getResponse('breaches', $query);

        $data = json_decode($response->getBody()->getContents());
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->mapBreaches($data);
        }
        return null;
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
     * @throws TooManyRequestsException
     */
    private function getResponse(string $uri, array $query = []): ?ResponseInterface
    {
        try {
            return $this->client->get($uri, [
                RequestOptions::QUERY => $query
            ]);
        } catch (ClientException $exception) {
            $reasonPhrase = $exception->getResponse()->getReasonPhrase();
            switch ($exception->getResponse()->getStatusCode()) {
                case 400:
                    throw new BadRequestException($reasonPhrase);
                    break;
                case 403:
                    throw new ForbiddenException($reasonPhrase);
                    break;
                case 404:
                    throw new NotFoundException($reasonPhrase);
                    break;
                case 429:
                    throw new TooManyRequestsException($reasonPhrase);
                    break;
                default:
                    throw $exception;
            }
        }
    }

    /**
     * @param array $breaches
     * @return array
     */
    private function mapBreaches(array $breaches): array
    {
        return array_map(function (\stdClass $breach) {
            return new Breach($breach);
        }, $breaches);
    }
}
