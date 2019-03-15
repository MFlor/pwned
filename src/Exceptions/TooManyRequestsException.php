<?php

namespace MFlor\Pwned\Exceptions;

class TooManyRequestsException extends AbstractException
{
    const STATUS_CODE = 429;
}
