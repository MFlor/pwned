<?php

namespace MFlor\Pwned;

use GuzzleHttp\Client;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Repositories\PasteRepository;

class Pwned
{
    private $breaches;
    private $passwords;
    private $pastes;

    public function __construct(string $apiKey = null)
    {
        $breachClient = new Client([
            'base_uri' => 'https://haveibeenpwned.com/api/v3/',
            'headers' => [
                'User-Agent' => 'mflor-pwned-php-library/2.0',
                'Accept' => 'application/json'
            ]
        ]);
        $this->breaches = new BreachRepository($breachClient, $apiKey);
        $this->pastes = new PasteRepository($breachClient, $apiKey);
        $passwordClient = new Client([
            'base_uri' => 'https://api.pwnedpasswords.com/',
            'headers' => [
                'User-Agent' => 'mflor-pwned-php-library/2.0',
                'Accept' => 'text/plain',
            ]
        ]);
        $this->passwords = new PasswordRepository($passwordClient);
    }

    public function breaches()
    {
        return $this->breaches;
    }

    public function pastes()
    {
        return $this->pastes;
    }

    public function passwords()
    {
        return $this->passwords;
    }
}
