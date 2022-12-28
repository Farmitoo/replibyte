<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\GeneratedData;

class GeneratedDataFixtures extends Fixture
{
    public const TOTAL_ENTITIES = 1000;

    protected Generator $faker;
    protected ObjectManager $manager;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        for ($i = 0; $i < self::TOTAL_ENTITIES; ++$i) {
            $generatedData = new GeneratedData();
            $generatedData->something = $this->faker->text(50);

            $this->manager->persist($generatedData);
        }
        $this->manager->flush();
    }
}
