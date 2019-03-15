<?php

namespace MFlor\Pwned\Exceptions;

class BadRequestException extends AbstractException
{
    const STATUS_CODE = 400;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
