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

    public function __construct(\stdClass $breach = null)
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
}
