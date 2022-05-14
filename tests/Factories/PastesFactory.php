<?php

namespace MFlor\Pwned\Tests\Factories;

use Faker\Factory;
use Faker\Generator;

class PastesFactory
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getPaste(): \stdClass
    {
        $paste = new \stdClass();
        $paste->Id = $this->faker->url;
        $paste->Source = implode('', $this->faker->words);
        $paste->Title = $this->faker->sentence(3);
        $paste->Date = $this->faker->dateTime->format('Y-m-d\TH:i:s\Z');
        $paste->EmailCount = $this->faker->numberBetween(10000, 99999);

        return $paste;
    }

    /**
     * @return \stdClass[]
     */
    public function getPastes(): array
    {
        return array_map(
            fn () => $this->getPaste(),
            range(1, $this->faker->numberBetween(3, 10))
        );
    }
}
