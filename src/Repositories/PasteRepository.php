<?php

namespace MFlor\Pwned\Repositories;

use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Models\Paste;
use stdClass;

class PasteRepository extends AbstractServiceRepository
{
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
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function byAccount(string $account)
    {
        $response = $this->getAuthenticatedResponse(sprintf('pasteaccount/%s', urlencode($account)));

        $data = json_decode($response->getBody()->getContents());
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->mapPastes($data);
        }

        return null;
    }

    private function mapPastes(array $pastes)
    {
        return array_map(function (stdClass $paste) {
            return new Paste($paste);
        }, $pastes);
    }
}
