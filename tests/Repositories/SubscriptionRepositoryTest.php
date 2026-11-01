<?php

namespace MFlor\Pwned\Tests\Repositories;

use GuzzleHttp\Psr7\Response;
use MFlor\Pwned\Exceptions\AbstractException;
use MFlor\Pwned\Models\Subscription;
use MFlor\Pwned\Repositories\SubscriptionRepository;

class SubscriptionRepositoryTest extends RepositoryTestCase
{
    public function testCanGetStatus(): void
    {
        $repository = new SubscriptionRepository($this->getClientWithResponse((string) json_encode([
            'SubscriptionName' => 'Pro 2',
            'Description' => 'Up to 500 email addresses per domain and 100 domains.',
            'SubscribedUntil' => '2027-05-01T00:00:00Z',
            'Rpm' => 500,
            'DomainSearchMaxBreachedAccounts' => 500,
            'MaxBreachedDomains' => 100,
            'IncludesStealerLogs' => true,
            'IncludesBulkDomainAdd' => true,
            'IncludesAutoSubdomainVerification' => false,
            'IncludesCustomerDomains' => false,
            'IncludesKAnon' => true,
        ])), 'api-key');

        $subscription = $repository->status();

        $this->assertRequest('subscription/status', '', ['hibp-api-key' => 'api-key']);
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertSame('Pro 2', $subscription->getName());
        $this->assertSame(500, $subscription->getRpm());
        $this->assertTrue($subscription->includesKAnon());
    }

    public function testStatusReturnsNullOnInvalidJson(): void
    {
        $repository = $this->getRepositoryWithResponse(SubscriptionRepository::class, new Response(200, [], 'invalid'));

        $this->assertNull($repository->status());
    }

    /**
     * @dataProvider authenticatedBadResponseProvider
     */
    public function testStatusCanHandleBadResponses(
        Response $response,
        string $expectedException,
        int $statusCode,
        string $reasonPhrase
    ): void {
        $repository = $this->getRepositoryWithResponse(SubscriptionRepository::class, $response);
        try {
            $repository->status();
            $this->fail('Failed throwing an exception!');
        } catch (AbstractException $exception) {
            $this->assertException($expectedException, $exception, $statusCode, $reasonPhrase);
        }
    }
}
