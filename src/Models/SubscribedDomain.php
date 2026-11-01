<?php

namespace MFlor\Pwned\Models;

/**
 * A domain that has been verified and added to the domain search dashboard
 */
class SubscribedDomain
{
    private string $domainName;
    private ?int $pwnCount;
    private ?int $pwnCountExcludingSpamLists;
    private ?int $pwnCountExcludingSpamListsAtLastSubscriptionRenewal;
    private ?\DateTime $nextSubscriptionRenewal = null;

    public function __construct(?object $domain = null)
    {
        $this->domainName = $domain->DomainName ?? '';
        $this->pwnCount = $domain->PwnCount ?? null;
        $this->pwnCountExcludingSpamLists = $domain->PwnCountExcludingSpamLists ?? null;
        $this->pwnCountExcludingSpamListsAtLastSubscriptionRenewal =
            $domain->PwnCountExcludingSpamListsAtLastSubscriptionRenewal ?? null;
        if ($renewal = $domain->NextSubscriptionRenewal ?? null) {
            try {
                $this->nextSubscriptionRenewal = new \DateTime($renewal);
            } catch (\Exception $exception) {
                $this->nextSubscriptionRenewal = null;
            }
        }
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    /**
     * The total number of breached email addresses found on the domain at last search,
     * or null if no searches have been performed yet
     */
    public function getPwnCount(): ?int
    {
        return $this->pwnCount;
    }

    /**
     * The number of breached email addresses found on the domain at last search, excluding spam lists,
     * or null if no searches have been performed yet
     */
    public function getPwnCountExcludingSpamLists(): ?int
    {
        return $this->pwnCountExcludingSpamLists;
    }

    /**
     * The number of breached email addresses, excluding spam lists, found on the domain when the current
     * subscription was taken out, or null if no searches have been performed yet
     */
    public function getPwnCountExcludingSpamListsAtLastSubscriptionRenewal(): ?int
    {
        return $this->pwnCountExcludingSpamListsAtLastSubscriptionRenewal;
    }

    /**
     * When the current subscription ends, or null if there have been no subscriptions
     */
    public function getNextSubscriptionRenewal(): ?\DateTime
    {
        return $this->nextSubscriptionRenewal;
    }
}
