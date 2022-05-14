<?php

namespace MFlor\Pwned\Models;

class Paste
{
    private string $source;
    private string $pasteID;
    private string $title;
    private ?\DateTime $date = null;
    private int $emailCount;

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

    public function getSource(): string
    {
        return $this->source;
    }

    public function getID(): string
    {
        return $this->pasteID;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function getEmailCount(): int
    {
        return $this->emailCount;
    }
}
