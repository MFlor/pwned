<?php

namespace MFlor\Pwned\Exceptions;

class ServiceUnavailableException extends AbstractException
{
    const STATUS_CODE = 503;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
