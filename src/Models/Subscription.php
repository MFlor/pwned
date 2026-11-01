<?php

namespace MFlor\Pwned\Models;

/**
 * The status of the subscription the API key belongs to
 */
class Subscription
{
    private string $name;
    private string $description;
    private ?\DateTime $subscribedUntil = null;
    private int $rpm;
    private int $domainSearchMaxBreachedAccounts;
    private ?int $maxBreachedDomains;
    private bool $includesStealerLogs;
    private bool $includesBulkDomainAdd;
    private bool $includesAutoSubdomainVerification;
    private bool $includesCustomerDomains;
    private bool $includesKAnon;

    public function __construct(?object $subscription = null)
    {
        $this->name = $subscription->SubscriptionName ?? '';
        $this->description = $subscription->Description ?? '';
        if ($subscribedUntil = $subscription->SubscribedUntil ?? null) {
            try {
                $this->subscribedUntil = new \DateTime($subscribedUntil);
            } catch (\Exception $exception) {
                $this->subscribedUntil = null;
            }
        }
        $this->rpm = $subscription->Rpm ?? 0;
        $this->domainSearchMaxBreachedAccounts = $subscription->DomainSearchMaxBreachedAccounts ?? 0;
        $this->maxBreachedDomains = $subscription->MaxBreachedDomains ?? null;
        $this->includesStealerLogs = $subscription->IncludesStealerLogs ?? false;
        $this->includesBulkDomainAdd = $subscription->IncludesBulkDomainAdd ?? false;
        $this->includesAutoSubdomainVerification = $subscription->IncludesAutoSubdomainVerification ?? false;
        $this->includesCustomerDomains = $subscription->IncludesCustomerDomains ?? false;
        $this->includesKAnon = $subscription->IncludesKAnon ?? false;
    }

    /**
     * The name of the plan and level, for example "Pro 2"
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * A human-readable sentence explaining the scope of the subscription
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    public function getSubscribedUntil(): ?\DateTime
    {
        return $this->subscribedUntil;
    }

    /**
     * The rate limit in requests per minute for searching breaches by email address
     */
    public function getRpm(): int
    {
        return $this->rpm;
    }

    /**
     * The size of the largest domain the subscription can search,
     * in breached email addresses (excluding those only in spam lists)
     */
    public function getDomainSearchMaxBreachedAccounts(): int
    {
        return $this->domainSearchMaxBreachedAccounts;
    }

    /**
     * The number of domains the subscription can add, or null if there's no limit
     */
    public function getMaxBreachedDomains(): ?int
    {
        return $this->maxBreachedDomains;
    }

    public function includesStealerLogs(): bool
    {
        return $this->includesStealerLogs;
    }

    /**
     * Whether domains can be verified via the API (by DNS or email)
     */
    public function includesBulkDomainAdd(): bool
    {
        return $this->includesBulkDomainAdd;
    }

    public function includesAutoSubdomainVerification(): bool
    {
        return $this->includesAutoSubdomainVerification;
    }

    public function includesCustomerDomains(): bool
    {
        return $this->includesCustomerDomains;
    }

    /**
     * Whether k-anonymity email searches are included
     */
    public function includesKAnon(): bool
    {
        return $this->includesKAnon;
    }
}
