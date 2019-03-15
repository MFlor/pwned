# Pwned [![CircleCI](https://circleci.com/gh/MFlor/pwned.svg?style=svg)](https://circleci.com/gh/MFlor/pwned) [![Coverage Status](https://coveralls.io/repos/github/MFlor/pwned/badge.svg?branch=test%2Fcoveralls)](https://coveralls.io/github/MFlor/pwned?branch=test%2Fcoveralls)
#### A clean and simple PHP library for interacting with all [HaveIBeenPwned.com's](https://haveibeenpwned.com/API/v2) API endpoints

This package wraps the entire HaveIBeenPwned API in a simple, easy to use, PHP library, that can be used in any project.

Many other packages wrapping the API, are either supposed to be used in a framework, or only wraps the password checker.

## Installation
Install the library with composer:
```bash
composer require mflor/pwned
```

### Setup
```php
<?php

// Require composers autoloader
require_once './vendor/autoload.php';

// Initiate a new instance of the Pwned class
$pwned = new \MFlor\Pwned\Pwned();
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

// Get breaches by account
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
// Get all pastes by account
$pwned->pastes()->byAccount('test@example.com')
// Returns an array of Paste Models (see MFlor/Pwned/Models/Paste.php)
```

### Passwords
```php
// Search for passwords (By the first five characters of a sha1 hash)
$pwned->passwords()->search('e38ad');
// Returns an array of Password Models (see MFlor/Pwned/Models/Password.php)

$pwned->passwords()->occurences('password1');
// Returns the number occurences of the given password has been found in leaks
```

### Exceptions
This package will throw custom exceptions if a Client Error occures.

`\Mflor\Pwned\Exceptions\BadRequestException` is thrown on status code 400

`\Mflor\Pwned\Exceptions\ForbiddenException` is thrown on status code 403

`\Mflor\Pwned\Exceptions\NotFoundException` is thrown on status code 404

`\Mflor\Pwned\Exceptions\TooManyRequestsException` is thrown on status code 429
 

| Code |                   Description                                                                   |
|------|-------------------------------------------------------------------------------------------------|
| 400  | Bad request — the account does not comply with an acceptable format (i.e. it's an empty string) |
| 403  | Forbidden — no user agent has been specified in the request                                     |
| 404  | Not found — the account could not be found and has therefore not been pwned                     |
| 429  | Too many requests — the rate limit has been exceeded                                            |

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
