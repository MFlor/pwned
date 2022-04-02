<?php

namespace MFlor\Pwned\Tests;

use MFlor\Pwned\Pwned;
use MFlor\Pwned\Repositories\BreachRepository;
use MFlor\Pwned\Repositories\PasswordRepository;
use MFlor\Pwned\Repositories\PasteRepository;
use PHPUnit\Framework\TestCase;

class PwnedTest extends TestCase
{
    public function testCanInstantiateClass(): void
    {
        $pwned = new Pwned();
        $this->assertInstanceOf(Pwned::class, $pwned);
        $this->assertInstanceOf(BreachRepository::class, $pwned->breaches());
        $this->assertInstanceOf(PasteRepository::class, $pwned->pastes());
        $this->assertInstanceOf(PasswordRepository::class, $pwned->passwords());
    }
}
