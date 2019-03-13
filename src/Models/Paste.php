<?php

namespace MFlor\Pwned\Models;

class Paste
{
    /** @var string */
    private $source;
    /** @var string */
    private $pasteID;
    /** @var string */
    private $title;
    /** @var \DateTime|null */
    private $date;
    /** @var int */
    private $emailCount;

    public function __construct(\stdClass $paste = null)
    {
        $this->source = $paste->Source ?? '';
        $this->pasteID = $paste->Id ?? '';
        $this->title = $paste->Title ?? '';
        if ($date = $paste->Date ?? null) {
            try {
                $this->date = new \DateTime($date);
            } catch (\Exception $exception) {
                $this->date = null;
            }
        }
        $this->emailCount = $paste->EmailCount ?? 0;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return string
     */
    public function getID(): string
    {
        return $this->pasteID;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return \DateTime|null
     */
    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    /**
     * @return int
     */
    public function getEmailCount(): int
    {
        return $this->emailCount;
    }
}
