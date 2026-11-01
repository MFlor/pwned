<?php

namespace MFlor\Pwned;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Repositories\PasteRepository;

class Pwned
{
    private const DEFAULT_USER_AGENT = 'mflor-pwned-php-library/2.0';

    /**
     * Applied unless overridden in $options. Guzzle's own defaults are to wait indefinitely.
     */
    private const DEFAULT_OPTIONS = [
        RequestOptions::CONNECT_TIMEOUT => 10,
        RequestOptions::TIMEOUT => 30,
    ];

    private BreachRepository $breaches;
    private PasswordRepository $passwords;
    private PasteRepository $pastes;

    /**
     * @param string|null $apiKey HIBP API key, required for searching by email address and domain
     * @param array<string, mixed> $options Guzzle request options used for every request, for example:
     *      [
     *          'timeout' => 10,
     *          'proxy' => 'http://proxy.example.com:8080',
     *          'headers' => ['User-Agent' => 'my-app'],
     *          'handler' => $handlerStack, // e.g. to add retry middleware
     *      ]
     *      The base_uri option is ignored, since the library talks to two different APIs.
     *      @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $breachClient = $this->createClient('https://haveibeenpwned.com/api/v3/', 'application/json', $options);
        $this->breaches = new BreachRepository($breachClient, $apiKey);
        $this->pastes = new PasteRepository($breachClient, $apiKey);

        $passwordClient = $this->createClient('https://api.pwnedpasswords.com/', 'text/plain', $options);
        $this->passwords = new PasswordRepository($passwordClient);
    }

    public function breaches(): BreachRepository
    {
        return $this->breaches;
    }

    public function pastes(): PasteRepository
    {
        return $this->pastes;
    }

    public function passwords(): PasswordRepository
    {
        return $this->passwords;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createClient(string $baseUri, string $accept, array $options): Client
    {
        $headers = [
            'User-Agent' => self::DEFAULT_USER_AGENT,
            'Accept' => $accept,
        ];
        if (isset($options[RequestOptions::HEADERS]) && is_array($options[RequestOptions::HEADERS])) {
            foreach ($options[RequestOptions::HEADERS] as $name => $value) {
                // Header names are case-insensitive, so a "user-agent" option replaces the default "User-Agent"
                foreach (array_keys($headers) as $default) {
                    if (strcasecmp($default, (string) $name) === 0) {
                        unset($headers[$default]);
                    }
                }
                $headers[$name] = $value;
            }
        }

        return new Client(array_merge(self::DEFAULT_OPTIONS, $options, [
            'base_uri' => $baseUri,
            RequestOptions::HEADERS => $headers,
        ]));
    }
}
