<?php

namespace MFlor\Pwned\Tests\Models;

use MFlor\Pwned\Models\Password;
use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    public function testCanGetEmptyPassword()
    {
        $password = new Password();
        $this->assertInstanceOf(Password::class, $password);
        $this->assertNull($password->getHash());
        $this->assertSame(0, $password->getOccurences());
    }

    public function testCanGetPasswordFromString()
    {
        $string = 'abc:10';
        $password = new Password();
        $password->fromString($string);

        $this->assertSame('abc', $password->getHash());
        $this->assertSame(10, $password->getOccurences());
    }

    public function testCanGetPasswordFromStringAndPrefix()
    {
        $prefix = 'abc';
        $string = 'def:10';

        $password = new Password();
        $password->fromString($string, $prefix);

        $this->assertSame('abcdef', $password->getHash());
        $this->assertSame(10, $password->getOccurences());
    }

    public function testCanSetDataOnPassword()
    {
        $password = new Password();
        $password->setHash('AbCDEFG');
        $password->setOccurences(100);

        $this->assertSame('abcdefg', $password->getHash());
        $this->assertSame(100, $password->getOccurences());
    }
}
