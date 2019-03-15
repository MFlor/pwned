<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Models\Paste;

class PasteRepository
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get all pastes where an account has occured.
     *
     * @see https://haveibeenpwned.com/API/v2#PastesForAccount
     *
     * @param string $account
     *
     * @return array|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     */
    public function byAccount(string $account)
    {
        try {
            $response = $this->client->get(sprintf('pasteaccount/%s', urlencode($account)));
        } catch (ClientException $exception) {
            $reasonPhrase = $exception->getResponse()->getReasonPhrase();
            switch ($exception->getResponse()->getStatusCode()) {
                case 400:
                    throw new BadRequestException($reasonPhrase);
                case 403:
                    throw new ForbiddenException($reasonPhrase);
                case 404:
                    return null;
                case 429:
                    throw new TooManyRequestsException($reasonPhrase);
                default:
                    throw $exception;
            }
        }

        $data = json_decode($response->getBody()->getContents());
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->mapPastes($data);
        }

        return null;
    }

    private function mapPastes(array $pastes)
    {
        return array_map(function (\stdClass $paste) {
            return new Paste($paste);
        }, $pastes);
    }
}
