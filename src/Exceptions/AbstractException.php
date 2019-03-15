<?php

namespace MFlor\Pwned\Exceptions;

abstract class AbstractException extends \Exception implements ExceptionInterface
{
    private $reasonPhrase;

    public function __construct(string $reasonPhrase)
    {
        $this->reasonPhrase = $reasonPhrase;
        parent::__construct(sprintf('%s: %s', static::STATUS_CODE, $this->reasonPhrase));
    }

    public function getStatusCode(): int
    {
        return static::STATUS_CODE;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
