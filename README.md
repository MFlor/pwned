# Pwned
[![Build Status](https://github.com/MFlor/pwned/actions/workflows/tests.yml/badge.svg)](https://github.com/MFlor/pwned/actions/workflows/tests.yml)
[![Coverage Status](https://codecov.io/gh/MFlor/pwned/branch/main/graph/badge.svg?token=L0Y15FOKTP)](https://codecov.io/gh/MFlor/pwned)
[![Total Downloads](https://img.shields.io/packagist/dt/mflor/pwned)](https://packagist.org/packages/mflor/pwned)
[![Latest Version](https://img.shields.io/packagist/v/mflor/pwned)](https://packagist.org/packages/mflor/pwned)
[![License](https://img.shields.io/packagist/l/mflor/pwned)](https://packagist.org/packages/mflor/pwned)
#### A clean and simple PHP library for interacting with all [HaveIBeenPwned.com's](https://haveibeenpwned.com/API/v3) API endpoints

This package wraps the entire HaveIBeenPwned API in a simple, easy to use, PHP library, that can be used in any project.

Many other packages wrapping the API, are either supposed to be used in a framework, or only wraps the password checker.

## Installation
Install the library with composer:
```bash
composer require mflor/pwned
```

The library requires PHP 8.0 or newer, and works with both Guzzle 7 and Guzzle 8.

### Authorisation

Authorisation is required for all APIs that search HIBP by email address or domain:
breaches and pastes by account, stealer logs, domain search and subscription status.
An HIBP subscription key is required to make an authorised call and can be obtained on [the API key page](https://haveibeenpwned.com/API/Key).
Some APIs, like stealer logs and k-anonymity email searches, require a higher subscription level - see [HIBP's pricing](https://haveibeenpwned.com/Subscription).

The breach catalogue, data classes and Pwned Passwords APIs are free and don't need a key.

### Setup
```php
<?php

// Require composers autoloader
require_once './vendor/autoload.php';

// Initiate a new instance of the Pwned class
// It can be instantiated without an API key
// but then account-specific braches and pastes
// will result in unauthorized exceptions.
$pwned = new \MFlor\Pwned\Pwned($apiKey = null);
```

#### Configuring the HTTP client
Any [Guzzle request options](https://docs.guzzlephp.org/en/stable/request-options.html) can be passed as the second argument,
and are used for every request:

```php
$pwned = new \MFlor\Pwned\Pwned($apiKey, [
    // HIBP asks API consumers to identify themselves in the user agent
    'headers' => ['User-Agent' => 'my-app'],
    // By default, requests time out after 30 seconds (10 seconds to connect)
    'timeout' => 10,
    'proxy' => 'http://proxy.example.com:8080',
]);
```

## Usage
### Breaches

```php
// Get all breaches
$pwned->breaches()->getAll();
// Returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// Get only the breaches that are (true) or aren't (false) flagged as spam lists
$pwned->breaches()->getAll(false);

// Get all breaches by a specific domain (optionally filtered by spam lists, like getAll)
$pwned->breaches()->byDomain('adobe.com');
// Returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// Get a breach by its name
$pwned->breaches()->byName('Adobe');
// Returns a Breach Model (see MFlor/Pwned/Models/Breach.php)

// Get the most recently added breach
$pwned->breaches()->latest();
// Returns a Breach Model - useful for checking if anything new has been loaded before searching

// Get breaches by account (Requires API key)
$pwned->breaches()->byAccount('test@example.com');
// By default the response is truncated to just the breach names,
// and returns an array of objects with a single Name property:
// [{"Name": "Adobe"}, {"Name": "Gawker"}]

// Options for breaches by account:
$options = [
    'truncateResponse' => false, // true returns only breach names, false returns full Breach models (Default: true)
    'domain' => 'adobe.com', // Filter results by a domain (Default: null)
    'includeUnverified' => false, // Include unverified breaches (Default: true)
];
$pwned->breaches()->byAccount('test@example.com', $options);
// With 'truncateResponse' => false, returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// If the account isn't found in any breaches, a NotFoundException is thrown (see Exceptions below)

// Get breaches by account using k-anonymity (Requires API key with k-anonymity search),
// so only the first 6 characters of the address's SHA-1 hash are sent to the API
$pwned->breaches()->byAccountAnonymously('test@example.com');
// Returns an array of breach names, e.g. ["Adobe", "Gawker"], or an empty array if not found

// Get all data classes
$pwned->breaches()->getDataClasses();
// ["Account balances","Address book contacts","Age groups","Ages"...]

// Get all data classes with descriptions
$pwned->breaches()->getDataClassDescriptions();
// Returns an array of DataClass Models (see MFlor/Pwned/Models/DataClass.php)
```

### Pastes
```php
// Get all pastes by account (Requires API key)
$pwned->pastes()->byAccount('test@example.com');
// Returns an array of Paste Models (see MFlor/Pwned/Models/Paste.php)
```

### Passwords
```php
// Search for passwords (By the first five characters of a sha1 hash)
$pwned->passwords()->search('e38ad');
// Returns an array of Password Models (see MFlor/Pwned/Models/Password.php)

$pwned->passwords()->occurrences('password1');
// Returns the number occurrences of the given password has been found in leaks

// The same, using NTLM hashes (e.g. for auditing Active Directory)
$pwned->passwords()->searchNtlm('8846f');
$pwned->passwords()->occurrencesByNtlmHash('8846F7EAEE8FB117AD06BDD830B7586C');
```

All search and occurrences methods take a second boolean parameter, to disable padding for request.
Be aware, that this is less secure, than having the padding enabled, which is default.
The padded entries added by the API are discarded, so they are never included in the results.
Read more about the padding in [Troy Hunt's blog post](https://www.troyhunt.com/enhancing-pwned-passwords-privacy-with-padding/)

### Stealer logs
Stealer logs are credentials captured by info-stealer malware. Searching them requires an API key with stealer logs included,
and can only be done for domains you have verified (see Domain search below).

```php
// Get the website domains an email address has been captured against
$pwned->stealerLogs()->byEmail('jane@example.com');
// ["netflix.com", "spotify.com"]

// Get the email addresses captured when signing in to a website domain
$pwned->stealerLogs()->byWebsiteDomain('example.com');
// ["andy@gmail.com", "jane@gmail.com"]

// Get the aliases on an email domain, and the website domains each was captured against
$pwned->stealerLogs()->byEmailDomain('example.com');
// ["andy" => ["netflix.com"], "jane" => ["netflix.com", "spotify.com"]]
```

### Domain search
Domain search returns all breached email addresses on a domain you have verified control of (Requires API key).

```php
// Get the breached aliases on a domain, and the breaches each appears in
$pwned->domains()->search('example.com');
// ["alias1" => ["Adobe"], "alias2" => ["Adobe", "Gawker", "Stratfor"]]

// Get all your verified domains
$pwned->domains()->subscribed();
// Returns an array of SubscribedDomain Models (see MFlor/Pwned/Models/SubscribedDomain.php)

// Verify a domain via DNS:
// 1. Generate a token, and add it as a TXT record to the domain
$txtRecordValue = $pwned->domains()->generateDnsToken('example.com');
// 2. Once the DNS record has been set, verify it
$pwned->domains()->verifyDnsToken('example.com');

// Or verify a domain via email, sent to admin, hostmaster, info, security or webmaster at the domain
$pwned->domains()->sendVerificationEmail('example.com', 'security');
```

If a verification fails, a `BadRequestException` is thrown, and `getApiMessage()` explains why.

### Subscription
```php
// Get the status of the subscription the API key belongs to
$pwned->subscription()->status();
// Returns a Subscription Model (see MFlor/Pwned/Models/Subscription.php)
```

### Exceptions
This package will throw custom exceptions if a Client Error occures.

`\MFlor\Pwned\Exceptions\BadRequestException` is thrown on status code 400

`\MFlor\Pwned\Exceptions\UnauthorizedException` is thrown on status code 401

`\MFlor\Pwned\Exceptions\ForbiddenException` is thrown on status code 403

`\MFlor\Pwned\Exceptions\NotFoundException` is thrown on status code 404

`\MFlor\Pwned\Exceptions\TooManyRequestsException` is thrown on status code 429

`\MFlor\Pwned\Exceptions\ServiceUnavailableException` is thrown on status code 503
 

| Code |                   Description                                                                   |
|------|-------------------------------------------------------------------------------------------------|
| 400  | Bad request — the account does not comply with an acceptable format (i.e. it's an empty string) |
| 401  | Unauthorised — either no API key was provided or it wasn't valid                                |
| 403  | Forbidden — no user agent was specified, or the key doesn't have access (e.g. unverified domain) |
| 404  | Not found — the account could not be found and has therefore not been pwned                     |
| 429  | Too many requests — the rate limit has been exceeded                                            |
| 503  | Service unavailable — usually returned by Cloudflare if the underlying service is not available |

All exceptions have a `getApiMessage()` method, with the explanation HIBP included in the response, if any -
for example "Access denied due to invalid hibp-api-key.".

#### Rate limits
When the rate limit is exceeded, `TooManyRequestsException::getRetryAfter()` returns the number of seconds to wait before retrying.
To retry automatically instead, add Guzzle's retry middleware:

```php
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$handler = HandlerStack::create();
$handler->push(Middleware::retry(
    // Retry up to 3 times when rate limited
    function (int $retries, RequestInterface $request, ?ResponseInterface $response = null): bool {
        return $retries < 3 && $response !== null && $response->getStatusCode() === 429;
    },
    // Wait for as long as the API asks (the delay is in milliseconds)
    function (int $retries, ?ResponseInterface $response = null): int {
        return 1000 * max(1, (int) ($response ? $response->getHeaderLine('retry-after') : 1));
    }
));

$pwned = new \MFlor\Pwned\Pwned($apiKey, ['handler' => $handler]);
```

## Testing
You can run all tests by executing either of the following commands:
```bash
$ composer test
$ ./vendor/bin/phpunit
# If you already have phpunit installed globally
$ phpunit
```

## License
MIT
