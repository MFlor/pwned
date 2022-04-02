<?php

namespace MFlor\Pwned\Models;

class Password
{
    private ?string $hash;
    private int $occurrences;

    public function __construct()
    {
        $this->hash = null;
        $this->occurrences = 0;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): Password
    {
        $this->hash = strtolower($hash);
        return $this;
    }

    public function getOccurrences(): int
    {
        return $this->occurrences;
    }

    public function setOccurrences(int $occurrences): Password
    {
        $this->occurrences = $occurrences;
        return $this;
    }

    /**
     * Format: `00969D6155DDF8FDEA98D8E0F85ADE21568:2`
     */
    public function fromString(string $string, string $prefix = ''): Password
    {
        $parts = explode(':', $prefix . $string);

        if (count($parts) === 2) {
            $this->setHash($parts[0]);
            $this->setoccurrences(intval($parts[1]));
        }

        return $this;
    }
}
