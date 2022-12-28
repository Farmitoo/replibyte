<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\Category;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\CategoryImage;

class CategoryFixtures extends Fixture
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
            $category = new Category();
            $category->name = $this->faker->text(50);

            for ($j = 0; $j < rand(1, 20); ++$j) {
                $categoryImage = new CategoryImage();
                $categoryImage->path = $this->faker->imageUrl();
                $categoryImage->category = $category;
                $this->setReference(sprintf("CategoryImage.%s", $i), $categoryImage);
                $this->manager->persist($categoryImage);
            }

            $this->setReference(sprintf("Category.%s", $i), $category);

            $this->manager->persist($category);
        }
        $this->manager->flush();
    }
}
