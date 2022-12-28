<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization;

interface OutputAwareInterface
{
    public function setOutput(?Output $output): void;

    public function writeln(string|array $data): void;
}
