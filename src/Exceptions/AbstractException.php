<?php

namespace MFlor\Pwned\Exceptions;

abstract class AbstractException extends \Exception implements ExceptionInterface
{
    private $reasonPhrase;
    private $statusCode;

    public function __construct(string $reasonPhrase, int $statusCode)
    {
        $this->reasonPhrase = $reasonPhrase;
        $this->statusCode = $statusCode;
        parent::__construct(sprintf('%s: %s', $this->statusCode, $this->reasonPhrase));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }
}
