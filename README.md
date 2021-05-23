# Pwned
[![Build Status](https://github.com/MFlor/pwned/actions/workflows/tests.yml/badge.svg)](https://codecov.io/gh/MFlor/pwned)
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

### Authorisation

Authorisation is required for all APIs that enable searching HIBP by email address,
namely [retrieving all breaches for an account](https://haveibeenpwned.com/API/v3#BreachesForAccount) and
[retrieving all pastes for an account](https://haveibeenpwned.com/API/v3#PastesForAccount).
An HIBP subscription key is required to make an authorised call and can be obtained on [the API key page](https://haveibeenpwned.com/API/Key). 

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

## Usage
### Breaches

```php
// Get all breaches
$pwned->breaches()->getAll();
// Returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// Get all breaches by a specific domain
$pwned->breaches()->byDomain('adobe.com');
// Returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// Get a breach by its name
$pwned->breaches()->byName('Adobe');
// Returns a Breach Model (see MFlor/Pwned/Models/Breach.php)

// Get breaches by account (Requires API key)
$pwned->breaches()->byAccount('test@example.com');
// Returns an array of Breach Models (see MFlor/Pwned/Models/Breach.php)

// Options for breaches by account:
$options = [
    'truncateResponse' => true // Show full breach or just the name (Default: true)
    'domain' => 'adobe.com' // Filter results by a domain (Default: null)
    'includeUnverified' => true // Include unverified breaches (Default: false)
];
$pwned->breaches()->byAccount('test@example.com', $options);

// Get all data classes
$pwned->breaches()->getDataClasses();
// ["Account balances","Address book contacts","Age groups","Ages"...]
```

### Pastes
```php
// Get all pastes by account (Requires API key)
$pwned->pastes()->byAccount('test@example.com')
// Returns an array of Paste Models (see MFlor/Pwned/Models/Paste.php)
```

### Passwords
```php
// Search for passwords (By the first five characters of a sha1 hash)
$pwned->passwords()->search('e38ad');
// Returns an array of Password Models (see MFlor/Pwned/Models/Password.php)

$pwned->passwords()->occurrences('password1');
// Returns the number occurrences of the given password has been found in leaks
```

Both search and occurrences takes a second boolean parameter, to disable padding for request.
Be aware, that this is less secure, than having the padding enabled, which is default.
Read more about the padding in [Troy Hunt's blog post](https://www.troyhunt.com/enhancing-pwned-passwords-privacy-with-padding/)

### Exceptions
This package will throw custom exceptions if a Client Error occures.

`\Mflor\Pwned\Exceptions\BadRequestException` is thrown on status code 400

`\Mflor\Pwned\Exceptions\UnauthorizedException` is thrown on status code 401

`\Mflor\Pwned\Exceptions\ForbiddenException` is thrown on status code 403

`\Mflor\Pwned\Exceptions\NotFoundException` is thrown on status code 404

`\Mflor\Pwned\Exceptions\TooManyRequestsException` is thrown on status code 429

`\Mflor\Pwned\Exceptions\ServiceUnavailableException` is thrown on status code 503
 

| Code |                   Description                                                                   |
|------|-------------------------------------------------------------------------------------------------|
| 400  | Bad request — the account does not comply with an acceptable format (i.e. it's an empty string) |
| 401  | Unauthorised — either no API key was provided or it wasn't valid                                |
| 403  | Forbidden — no user agent has been specified in the request                                     |
| 404  | Not found — the account could not be found and has therefore not been pwned                     |
| 429  | Too many requests — the rate limit has been exceeded                                            |
| 503  | Service unavailable — usually returned by Cloudflare if the underlying service is not available |

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
