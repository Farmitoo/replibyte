<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\Tag;

class TagFixtures extends Fixture
{
    public const TOTAL_ENTITIES = 100;

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
            $tag = new Tag();
            $tag->name = $this->faker->text(50);

            $this->setReference(sprintf("Tag.%s", $i), $tag);

            $this->manager->persist($tag);
        }
        $this->manager->flush();
    }
}
