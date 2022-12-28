<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Provider;

interface TableModelProviderInterface
{
    public function provideAll(): array;
}
