<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Executor;

use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareInterface;

interface DataTransferInterface extends OutputAwareInterface
{
    public function fromDistantToLocal(array $tableModels): void;
}
