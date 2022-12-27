<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Executor;

use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareInterface;

interface DatabaseCleanerInterface extends OutputAwareInterface
{
    public function clean(): void;
}
