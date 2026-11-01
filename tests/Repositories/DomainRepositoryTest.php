<?php

namespace MFlor\Pwned\Tests\Repositories;

use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Models\SubscribedDomain;
use MFlor\Pwned\Repositories\DomainRepository;

class DomainRepositoryTest extends RepositoryTestCase
{
    public function testCanSearchDomain(): void
    {
        $repository = new DomainRepository(
            $this->getClientWithResponse('{"alias1":["Adobe"],"alias2":["Adobe","Gawker","Stratfor"]}'),
            'api-key'
        );

        $results = $repository->search('example.com');

        $this->assertRequest('breachedDomain/example.com', '', ['hibp-api-key' => 'api-key']);
        $this->assertSame([
            'alias1' => ['Adobe'],
            'alias2' => ['Adobe', 'Gawker', 'Stratfor'],
        ], $results);
    }

    public function testCanGetSubscribedDomains(): void
    {
        $repository = new DomainRepository($this->getClientWithResponse((string) json_encode([
            [
                'DomainName' => 'example.com',
                'PwnCount' => 10,
                'PwnCountExcludingSpamLists' => 8,
                'PwnCountExcludingSpamListsAtLastSubscriptionRenewal' => 7,
                'NextSubscriptionRenewal' => '2027-01-01T00:00:00Z',
            ],
            [
                'DomainName' => 'example.org',
                'PwnCount' => null,
                'PwnCountExcludingSpamLists' => null,
                'PwnCountExcludingSpamListsAtLastSubscriptionRenewal' => null,
                'NextSubscriptionRenewal' => null,
            ],
        ])), 'api-key');

        $domains = $repository->subscribed();

        $this->assertRequest('subscribedDomains', '', ['hibp-api-key' => 'api-key']);
        $this->assertIsArray($domains);
        $this->assertCount(2, $domains);
        $this->assertInstanceOf(SubscribedDomain::class, $domains[0]);
        $this->assertSame('example.com', $domains[0]->getDomainName());
        $this->assertSame(10, $domains[0]->getPwnCount());
        $this->assertSame('example.org', $domains[1]->getDomainName());
        $this->assertNull($domains[1]->getPwnCount());
    }

    public function testCanGenerateDnsToken(): void
    {
        $repository = new DomainRepository(
            $this->getClientWithResponse('{"txtRecordValue":"hibp-verify=dweb_abc123xyz"}'),
            'api-key'
        );

        $token = $repository->generateDnsToken('example.com');

        $this->assertRequest(
            'domainVerification/generateDnsToken',
            '',
            ['hibp-api-key' => 'api-key'],
            'POST'
        );
        $this->assertJsonBody(['DomainName' => 'example.com']);
        $this->assertSame('hibp-verify=dweb_abc123xyz', $token);
    }

    public function testGenerateDnsTokenReturnsNullWithoutToken(): void
    {
        $repository = new DomainRepository($this->getClientWithResponse('{}'), 'api-key');

        $this->assertNull($repository->generateDnsToken('example.com'));
    }

    public function testCanVerifyDnsToken(): void
    {
        $repository = new DomainRepository($this->getClientWithResponse(), 'api-key');

        $repository->verifyDnsToken('example.com');

        $this->assertRequest('domainVerification/verifyDnsToken', '', ['hibp-api-key' => 'api-key'], 'POST');
        $this->assertJsonBody(['DomainName' => 'example.com']);
    }

    public function testFailedDnsVerificationExplainsWhy(): void
    {
        $repository = $this->getRepositoryWithResponse(DomainRepository::class, new Response(
            400,
            ['Content-Type' => 'application/json'],
            '{"message": "No hibp-verify TXT records were found on the domain."}'
        ));

        try {
            $repository->verifyDnsToken('example.com');
            $this->fail('Failed throwing an exception!');
        } catch (BadRequestException $exception) {
            $this->assertSame('No hibp-verify TXT records were found on the domain.', $exception->getApiMessage());
        }
    }

    public function testCanSendVerificationEmail(): void
    {
        $repository = new DomainRepository($this->getClientWithResponse(), 'api-key');

        $repository->sendVerificationEmail('example.com', 'security');

        $this->assertRequest('domainVerification/sendEmail', '', ['hibp-api-key' => 'api-key'], 'POST');
        $this->assertJsonBody(['DomainName' => 'example.com', 'EmailAlias' => 'security']);
    }

    /**
     * @dataProvider methodProvider
     */
    public function testMethodsReturnNullOnInvalidJson(string $method, array $arguments): void
    {
        $repository = $this->getRepositoryWithResponse(DomainRepository::class, new Response(200, [], 'invalid'));

        $this->assertNull($repository->$method(...$arguments));
    }

    /**
     * @return array<string, array{0: string, 1: string[]}>
     */
    public function methodProvider(): array
    {
        return [
            'search' => ['search', ['example.com']],
            'subscribed' => ['subscribed', []],
            'generateDnsToken' => ['generateDnsToken', ['example.com']],
        ];
    }

    /**
     * @dataProvider authenticatedBadResponseProvider
     */
    public function testMethodsCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $methods = $this->methodProvider() + [
            'verifyDnsToken' => ['verifyDnsToken', ['example.com']],
            'sendVerificationEmail' => ['sendVerificationEmail', ['example.com', 'security']],
        ];
        foreach ($methods as [$method, $arguments]) {
            $repository = $this->getRepositoryWithResponse(DomainRepository::class, $response);
            try {
                $repository->$method(...$arguments);
                $this->fail(sprintf('%s failed throwing an exception!', $method));
            } catch (AbstractException $exception) {
                $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
            }
        }
    }
}
