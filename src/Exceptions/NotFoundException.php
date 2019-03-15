<?php

namespace MFlor\Pwned\Exceptions;

class NotFoundException extends AbstractException
{
    const STATUS_CODE = 404;

    public function __construct(string $reasonPhrase)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE);
    }
}
