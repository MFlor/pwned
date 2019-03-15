<?php

namespace MFlor\Pwned\Exceptions;

class ForbiddenException extends AbstractException
{
    const STATUS_CODE = 403;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
