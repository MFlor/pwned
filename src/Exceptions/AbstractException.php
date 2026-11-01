<?php

namespace MFlor\Pwned\Exceptions;

abstract class AbstractException extends \RuntimeException implements ExceptionInterface
{
    private string $reasonPhrase;
    private int $statusCode;
    private ?string $apiMessage;

    public function __construct(string $reasonPhrase, int $statusCode, ?string $apiMessage = null)
    {
        $this->reasonPhrase = $reasonPhrase;
        $this->statusCode = $statusCode;
        $this->apiMessage = $apiMessage;

        $message = sprintf('%s: %s', $this->statusCode, $this->reasonPhrase);
        if ($apiMessage !== null && $apiMessage !== '') {
            $message .= sprintf(' (%s)', $apiMessage);
        }
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * The explanation the API included in the response body, if any.
     * For example: "Access denied due to invalid hibp-api-key."
     */
    public function getApiMessage(): ?string
    {
        return $this->apiMessage;
    }
}
