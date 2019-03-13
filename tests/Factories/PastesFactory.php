<?php

namespace MFlor\Pwned\Tests\Factories;

use Faker\Factory;

class PastesFactory
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getPaste()
    {
        $paste = new \stdClass();
        $paste->Id = $this->faker->url;
        $paste->Source = implode('', $this->faker->words);
        $paste->Title = $this->faker->sentence(3);
        $paste->Date = $this->faker->dateTime->format('Y-m-d\TH:i:s\Z');
        $paste->EmailCount = $this->faker->numberBetween(10000, 99999);

        return $paste;
    }

    public function getPastes()
    {
        $pastes = [];
        $max = $this->faker->numberBetween(3, 10);
        for ($i = 0; $i < $max; $i++) {
            $pastes[] = $this->getPaste();
        }
        return $pastes;
    }
}
