<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Executor;

use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Model\TableModel;
use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareTrait;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\ConfigurationProviderInterface;

class DataTransfer implements DataTransferInterface
{
    use OutputAwareTrait;

    protected \PDO $dbDistant;
    protected \PDO $dbLocale;
    protected ConfigurationProviderInterface $configurationProvider;
    protected SqlBuilder $sqlBuilder;

    /**
     * @var TableModel[]
     */
    protected array $tableModels;

    /**
     * tableName => tableName
     * Allow to know which table should not be insert in the current Loop because of the need of other tables (and their foreign key).
     */
    protected array $tablesReferenced = [];

    /**
     * tableName => Ids
     * where ids are those already inserted for the given table.
     */
    protected array $tableIdInserted = [];
    protected PDOConnectionInterface $distantPDOConnection;
    protected PDOConnectionInterface $localPDOConnection;

    public function __construct(ConfigurationProviderInterface $configurationProvider, PDOConnectionInterface $distantPDOConnection, PDOConnectionInterface $localPDOConnection, SqlBuilder $sqlBuilder)
    {
        $this->configurationProvider = $configurationProvider;
        $this->sqlBuilder = $sqlBuilder;
        $this->distantPDOConnection = $distantPDOConnection;
        $this->localPDOConnection = $localPDOConnection;
    }

    public function fromDistantToLocal(array $tableModels): void
    {
        $this->dbLocale = $this->localPDOConnection->getPDO();
        $this->dbDistant = $this->distantPDOConnection->getPDO();
        $this->tableModels = $tableModels;
        $this->removeDisabledTables();
        $this->fillCurrentTableReferenced();
        $this->writeln("-----------------------------------------------------------");
        $this->writeln("init ids already inserted in Database");
        $this->writeln("-----------------------------------------------------------");

        $this->initTableIdInserted();

        $this->writeln("-----------------------------------------------------------");
        $this->writeln("Start Fill tables in Database using Tables Models generated");
        $this->writeln("-----------------------------------------------------------");

        while (\count($this->tableModels) > 0) {
            $this->writeln("----- New Loop -----");
            foreach ($this->tableModels as $tableName => $el) {
                $this->insertTableIfAllowed($tableName);
            }
            $this->fillCurrentTableReferenced();
        }
    }

    protected function removeDisabledTables(): void
    {
        foreach ($this->tableModels as $tableName => $tableModel) {
            if ($this->configurationProvider->isDisabledTable($tableName)) {
                unset($this->tableModels[$tableName]);
                continue;
            }
        }
    }

    protected function fillCurrentTableReferenced(): void
    {
        $this->tablesReferenced = [];

        foreach ($this->tableModels as $tableName => $tableModel) {
            if (empty($tableModel->referencedByTables)) {
                continue;
            }
            foreach ($tableModel->referencedByTables as $key => $data) {
                if ($tableName === $key) {
                    continue;
                }
                $this->tablesReferenced[$key] = $key;
            }
        }
    }

    protected function selectAndInsertTable(string $tableName): array
    {
        $this->writeln(sprintf("------> insert Table %s <-----", $tableName));
        $columns = $this->dbDistant->query(sprintf("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='%s'", $tableName))->fetchAll(\PDO::FETCH_COLUMN);
        $columnsLocales = $this->dbLocale->query(sprintf("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='%s'", $tableName))->fetchAll(\PDO::FETCH_COLUMN);
        $columns = array_combine($columns, $columns);
        $missingColumns = array_diff($columns, $columnsLocales); // Tables dans la base distante qui n'existe pas dans le schéma local
        foreach ($missingColumns as $col) {
            unset($columns[$col]);
        }

        $resultsIds = [];
        $query = $this->sqlBuilder->selectQuery($this->tableModels, $tableName, $columns, [], $this->tableIdInserted[$tableName]);
        $results = $this->dbDistant->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $tableModel = $this->tableModels[$tableName];
        foreach ($tableModel->forceIds as $key => $forceIds) { // Ids necessary for other tables as foreign_key
            $this->writeln(sprintf("%d forced Ids (%s)", \count($forceIds), $key));
            $exceptIds = array_map(fn ($result) => $result["id"], $results);
            $query = $this->sqlBuilder->selectQuery($this->tableModels, $tableName, $columns, $forceIds, array_merge($exceptIds, $this->tableIdInserted[$tableName], $this->tableIdInserted[$tableName] ?? []));
            $results2 = $this->dbDistant->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            $results = array_merge($results2, $results);
        }

        $this->writeln(sprintf("%d lines to insert", \count($results)));
        $countErrors = 0;
        foreach ($results as $key => $result) {
            try {
                $insertQuery = $this->sqlBuilder->insertQuery($tableName, $columns, $result);
                $this->dbLocale->query($insertQuery);
                if (isset($columns["id"])) {
                    $resultsIds[] = $result["id"];
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), "a foreign key constraint fails") && $tableModel->hasCircularChild) {
                    continue;
                }
                ++$countErrors;
                $this->writeln(sprintf("Error for table %s %s : %s", $tableName, isset($result["id"]) ? " and id ".$result["id"] : "", $e->getMessage()));
            }
        }
        if ($countErrors) {
            $this->writeln(sprintf("%d errors when trying to insert", $countErrors));
        }

        return $resultsIds;
    }

    protected function insertTableIfAllowed(string $tableName): void
    {
        if (\array_key_exists($tableName, $this->tablesReferenced)) {
            return;
        }
        $ids = $this->selectAndInsertTable($tableName);
        $this->tableIdInserted[$tableName] = array_merge($this->tableIdInserted[$tableName] ?? [], $ids);

        $el = $this->tableModels[$tableName];
        if (empty($el->referencedByTables)) {
            unset($this->tableModels[$tableName]);

            return;
        }

        $constraints = $el->referencedByTables;
        if ($el->hasCircularChild) {
            $this->writeln(sprintf("%s is circular", $tableName));
        }

        foreach ($constraints as $key => $data) {
            foreach ($data as $datum) {
                if (!isset($this->tableModels[$key])) {
                    continue;
                }
                $this->tableModels[$key]->whereRestrictions[$datum["column"]] = [
                    "column" => $datum["column"],
                    "values" => $this->tableIdInserted[$tableName],
                    "circular" => $key === $tableName,
                ];
            }
        }

        if (!$el->hasCircularChild || empty($ids)) {
            unset($this->tableModels[$tableName]);
        }
    }

    protected function initTableIdInserted(): void
    {
        foreach ($this->tableModels as $tableName => $el) {
            $columnsLocales = $this->dbLocale->query(sprintf("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='%s'", $tableName))->fetchAll(\PDO::FETCH_COLUMN);

            $this->tableIdInserted[$tableName] = [];
            if (!\in_array("id", $columnsLocales, true)) {
                continue;
            }

            $this->tableIdInserted[$tableName] = $this->dbLocale->query(sprintf("SELECT id FROM %s", $tableName))->fetchAll(\PDO::FETCH_COLUMN);
        }

        foreach ($this->tableModels as $tableName => $el) {
            if (!$this->tableIdInserted[$tableName]) {
                continue;
            }
            $constraints = $el->referencedByTables;
            foreach ($constraints as $key => $data) {
                foreach ($data as $datum) {
                    if (!isset($this->tableModels[$key])) {
                        continue;
                    }

                    $this->tableModels[$key]->whereRestrictions[$datum["column"]] = [
                        "column" => $datum["column"],
                        "values" => $this->tableIdInserted[$tableName],
                        "circular" => $key === $tableName,
                    ];
                }
            }
        }
    }
}
