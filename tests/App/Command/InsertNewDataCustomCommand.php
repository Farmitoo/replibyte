<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Tests\App\Command;

use Farmitoo\ReplibyteBundle\Synchronization\Replicator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "app:insert:posts",
    description: "Insert post for given author with firstname given in argument",
    hidden: false,
)]
class InsertNewDataCustomCommand extends Command
{
    protected Replicator $replicator;

    public function __construct(Replicator $replicator)
    {
        $this->replicator = $replicator;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument("firstname", InputArgument::REQUIRED, "firstname")
            ->addOption("limit", null, InputOption::VALUE_REQUIRED, "how many line ? default 100")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $firstname = $input->getArgument("firstname");

        $this->replicator->setWriteln(function (string|array $lines, bool $isTitle) use ($output) {
            if ($isTitle && \is_string($lines)) {
                $output->writeln("<bg=cyan;options=bold>".$lines."</>");

                return;
            }

            $output->writeln($lines);
        });
        $limit = $input->getOption("limit") ?? 100;

        $this->replicator->setConstraints(
            [
                "replibyte_post" => [
                    "data" => [
                        sprintf("
                           JOIN replibyte_author a ON a.id = #aliasTable#.author_id
                           WHERE LOWER(a.firstname) = LOWER('%s') LIMIT %d
                         ", $firstname, (int) $limit),
                    ],
                ],
            ]
        );
        $this->replicator->insertNewData();

        return Command::SUCCESS;
    }
}
