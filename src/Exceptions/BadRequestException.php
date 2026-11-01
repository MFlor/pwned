<?php

namespace MFlor\Pwned\Exceptions;

class BadRequestException extends AbstractException
{
    public const STATUS_CODE = 400;

    public function __construct(string $reasonPhrase, ?string $apiMessage = null)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE, $apiMessage);
    }
}
