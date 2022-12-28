<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\Entity;

class CategoryImage
{
    public int $id;
    public string $path;
    public Category $category;
}
