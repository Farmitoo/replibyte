<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Executor;

use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class DatabaseCleaner implements DatabaseCleanerInterface
{
    use OutputAwareTrait;

    protected KernelInterface $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function clean(): void
    {
        $this->writeln(["Clean DB started", "============"]);

        $this->writeln(["Delete base"]);
        $output = [];
        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $output = new BufferedOutput();

        // Delete base
        $input = new ArrayInput([
            "command" => "d:d:d",
            "--force" => true,
            "--if-exists" => true,
        ]);
        $application->run($input, $output);
        $this->writeln(["Create base"]);

        // Create base
        $input = new ArrayInput([
            "command" => "d:d:c",
        ]);
        $application->run($input, $output);
        $this->writeln(["Update schema"]);

        // Schema update
        $input = new ArrayInput([
            "command" => "d:s:u",
            "--force" => true,
        ]);
        $application->run($input, $output);

        $this->writeln(["Insert migrations"]);
        // Insert migrations
        $input = new ArrayInput([
            "command" => "doctrine:migrations:sync-metadata-storage",
        ]);
        $application->run($input, $output);

        $input = new ArrayInput([
            "command" => "doctrine:migrations:version",
            "--add" => true,
            "--all" => true,
            "--no-interaction" => true,
        ]);
        $application->run($input, $output);

        $this->writeln(["============", "Clean DB ended", ""]);
    }
}
