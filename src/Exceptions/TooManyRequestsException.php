<?php

namespace MFlor\Pwned\Exceptions;

class TooManyRequestsException extends AbstractException
{
    public const STATUS_CODE = 429;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
