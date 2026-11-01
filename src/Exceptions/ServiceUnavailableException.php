<?php

namespace MFlor\Pwned\Exceptions;

class ServiceUnavailableException extends AbstractException
{
    public const STATUS_CODE = 503;

    public function __construct(string $reasonPhrase, ?string $apiMessage = null)
    {
        parent::__construct($reasonPhrase, self::STATUS_CODE, $apiMessage);
    }
}
