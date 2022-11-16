<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Command;

use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: "farmitoo:replibyte:db-test",
    description: "Test both distant and local connection",
    hidden: false,
)]
class TestDabatasesConnectionsCommand extends Command
{
    protected PDOConnectionInterface $distantPDOConnection;
    protected PDOConnectionInterface $localPDOConnection;

    public function __construct(PDOConnectionInterface $distantPDOConnection, PDOConnectionInterface $localPDOConnection)
    {
        $this->distantPDOConnection = $distantPDOConnection;
        $this->localPDOConnection = $localPDOConnection;
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>TEST DISTANT CONNECTION</info>");
        if ($this->distantPDOConnection->testConnection()) {
            $output->writeln("✅ connexion OK");
        } else {
            $output->writeln("❌ connexion KO");
        }
        $output->writeln("---");
        $output->writeln("<info>TEST LOCAL CONNECTION</info>");
        if ($this->localPDOConnection->testConnection()) {
            $output->writeln("✅ connexion OK");
        } else {
            $output->writeln("❌ connexion KO");
        }

        return Command::SUCCESS;
    }
}
