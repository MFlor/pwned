<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;

/**
 * Stealer logs are credentials captured by info-stealer malware.
 *
 * All stealer log searches require an API key with a subscription that includes stealer logs,
 * and can only be performed for domains that have been verified on the domain search dashboard.
 * Searching an unverified domain throws a ForbiddenException.
 *
 * @see https://haveibeenpwned.com/API/v3#StealerLogsOverview
 */
class StealerLogRepository extends AbstractServiceRepository
{
    /**
     * Get the website domains an email address has appeared against in stealer logs
     *
     * @see https://haveibeenpwned.com/API/v3#StealerLogsForEmail
     *
     * @param string $email
     *
     * @return string[]|null Website domains, e.g. ["netflix.com", "spotify.com"]
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException If the address has no stealer log entries
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byEmail(string $email): ?array
    {
        return $this->decodeStringList(
            $this->getAuthenticatedResponse(sprintf('stealerLogsByEmail/%s', rawurlencode($email)))
        );
    }

    /**
     * Get the email addresses captured by stealer logs when signing in to a website domain
     *
     * @see https://haveibeenpwned.com/API/v3#StealerLogsForWebsiteDomain
     *
     * @param string $domain The website domain, e.g. "netflix.com"
     *
     * @return string[]|null Email addresses, e.g. ["andy@gmail.com", "jane@gmail.com"]
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byWebsiteDomain(string $domain): ?array
    {
        return $this->decodeStringList(
            $this->getAuthenticatedResponse(sprintf('stealerLogsByWebsiteDomain/%s', rawurlencode($domain)))
        );
    }

    /**
     * Get the email aliases on an email domain that appear in stealer logs,
     * along with the website domains each one appeared against
     *
     * @see https://haveibeenpwned.com/API/v3#StealerLogsForEmailDomain
     *
     * @param string $domain The email domain, e.g. "gmail.com"
     *
     * @return array<int|string, string[]>|null Website domains by alias,
     *      e.g. ["andy" => ["netflix.com"], "jane" => ["netflix.com", "spotify.com"]].
     *      Numeric aliases like "123" become integer keys, as in any PHP array.
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function byEmailDomain(string $domain): ?array
    {
        return $this->decodeStringListMap(
            $this->getAuthenticatedResponse(sprintf('stealerLogsByEmailDomain/%s', rawurlencode($domain)))
        );
    }
}
