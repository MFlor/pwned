<?php

namespace MFlor\Pwned\Models;

/**
 * A data class (e.g. "Email addresses") with its description
 */
class DataClass
{
    private string $name;
    private string $description;

    public function __construct(?object $dataClass = null)
    {
        // The API returns lowercase keys, although the documentation shows them capitalised
        $this->name = $dataClass->name ?? $dataClass->Name ?? '';
        $this->description = $dataClass->description ?? $dataClass->Description ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
