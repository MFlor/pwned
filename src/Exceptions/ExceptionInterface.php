<?php

namespace MFlor\Pwned\Exceptions;

interface ExceptionInterface
{
    public function getStatusCode(): int;
    public function getReasonPhrase(): string;
}
