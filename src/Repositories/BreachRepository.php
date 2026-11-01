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
use MFlor\Pwned\Models\DataClass;

class BreachRepository extends AbstractServiceRepository
{
    /**
     * Fetch all breaches in HaveIBeenPwned's database
     *
     * @see https://haveibeenpwned.com/API/v3#AllBreaches
     *
     * @param bool|null $isSpamList When set, only returns breaches that are (true) or aren't (false) spam lists
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
    public function getAll(?bool $isSpamList = null): ?array
    {
        return $this->getAllBreaches(null, $isSpamList);
    }

    /**
     * Fetch all breaches in HaveIBeenPwned's database by a domain
     *
     * @see https://haveibeenpwned.com/API/v3#AllBreaches
     *
     * @param string $domain
     * @param bool|null $isSpamList When set, only returns breaches that are (true) or aren't (false) spam lists
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
    public function byDomain(string $domain, ?bool $isSpamList = null): ?array
    {
        return $this->getAllBreaches($domain, $isSpamList);
    }

    /**
     * Get the most recently added breach, based on its AddedDate.
     * This is useful for checking whether a new breach has been loaded
     * before searching for accounts or domains.
     *
     * @see https://haveibeenpwned.com/API/v3#MostRecentBreach
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
    public function latest(): ?Breach
    {
        $data = $this->decodeJson($this->getResponse('latestBreach'));

        return $data instanceof \stdClass ? new Breach($data) : null;
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
     * - truncateResponse (bool, default: true) - When true, only the name of each breach is returned.
     *      Pass false to get full Breach models.
     * - domain (string, default: null) - Filters the result set to only breaches against the domain specified.
     *      It is possible that one site (and consequently domain), is compromised on multiple occasions.
     * - includeUnverified (bool, default: true) - Whether breaches flagged as "unverified" are included.
     *      Pass false to exclude them.
     *
     * When the response is truncated (the default), an array of objects with a single "Name" property
     * is returned. When truncateResponse is false, an array of Breach models is returned.
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
     * Get all data classes along with a description of each,
     * e.g. to explain them to users in a tooltip.
     *
     * @see https://haveibeenpwned.com/API/v3#DataClassDescriptions
     *
     * @return DataClass[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function getDataClassDescriptions(): ?array
    {
        $data = $this->decodeObjectList($this->getResponse('dataClasses/descriptions'));
        if ($data === null) {
            return null;
        }

        return array_map(function (\stdClass $dataClass) {
            return new DataClass($dataClass);
        }, $data);
    }

    /**
     * Search for breached email addresses by the first 6 characters of their SHA-1 hash, using k-anonymity,
     * so the email address being searched for is never sent to the API.
     * Requires an API key with a subscription that includes k-anonymity searches.
     *
     * Returns breach names indexed by the full (lowercase) SHA-1 hash of each address.
     * Per HIBP's terms of use, any results that don't match the address being searched for
     * must be discarded immediately and not stored. Consider using byAccountAnonymously() instead.
     *
     * @see https://haveibeenpwned.com/API/v3#BreachesForHashRange
     *
     * @param string $prefix The first 6 characters of the SHA-1 hash of an email address
     *
     * @return array<string, string[]>|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function searchByRange(string $prefix): ?array
    {
        $prefix = strtolower($prefix);
        $data = $this->decodeObjectList(
            $this->getAuthenticatedResponse(sprintf('breachedaccount/range/%s', rawurlencode($prefix)))
        );
        if ($data === null) {
            return null;
        }

        $results = [];
        foreach ($data as $result) {
            $suffix = $result->hashSuffix ?? null;
            $websites = $result->websites ?? null;
            if (is_string($suffix) && is_array($websites)) {
                $results[$prefix . strtolower($suffix)] = array_values(array_filter($websites, 'is_string'));
            }
        }

        return $results;
    }

    /**
     * Get the names of all breaches an email address appears in, using k-anonymity,
     * so the address itself is never sent to the API.
     * Requires an API key with a subscription that includes k-anonymity searches.
     *
     * Unlike byAccount(), an account that isn't found returns an empty array rather than throwing
     * a NotFoundException, as the k-anonymity API always responds with the whole hash range.
     *
     * @see https://haveibeenpwned.com/API/v3#BreachesForHashRange
     *
     * @param string $account
     *
     * @return string[] Breach names
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byAccountAnonymously(string $account): array
    {
        // Email addresses are case-insensitive, and the API hashes them in lowercase
        $hash = sha1(strtolower(trim($account)));
        $results = $this->searchByRange(substr($hash, 0, 6)) ?? [];

        return $results[$hash] ?? [];
    }

    /**
     * Get all breaches, optionally by domain
     *
     * @param string|null $domain
     * @param bool|null $isSpamList
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
    private function getAllBreaches(?string $domain = null, ?bool $isSpamList = null): ?array
    {
        $query = [];
        if ($domain) {
            $query['domain'] = $domain;
        }
        if ($isSpamList !== null) {
            $query['isSpamList'] = $isSpamList;
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
