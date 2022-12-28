<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization;

use Doctrine\ORM\EntityManagerInterface;
use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Executor\DatabaseCleanerInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Executor\DataTransfer;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\ConfigurationProviderInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\TableModelProvider;

class Replicator implements OutputAwareInterface
{
    use OutputAwareTrait;

    protected EntityManagerInterface $em;
    protected PDOConnectionInterface $distantPDOConnection;
    protected PDOConnectionInterface $localPDOConnection;
    protected DatabaseCleanerInterface $databaseCleaner;
    protected ConfigurationProviderInterface $configurationProvider;
    protected TableModelProvider $tableModelProvider;
    protected DataTransfer $dataTransfer;

    protected \PDO $dbDistant;
    protected \PDO $dbLocale;

    public function __construct(
        EntityManagerInterface $em,
        PDOConnectionInterface $distantPDOConnection,
        PDOConnectionInterface $localPDOConnection,
        DatabaseCleanerInterface $databaseCleaner,
        ConfigurationProviderInterface $configurationProvider,
        TableModelProvider $tableModelProvider,
        DataTransfer $dataTransfer,
    ) {
        $this->em = $em;

        $this->distantPDOConnection = $distantPDOConnection;
        $this->localPDOConnection = $localPDOConnection;
        $this->databaseCleaner = $databaseCleaner;
        $this->configurationProvider = $configurationProvider;
        $this->tableModelProvider = $tableModelProvider;
        $this->dataTransfer = $dataTransfer;
    }

    public function setConstraints(array $tablesConfigs): void
    {
        $this->configurationProvider->resetTablesConstraintData();
        foreach ($tablesConfigs as $tableName => $config) {
            $this->configurationProvider->addTableConstraint($tableName, $config);
        }
    }

    public function insertNewData(?int $limit = null): void
    {
        if ($limit) {
            $this->configurationProvider->setGlobalLimit($limit);
        }
        $this->configurationProvider->setDisableRandomInsert();

        $startAt = microtime(true);
        $this->prepareDbs();
        $tableModels = $this->getTableModels();
        $this->executeTransfer($tableModels);
        $endAt = microtime(true);

        $this->writeln("---------- ENDED ---------- ");
        $this->writeln(sprintf("Execution done in %d minutes", ($endAt - $startAt) / 60));
        $this->writeln("--------------------------- ");
    }

    public function execute(?int $limit = null): void
    {
        if ($limit) {
            $this->configurationProvider->setGlobalLimit($limit);
        }
        $startAt = microtime(true);
        $this->prepareDbs();
        $this->resetDbLocale();
        $tableModels = $this->getTableModels();
        $this->executeTransfer($tableModels);
        $endAt = microtime(true);

        $this->writeln("---------- ENDED ---------- ");
        $this->writeln(sprintf("Execution done in %d minutes", ($endAt - $startAt) / 60));
        $this->writeln("--------------------------- ");
    }

    protected function executeTransfer(array $tableModels): void
    {
        $this->dataTransfer->setOutput($this->output);

        $this->dataTransfer->fromDistantToLocal($tableModels);
    }

    protected function resetDbLocale(): void
    {
        $this->databaseCleaner->setOutput($this->output);

        $this->databaseCleaner->clean();
    }

    protected function getTableModels(): array
    {
        $this->tableModelProvider->setOutput($this->output);

        return $this->tableModelProvider->provideAll();
    }

    protected function prepareDbs(): void
    {
        $this->dbDistant = $this->distantPDOConnection->getPDO();
        $this->dbLocale = $this->localPDOConnection->getPDO();
    }

    public function setWriteln(callable $writelnCallable)
    {
        $this->output = new Output($writelnCallable);
    }
}
