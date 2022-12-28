<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization;

trait OutputAwareTrait
{
    protected ?Output $output = null;

    public function setOutput(?Output $output): void
    {
        $this->output = $output;
    }

    public function writeln(string|array $data): void
    {
        if (null === $this->output) {
            return;
        }

        $this->output->writeln($data);
    }
}
