<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\Post;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public const TOTAL_ENTITIES = 10000;

    protected Generator $faker;
    protected ObjectManager $manager;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function getDependencies(): array
    {
        return [TagFixtures::class, AuthorFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $authors = AuthorFixtures::getAuthors();
        $authorLoop = 0;
        for ($i = 0; $i < self::TOTAL_ENTITIES; ++$i) {
            $post = new Post();
            $post->title = $this->faker->text(150);
            $post->uuid = $this->faker->uuid();
            $post->description = 0 === $i % 10 ? null : $this->faker->text(500);
            $rand = rand(2, 20);
            for ($j = 0; $j < $rand; ++$j) {
                $randId = rand(0, TagFixtures::TOTAL_ENTITIES - 1);
                $tag = $this->getReference(sprintf("Tag.%s", $randId));
                if (!$post->tags->contains($tag)) {
                    $post->tags->add($tag);
                }
            }
            $author = $this->getReference(sprintf("Author.%s", $authors[$authorLoop % \count($authors)]["firstname"]));
            ++$authorLoop;

            $post->author = $author;
            $this->setReference(sprintf("Post.%s", $i), $post);

            $this->manager->persist($post);
        }
        $this->manager->flush();
    }
}
