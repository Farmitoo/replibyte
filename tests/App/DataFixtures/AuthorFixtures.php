<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Farmitoo\ReplibyteBundle\Tests\App\Entity\Author;

class AuthorFixtures extends Fixture
{
    protected ObjectManager $manager;

    public static function getAuthors(): array
    {
        return [
            ["firstname" => "Henry", "lastname" => "Durant", "country" => "FR"],
            ["firstname" => "Diana", "lastname" => "Mattia", "country" => "IT"],
            ["firstname" => "Robert", "lastname" => "Camus", "country" => "FR"],
            ["firstname" => "Jasper", "lastname" => "Müller", "country" => "DE"],
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $data = self::getAuthors();
        foreach ($data as $datum) {
            $author = new Author();
            $author->country = $datum["country"];
            $author->firstname = $datum["firstname"];
            $author->lastname = $datum["lastname"];

            $this->setReference(sprintf("Author.%s", $datum["firstname"]), $author);

            $this->manager->persist($author);
        }

        $this->manager->flush();
    }
}
