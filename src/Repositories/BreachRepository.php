<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Models\Breach;

class BreachRepository extends AbstractServiceRepository
{
    /**
     * Fetch all breaches in HaveIBeenPwned's database
     *
     * @see https://haveibeenpwned.com/API/v3#AllBreaches
     *
     * @return Breach[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     *
     */
    public function getAll(): ?array
    {
        return $this->getAllBreaches();
    }

    /**
     * Fetch all breaches in HaveIBeenPwned's database by a domain
     *
     * @see https://haveibeenpwned.com/API/v3#AllBreaches
     *
     * @param string $domain
     *
     * @return Breach[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byDomain(string $domain): ?array
    {
        return $this->getAllBreaches($domain);
    }

    /**
     * Get a breach by its name
     *
     * @see https://haveibeenpwned.com/API/v3#SingleBreach
     *
     * @param string $name
     *
     * @return Breach|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byName(string $name): ?Breach
    {
        $response = $this->getResponse(sprintf('breach/%s', $name));

        try {
            $data = (object) json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            return new Breach($data);
        } catch (\JsonException $exception) {
        }

        return null;
    }

    /**
     * Get all breaches for an account
     *
     * Options:
     * - truncateResponse (bool):false - Returns only the name of the breach.
     *      By default, the response is truncated
     * - domain (string):null - Filters the result set to only breaches against the domain specified.
     *      It is possible that one site (and consequently domain), is compromised on multiple occasions.
     * - includeUnverified (bool):false - Returns breaches that have been flagged as "unverified".
     *      By default, unverified breaches are included when performing a search.
     *
     * @see https://haveibeenpwned.com/API/v3#BreachesForAccount
     *
     * @param string $account
     * @param array<string, string|bool> $options
     *
     * @return array<Breach>|array<mixed>|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws UnauthorizedException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function byAccount(string $account, array $options = []): ?array
    {
        $response = $this->getAuthenticatedResponse(sprintf('breachedaccount/%s', urlencode($account)), $options);
        try {
            $data = (array) json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            if (key_exists('truncateResponse', $options) && ($options['truncateResponse'] === false)) {
                return $this->mapBreaches($data);
            }
            return $data;
        } catch (\JsonException $exception) {
        }

        return null;
    }

    /**
     * A "data class" is an attribute of a record compromised in a breach.
     * For example, many breaches expose data classes such as "Email addresses" and "Passwords".
     * The values returned by this service are ordered alphabetically in a string array and will expand over time
     * as new breaches expose previously unseen classes of data.
     *
     * @see https://haveibeenpwned.com/API/v3#AllDataClasses
     *
     * @return array<mixed>|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function getDataClasses(): ?array
    {
        $response = $this->getResponse('dataclasses');

        try {
            return (array) json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
        }

        return null;
    }

    /**
     * Get all breaches, optionally by domain
     *
     * @param string|null $domain
     *
     * @return Breach[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    private function getAllBreaches(?string $domain = null): ?array
    {
        $query = [];
        if ($domain) {
            $query['domain'] = $domain;
        }

        $response = $this->getResponse('breaches', $query);

        try {
            $data = (array) json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            return $this->mapBreaches($data);
        } catch (\JsonException $exception) {
        }

        return null;
    }

    /**
     * @param array<mixed> $breaches
     * @return Breach[]
     */
    private function mapBreaches(array $breaches): array
    {
        return array_reduce($breaches, function (array $breaches, $data) {
            if ($data instanceof \stdClass) {
                $breaches[] = new Breach($data);
            }
            return $breaches;
        }, []);
    }
}
