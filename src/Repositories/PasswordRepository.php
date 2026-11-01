<?php

namespace MFlor\Pwned\Repositories;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use MFlor\Pwned\Exceptions\BadRequestException;
use MFlor\Pwned\Exceptions\ForbiddenException;
use MFlor\Pwned\Exceptions\NotFoundException;
use MFlor\Pwned\Exceptions\ServiceUnavailableException;
use MFlor\Pwned\Exceptions\TooManyRequestsException;
use MFlor\Pwned\Models\Password;

/**
 * The Pwned Passwords API is free and doesn't need an API key.
 */
class PasswordRepository extends AbstractServiceRepository
{
    /**
     * Search for passwords in the HaveIBeenPwned database,
     * by the passwords first five characters, when hashed with SHA-1
     * Returns an array of Password models
     *
     * When padding is enabled, the API adds fake entries with an occurrence count of 0,
     * which are discarded, so only real results are returned.
     * @see Password
     * @see https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange
     *
     * @param string $prefix
     * @param bool $pad
     * @return Password[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function search(string $prefix, bool $pad = true): ?array
    {
        return $this->searchRange($prefix, $pad, false);
    }

    /**
     * Search for passwords in the HaveIBeenPwned database,
     * by the first five characters of their NTLM hash.
     * Returns an array of Password models, where the hash is the full NTLM hash.
     *
     * Padded entries are discarded, as with search().
     * @see Password
     * @see https://haveibeenpwned.com/API/v3#PwnedPasswordsNTLM
     *
     * @param string $prefix
     * @param bool $pad
     * @return Password[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function searchNtlm(string $prefix, bool $pad = true): ?array
    {
        return $this->searchRange($prefix, $pad, true);
    }

    /**
     * Returns the occurrence count for the given password.
     * The count is how many times it appears in leaks, known by HaveIBeenPwned.
     *
     * @param string $password
     * @param bool $pad
     * @return int
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function occurrences(string $password, bool $pad = true): int
    {
        return $this->findOccurrences(hash('sha1', $password), $pad, false);
    }

    /**
     * Returns the occurrence count for a password, given its NTLM hash.
     * This is useful when auditing systems that store NTLM hashes, such as Active Directory.
     *
     * @see https://haveibeenpwned.com/API/v3#PwnedPasswordsNTLM
     *
     * @param string $hash The full 32-character NTLM hash
     * @param bool $pad
     * @return int
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    public function occurrencesByNtlmHash(string $hash, bool $pad = true): int
    {
        return $this->findOccurrences($hash, $pad, true);
    }

    /**
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    private function findOccurrences(string $hash, bool $pad, bool $ntlm): int
    {
        $hash = strtolower($hash);
        if ($passwords = $this->searchRange(substr($hash, 0, 5), $pad, $ntlm)) {
            foreach ($passwords as $password) {
                if ($password->getHash() === $hash) {
                    return $password->getOccurrences();
                }
            }
        }

        return 0;
    }

    /**
     * @return Password[]|null
     *
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws TooManyRequestsException
     * @throws ServiceUnavailableException
     * @throws GuzzleException
     */
    private function searchRange(string $prefix, bool $pad, bool $ntlm): ?array
    {
        $options = [];
        if ($pad) {
            $options[RequestOptions::HEADERS] = ['Add-Padding' => 'true'];
        }
        if ($ntlm) {
            $options[RequestOptions::QUERY] = ['mode' => 'ntlm'];
        }

        $response = $this->send('GET', sprintf('range/%s', $prefix), $options);

        // The API separates lines with CRLF
        $data = preg_split('/\R/', (string) $response->getBody(), -1, PREG_SPLIT_NO_EMPTY);

        if ($data) {
            $passwords = array_map(function (string $string) use ($prefix) {
                return (new Password())->fromString($string, $prefix);
            }, $data);

            // Padded entries always have an occurrence count of 0 and should be discarded
            return array_values(array_filter($passwords, function (Password $password) {
                return $password->getOccurrences() > 0;
            }));
        }
        return null;
    }
}
