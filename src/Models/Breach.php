<?php

namespace MFlor\Pwned\Models;

class Breach
{
    private string $name;
    private string $title;
    private string $domain;
    private ?\DateTime $breached = null;
    private ?\DateTime $added = null;
    private ?\DateTime $modified = null;
    private int $pwnCount;
    private string $description;
    private string $logo;
    /** @var string[] */
    private array $classes;
    private bool $verified;
    private bool $fabricated;
    private bool $sensitive;
    private bool $retired;
    private bool $spamList;
    private bool $malware;
    private bool $subscriptionFree;
    private bool $stealerLog;
    private ?string $attribution;
    private ?string $disclosureUrl;

    public function __construct(?object $breach = null)
    {
        $this->name = $breach->Name ?? '';
        $this->title = $breach->Title ?? '';
        $this->domain = $breach->Domain ?? '';
        if ($breached = $breach->BreachDate ?? '') {
            try {
                $this->breached = new \DateTime($breached);
            } catch (\Exception $exception) {
                $this->breached = null;
            }
        }
        if ($added = $breach->AddedDate ?? null) {
            try {
                $this->added = new \DateTime($added);
            } catch (\Exception $exception) {
                $this->added = null;
            }
        }
        if ($modified = $breach->ModifiedDate ?? null) {
            try {
                $this->modified = new \DateTime($modified);
            } catch (\Exception $exception) {
                $this->modified = null;
            }
        }
        $this->pwnCount = $breach->PwnCount ?? 0;
        $this->description = $breach->Description ?? '';
        $this->logo = $breach->LogoPath ?? '';
        $this->classes = $breach->DataClasses ?? [];
        $this->verified = $breach->IsVerified ?? false;
        $this->fabricated = $breach->IsFabricated ?? false;
        $this->sensitive = $breach->IsSensitive ?? false;
        $this->retired = $breach->IsRetired ?? false;
        $this->spamList = $breach->IsSpamList ?? false;
        $this->malware = $breach->IsMalware ?? false;
        $this->subscriptionFree = $breach->IsSubscriptionFree ?? false;
        $this->stealerLog = $breach->IsStealerLog ?? false;
        $this->attribution = $breach->Attribution ?? null;
        $this->disclosureUrl = $breach->DisclosureUrl ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getBreached(): ?\DateTime
    {
        return $this->breached;
    }

    public function getAdded(): ?\DateTime
    {
        return $this->added;
    }

    public function getModified(): ?\DateTime
    {
        return $this->modified;
    }

    public function getPwnCount(): int
    {
        return $this->pwnCount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @return string[]
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function isFabricated(): bool
    {
        return $this->fabricated;
    }

    public function isSensitive(): bool
    {
        return $this->sensitive;
    }

    public function isRetired(): bool
    {
        return $this->retired;
    }

    public function isSpamList(): bool
    {
        return $this->spamList;
    }

    /**
     * Whether the breach was sourced from malware rather than a compromise of an online service
     */
    public function isMalware(): bool
    {
        return $this->malware;
    }

    /**
     * Whether the breach is subscription-free (only relevant for domain searches)
     */
    public function isSubscriptionFree(): bool
    {
        return $this->subscriptionFree;
    }

    /**
     * Whether the breach was sourced from stealer logs
     */
    public function isStealerLog(): bool
    {
        return $this->stealerLog;
    }

    /**
     * Attribution sometimes requested by the party that provided the data, or null
     */
    public function getAttribution(): ?string
    {
        return $this->attribution;
    }

    /**
     * A URL to the breached organisation's own public disclosure of the breach, or null
     */
    public function getDisclosureUrl(): ?string
    {
        return $this->disclosureUrl;
    }
}
