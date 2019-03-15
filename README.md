# Pwned [![CircleCI](https://circleci.com/gh/MFlor/pwned.svg?style=svg)](https://circleci.com/gh/MFlor/pwned)
#### A PHP library for interacting with [HaveIBeenPwned.com's](https://haveibeenpwned.com/API/v2) API

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
