<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\Content;

class ContentFixtures extends Fixture implements DependentFixtureInterface
{
    public const TOTAL_ENTITIES_BY_TYPE = 100;

    protected Generator $faker;
    protected ObjectManager $manager;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getDependencies(): array
    {
        return [
          PostFixtures::class,
          TagFixtures::class,
          CategoryFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $count = 0;

        $i = 0;
        while ($i < self::TOTAL_ENTITIES_BY_TYPE) {
            $this->createForType($count, "Tag");
            ++$count;
            ++$i;
        }

        $this->manager->flush();
    }

    protected function createForType(int $idContent, string $type): void
    {
        $id = rand(0, TagFixtures::TOTAL_ENTITIES - 1);
        $tag = $this->getReference(sprintf("%s.%s", $type, $id));
        $content = new Content();
        $content->text = $this->faker->text(500);
        $content->reference = (string) $tag->id;

        $this->setReference(sprintf("Content.%s", $idContent), $content);

        $this->manager->persist($content);
    }
}
