<?php

namespace MFlor\Pwned\Models;

class Breach
{
    /** @var string */
    private $name;
    /** @var string */
    private $title;
    /** @var string */
    private $domain;
    /** @var \DateTime|null */
    private $breached;
    /** @var \DateTime|null */
    private $added;
    /** @var \DateTime|null */
    private $modified;
    /** @var int */
    private $pwnCount;
    /** @var string */
    private $description;
    /** @var string */
    private $logo;
    /** @var array */
    private $classes;
    /** @var bool */
    private $verified;
    /** @var bool */
    private $fabricated;
    /** @var bool */
    private $sensitive;
    /** @var bool */
    private $retired;
    /** @var bool */
    private $spamList;

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

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @return \DateTime|null
     */
    public function getBreached()
    {
        return $this->breached;
    }

    /**
     * @return \DateTime|null
     */
    public function getAdded()
    {
        return $this->added;
    }

    /**
     * @return \DateTime|null
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @return int
     */
    public function getPwnCount()
    {
        return $this->pwnCount;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @return bool
     */
    public function isFabricated()
    {
        return $this->fabricated;
    }

    /**
     * @return bool
     */
    public function isSensitive()
    {
        return $this->sensitive;
    }

    /**
     * @return bool
     */
    public function isRetired()
    {
        return $this->retired;
    }

    /**
     * @return bool
     */
    public function isSpamList()
    {
        return $this->spamList;
    }
}
