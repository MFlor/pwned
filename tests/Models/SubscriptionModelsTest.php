<?php

namespace MFlor\Pwned\Tests\Models;

use MFlor\Pwned\Models\SubscribedDomain;
use MFlor\Pwned\Models\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionModelsTest extends TestCase
{
    public function testCanConstructEmptySubscription(): void
    {
        $subscription = new Subscription();

        $this->assertSame('', $subscription->getName());
        $this->assertSame('', $subscription->getDescription());
        $this->assertNull($subscription->getSubscribedUntil());
        $this->assertSame(0, $subscription->getRpm());
        $this->assertSame(0, $subscription->getDomainSearchMaxBreachedAccounts());
        $this->assertNull($subscription->getMaxBreachedDomains());
        $this->assertFalse($subscription->includesStealerLogs());
        $this->assertFalse($subscription->includesBulkDomainAdd());
        $this->assertFalse($subscription->includesAutoSubdomainVerification());
        $this->assertFalse($subscription->includesCustomerDomains());
        $this->assertFalse($subscription->includesKAnon());
    }

    public function testCanConstructSubscriptionWithData(): void
    {
        $subscription = new Subscription((object) [
            'SubscriptionName' => 'Pro 2',
            'Description' => 'Up to 500 email addresses per domain and 100 domains.',
            'SubscribedUntil' => '2027-05-01T12:30:00Z',
            'Rpm' => 500,
            'DomainSearchMaxBreachedAccounts' => 1000,
            'MaxBreachedDomains' => 100,
            'IncludesStealerLogs' => true,
            'IncludesBulkDomainAdd' => true,
            'IncludesAutoSubdomainVerification' => true,
            'IncludesCustomerDomains' => true,
            'IncludesKAnon' => true,
        ]);

        $this->assertSame('Pro 2', $subscription->getName());
        $this->assertSame('Up to 500 email addresses per domain and 100 domains.', $subscription->getDescription());
        $this->assertInstanceOf(\DateTime::class, $subscription->getSubscribedUntil());
        $this->assertSame('2027-05-01T12:30:00+00:00', $subscription->getSubscribedUntil()->format('c'));
        $this->assertSame(500, $subscription->getRpm());
        $this->assertSame(1000, $subscription->getDomainSearchMaxBreachedAccounts());
        $this->assertSame(100, $subscription->getMaxBreachedDomains());
        $this->assertTrue($subscription->includesStealerLogs());
        $this->assertTrue($subscription->includesBulkDomainAdd());
        $this->assertTrue($subscription->includesAutoSubdomainVerification());
        $this->assertTrue($subscription->includesCustomerDomains());
        $this->assertTrue($subscription->includesKAnon());
    }

    public function testInvalidSubscribedUntilIsNull(): void
    {
        $subscription = new Subscription((object) ['SubscribedUntil' => 'not a date']);

        $this->assertNull($subscription->getSubscribedUntil());
    }

    public function testCanConstructEmptySubscribedDomain(): void
    {
        $domain = new SubscribedDomain();

        $this->assertSame('', $domain->getDomainName());
        $this->assertNull($domain->getPwnCount());
        $this->assertNull($domain->getPwnCountExcludingSpamLists());
        $this->assertNull($domain->getPwnCountExcludingSpamListsAtLastSubscriptionRenewal());
        $this->assertNull($domain->getNextSubscriptionRenewal());
    }

    public function testCanConstructSubscribedDomainWithData(): void
    {
        $domain = new SubscribedDomain((object) [
            'DomainName' => 'example.com',
            'PwnCount' => 10,
            'PwnCountExcludingSpamLists' => 8,
            'PwnCountExcludingSpamListsAtLastSubscriptionRenewal' => 7,
            'NextSubscriptionRenewal' => '2027-01-01T00:00:00Z',
        ]);

        $this->assertSame('example.com', $domain->getDomainName());
        $this->assertSame(10, $domain->getPwnCount());
        $this->assertSame(8, $domain->getPwnCountExcludingSpamLists());
        $this->assertSame(7, $domain->getPwnCountExcludingSpamListsAtLastSubscriptionRenewal());
        $this->assertInstanceOf(\DateTime::class, $domain->getNextSubscriptionRenewal());
        $this->assertSame('2027-01-01', $domain->getNextSubscriptionRenewal()->format('Y-m-d'));
    }

    public function testInvalidNextSubscriptionRenewalIsNull(): void
    {
        $domain = new SubscribedDomain((object) ['NextSubscriptionRenewal' => 'not a date']);

        $this->assertNull($domain->getNextSubscriptionRenewal());
    }
}
