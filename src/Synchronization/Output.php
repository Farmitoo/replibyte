<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization;

class Output
{
    /**
     * @var callable
     */
    protected $writelnCallable;

    public function __construct($writelnCallable)
    {
        $this->writelnCallable = $writelnCallable;
    }

    public function writeln(string|array $lines, bool $title = false): void
    {
        if ($this->writelnCallable) {
            $writeLnCallable = $this->writelnCallable;
            $writeLnCallable($lines, $title);
        }
    }
}
