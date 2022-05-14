<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Exceptions\UnauthorizedException;
use MFlor\Pwned\Models\Paste;

class PasteRepository extends AbstractServiceRepository
{
    /**
     * Get all pastes where an account has occurred.
     *
     * @see https://haveibeenpwned.com/API/v3#PastesForAccount
     *
     * @param string $account
     *
     * @return Paste[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws NotFoundException
     * @throws UnauthorizedException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function byAccount(string $account): ?array
    {
        $response = $this->getAuthenticatedResponse(sprintf('pasteaccount/%s', urlencode($account)));
        try {
            $data = (array) json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
            return $this->mapPastes($data);
        } catch (\JsonException $exception) {
        }

        return null;
    }

    /**
     * @param array<mixed> $pastes
     * @return Paste[]
     */
    private function mapPastes(array $pastes): array
    {
        return array_reduce($pastes, function ($pastes, $data) {
            if ($data instanceof \stdClass) {
                $pastes[] = new Paste($data);
            }
            return $pastes;
        }, []);
    }
}
