<?php

namespace MFlor\Pwned\Exceptions;

class TooManyRequestsException extends AbstractException
{
    public const STATUS_CODE = 429;

    private ?int $retryAfter;

    public function __construct(string $reasonPhrase, ?int $retryAfter = null, ?string $apiMessage = null)
    {
        $this->retryAfter = $retryAfter;
        parent::__construct($reasonPhrase, self::STATUS_CODE, $apiMessage);
    }

    /**
     * The number of seconds to wait before retrying, taken from the response's retry-after header.
     * Null if the API didn't specify it.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
