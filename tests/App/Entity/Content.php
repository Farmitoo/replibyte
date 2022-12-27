<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\Entity;

class Content
{
    public int $id;
    public string $reference; // invisible database link to Tag
    public string $text;
}
