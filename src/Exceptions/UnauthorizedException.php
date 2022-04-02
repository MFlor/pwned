<?php

namespace MFlor\Pwned\Exceptions;

class UnauthorizedException extends AbstractException
{
    public const STATUS_CODE = 401;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
