<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Models\SubscribedDomain;

/**
 * Domain search lets organisations find all breached email addresses on domains they control.
 * Domains must first be verified, either on the dashboard or via the verification methods below.
 * All methods require an API key.
 *
 * @see https://haveibeenpwned.com/API/v3#DomainSearchOverview
 */
class DomainRepository extends AbstractServiceRepository
{
    /**
     * Get all breached email aliases on a verified domain, and the breaches each one appears in
     *
     * @see https://haveibeenpwned.com/API/v3#BreachesForDomain
     *
     * @param string $domain
     *
     * @return array<int|string, string[]>|null Breach names by alias,
     *      e.g. ["alias1" => ["Adobe"], "alias2" => ["Adobe", "Gawker"]].
     *      Numeric aliases like "123" become integer keys, as in any PHP array.
     *
     * @throws BadRequestException
     * @throws ForbiddenException If the domain hasn't been verified
     * @throws NotFoundException If no addresses on the domain have been breached
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function search(string $domain): ?array
    {
        return $this->decodeStringListMap(
            $this->getAuthenticatedResponse(sprintf('breachedDomain/%s', rawurlencode($domain)))
        );
    }

    /**
     * Get all domains that have been verified and added to the domain search dashboard
     *
     * @see https://haveibeenpwned.com/API/v3#SubscribedDomains
     *
     * @return SubscribedDomain[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function subscribed(): ?array
    {
        $data = $this->decodeObjectList($this->getAuthenticatedResponse('subscribedDomains'));
        if ($data === null) {
            return null;
        }

        return array_map(function (\stdClass $domain) {
            return new SubscribedDomain($domain);
        }, $data);
    }

    /**
     * Step 1 of verifying a domain by DNS: generate the value for a TXT record,
     * which must then be added to the domain's DNS before calling verifyDnsToken().
     *
     * @see https://haveibeenpwned.com/API/v3#VerifyDomainViaDns
     *
     * @param string $domain
     *
     * @return string|null The TXT record value, e.g. "hibp-verify=dweb_abc123xyz"
     *
     * @throws BadRequestException If the domain is invalid
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function generateDnsToken(string $domain): ?string
    {
        $data = $this->decodeJson(
            $this->postAuthenticatedResponse('domainVerification/generateDnsToken', ['DomainName' => $domain])
        );

        if ($data instanceof \stdClass && isset($data->txtRecordValue) && is_string($data->txtRecordValue)) {
            return $data->txtRecordValue;
        }

        return null;
    }

    /**
     * Step 2 of verifying a domain by DNS: check that the TXT record from generateDnsToken() has been set.
     * DNS changes can take a while to propagate, so this may need to be retried.
     *
     * @see https://haveibeenpwned.com/API/v3#VerifyDomainViaDns
     *
     * @param string $domain
     *
     * @throws BadRequestException If verification failed - see getApiMessage() for the reason,
     *      e.g. "No hibp-verify TXT records were found on the domain."
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function verifyDnsToken(string $domain): void
    {
        $this->postAuthenticatedResponse('domainVerification/verifyDnsToken', ['DomainName' => $domain]);
    }

    /**
     * Verify a domain by email: sends an email to an alias on the domain, containing a link
     * that must be clicked to complete verification.
     *
     * @see https://haveibeenpwned.com/API/v3#VerifyDomainViaEmail
     *
     * @param string $domain
     * @param string $alias One of: admin, hostmaster, info, security, webmaster
     *
     * @throws BadRequestException If the domain or alias is invalid - see getApiMessage() for the reason
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws UnauthorizedException
     * @throws GuzzleException
     */
    public function sendVerificationEmail(string $domain, string $alias): void
    {
        $this->postAuthenticatedResponse('domainVerification/sendEmail', [
            'DomainName' => $domain,
            'EmailAlias' => $alias,
        ]);
    }
}
