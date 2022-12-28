<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Command;

use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Replicator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: "farmitoo:replibyte:execute",
    description: "Execute the replication of the distant database to a locale database.",
    hidden: false,
)]
class ExecuteReplicationCommand extends Command
{
    protected string $appEnvironment;
    protected PDOConnectionInterface $distantPDOConnection;
    protected PDOConnectionInterface $localPDOConnection;
    protected Replicator $databaseReplication;

    public function __construct(string $appEnvironment, PDOConnectionInterface $distantPDOConnection, PDOConnectionInterface $localPDOConnection, Replicator $databaseReplication)
    {
        $this->appEnvironment = $appEnvironment;
        $this->distantPDOConnection = $distantPDOConnection;
        $this->localPDOConnection = $localPDOConnection;
        $this->databaseReplication = $databaseReplication;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption("limit", null, InputOption::VALUE_REQUIRED, "How many line for each table")
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("--- Replibyte : Execute --- ");
        if ("prod" === $this->appEnvironment) {
            $io->error("This command cannot be used in production environment");

            return Command::FAILURE;
        }

        $io->info(
            sprintf("You are going to start a database replication %s %s",
                sprintf("%s from: %s", \PHP_EOL, $this->distantPDOConnection->getHost()),
                sprintf("%s to: %s", \PHP_EOL, $this->localPDOConnection->getHost())
            )
        );
        $io->warning("This will drop the locale database");

        $answer = $io->ask('Please check the both host and your environment variables and write "confirmed" in the prompt when done');

        if ("confirmed" !== $answer) {
            $io->section("aborted");

            return Command::INVALID;
        }

        $io->section("confirmed");

        $io->info("The process has been launch and can take a while. Please do not interrupt it.");

        $this->databaseReplication->setWriteln(function (string|array $lines, bool $isTitle) use ($output) {
            if ($isTitle && \is_string($lines)) {
                $output->writeln("<bg=cyan;options=bold>".$lines."</>");

                return;
            }

            $output->writeln($lines);
        });
        $limit = $input->getOption("limit");

        $this->databaseReplication->execute($limit ? (int) $limit : null);

        return Command::SUCCESS;
    }
}
