<?php

namespace MFlor\Pwned\Tests\Factories;

use Faker\Factory;

class BreachFactory
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getBreach(): \stdClass
    {
        $breach = new \stdClass();
        $breach->Name = $this->faker->company;
        $breach->Title = $this->faker->sentence(3);
        $breach->Domain = parse_url($this->faker->url, PHP_URL_HOST);
        $breach->BreachDate = $this->faker->date('Y-m-d');
        $breach->AddedDate = $this->faker->dateTime->format('Y-m-d\TH:i:s\Z');
        $breach->ModifiedDate = $this->faker->dateTime->format('Y-m-d\TH:i:s\Z');
        $breach->PwnCount = $this->faker->numberBetween(100000000, 999999999);
        $breach->Description = $this->faker->text;
        $breach->LogoPath = $this->faker->imageUrl();
        $breach->DataClasses = $this->getDataClasses();
        $breach->IsVerified = $this->faker->boolean;
        $breach->IsFabricated = $this->faker->boolean;
        $breach->IsSensitive = $this->faker->boolean;
        $breach->IsRetired = $this->faker->boolean;
        $breach->IsSpamList = $this->faker->boolean;

        return $breach;
    }

    public function getDataClasses()
    {
        $max = $this->faker->numberBetween(3, 10);
        $dataClasses = [];
        for ($i = 0; $i < $max; $i++) {
            $dataClasses[] = $this->faker->words(2, true);
        }

        return $dataClasses;
    }

    public function getBreaches()
    {
        $breaches = [];
        $max = $this->faker->numberBetween(3, 10);
        for ($i = 0; $i < $max; $i++) {
            $breaches[] = $this->getBreach();
        }
        return $breaches;
    }

    public function getNames()
    {
        $names = [];
        $max = $this->faker->numberBetween(3, 10);
        for ($i = 0; $i < $max; $i++) {
            $names[] = $this->faker->company;
        }
        return $names;
    }
}
