<?php

namespace MFlor\Pwned\Tests\Models;

use MFlor\Pwned\Models\DataClass;
use PHPUnit\Framework\TestCase;

class DataClassTest extends TestCase
{
    public function testCanConstructEmpty(): void
    {
        $dataClass = new DataClass();

        $this->assertSame('', $dataClass->getName());
        $this->assertSame('', $dataClass->getDescription());
    }

    /**
     * The API returns lowercase keys, while the documentation shows capitalised keys,
     * so both are supported.
     *
     * @dataProvider dataProvider
     */
    public function testCanConstructWithData(\stdClass $data): void
    {
        $dataClass = new DataClass($data);

        $this->assertSame('Passwords', $dataClass->getName());
        $this->assertSame('Passwords used to authenticate.', $dataClass->getDescription());
    }

    /**
     * @return array<string, \stdClass[]>
     */
    public function dataProvider(): array
    {
        return [
            'lowercase keys' => [(object) ['name' => 'Passwords', 'description' => 'Passwords used to authenticate.']],
            'capitalised keys' => [(object) ['Name' => 'Passwords', 'Description' => 'Passwords used to authenticate.']],
        ];
    }
}
