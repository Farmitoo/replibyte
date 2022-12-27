<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Post
{
    public int $id;
    public string $uuid;
    public string $title;
    public ?string $description = null;
    public ?Author $author = null;
    public ?Category $category = null;
    public Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}
