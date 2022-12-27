<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Provider;

use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Model\TableModel;
use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareInterface;
use Farmitoo\ReplibyteBundle\Synchronization\OutputAwareTrait;

class TableModelProvider implements TableModelProviderInterface, OutputAwareInterface
{
    use OutputAwareTrait;

    /**
     * @var TableModel[]
     */
    protected array $tableModels = [];

    protected \PDO $dbLocale;
    protected \PDO $dbDistant;
    protected ConfigurationProviderInterface $configurationProvider;

    public function __construct(ConfigurationProviderInterface $configurationProvider, PDOConnectionInterface $distantPDOConnection, PDOConnectionInterface $localPDOConnection)
    {
        $this->configurationProvider = $configurationProvider;
        $this->dbLocale = $localPDOConnection->getPDO();
        $this->dbDistant = $distantPDOConnection->getPDO();
    }

    public function provideAll(): array
    {
        $this->writeln("-----------------------------------");
        $this->writeln("Generation of Tables Models started");
        $this->writeln("-----------------------------------");

        $this->insertFillReferencedByTables();
        $this->insertNotReferencedTables();
        $this->insertMandatoryJoinTables();
        $this->insertForcedValuesAndConstraints();

        return $this->tableModels;
    }

    protected function insertNotReferencedTables(): void
    {
        $tablesDistant = $this->dbDistant->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $tablesLocale = $this->dbLocale->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        $tables = array_combine($tablesDistant, $tablesDistant);

        $tablesToNotFill = array_diff($tables, $tablesLocale); // Tables dans la base distante qui n'existe pas dans le schéma local
        foreach ($tables as $tableName) {
            if (\in_array($tableName, $tablesToNotFill, true) || isset($this->tableModels[$tableName])) {
                continue;
            }
            $this->tableModels[$tableName] = new TableModel($tableName);
        }
    }

    protected function insertFillReferencedByTables(): void
    {
        $results = $this->getConstraints();
        $tablesConstraints = [];
        foreach ($results as $result) {
            $tablesConstraints[$result["REFERENCED_TABLE_NAME"]][$result["TABLE_NAME"]][] = [
                "column" => $result["COLUMN_NAME"],
            ];
        }

        foreach ($tablesConstraints as $tableName => $data) {
            $referencedByTables = [];
            $columnsRestrictions = [];
            $hasCircular = false;

            foreach ($data as $subTableName => $columns) {
                if ($subTableName === $tableName) {
                    array_map(function ($column) use (&$columnsRestrictions) {
                        $columnsRestrictions[$column["column"]] = [
                            "column" => $column["column"],
                            "values" => TableModel::WHERE_VALUE_FORCED_NULL,
                        ];
                    }, $columns);
                    $hasCircular = true;
                }
                array_map(function ($column) use (&$referencedByTables, $subTableName) {
                    $referencedByTables[$subTableName][] = $column;
                }, $columns);
            }

            if (!isset($this->tableModels[$tableName])) {
                $this->tableModels[$tableName] = new TableModel($tableName);
            }

            $this->tableModels[$tableName]->referencedByTables = $referencedByTables;
            $this->tableModels[$tableName]->whereRestrictions = $columnsRestrictions;
            $this->tableModels[$tableName]->hasCircularChild = $hasCircular;
        }
    }

    protected function insertMandatoryJoinTables()
    {
        foreach ($this->tableModels as $tableName => $constraint) {
            $neededTables = $this->getConstraints($tableName);

            $tables[$tableName] = $constraint->alias;
            while (\count($neededTables) > 0) {
                $newTables = [];
                foreach ($neededTables as $table) {
                    $alias = uniqid($table["REFERENCED_TABLE_NAME"]);
                    $constraint->mandatoryJoinTables[] = [
                        "table" => $table["TABLE_NAME"],
                        "column" => $table["COLUMN_NAME"],
                        "aliasTable" => $tables[$table["TABLE_NAME"]],
                        "joinTable" => $table["REFERENCED_TABLE_NAME"],
                        "joinColumn" => $table["REFERENCED_COLUMN_NAME"],
                        "aliasJoinTable" => $alias,
                    ];
                    if ($table["TABLE_NAME"] !== $table["REFERENCED_TABLE_NAME"]) {
                        $newTables[$table["REFERENCED_TABLE_NAME"]] = $alias;
                    }
                }
                $tables = $newTables;
                $tablesString = implode(",", array_map(fn ($val) => $this->dbLocale->quote($val), array_keys($tables)));
                if (empty($tablesString)) {
                    $neededTables = [];
                    continue;
                }
                $stmt = $this->dbLocale->query(sprintf("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME , REFERENCED_COLUMN_NAME  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME IN (%s)", $tablesString));
                $neededTables = $stmt->fetchAll();
            }
        }
    }

    protected function getConstraints(string $tableName = null): array
    {
        if ($tableName) {
            $stmt = $this->dbLocale->query(sprintf("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME , REFERENCED_COLUMN_NAME  FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME = '%s'", $tableName));
        } else {
            $stmt = $this->dbLocale->query("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME , REFERENCED_COLUMN_NAME FROM `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE` WHERE REFERENCED_TABLE_NAME IS NOT NULL");
        }
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_merge($results, $this->configurationProvider->getInvisibleConstraints($tableName));
    }

    protected function insertForcedValuesAndConstraints()
    {
        foreach ($this->tableModels as $tableName => $el) {
            $columnsLocales = $this->dbLocale->query(sprintf("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='%s'", $tableName))->fetchAll(\PDO::FETCH_COLUMN);

            $forceJoinAndWhere = $this->configurationProvider->forceSpecificData($tableName);
            if (!$forceJoinAndWhere && $this->configurationProvider->isEnableRandomInsert()) {
                $forceJoinAndWhere = [sprintf("WHERE 1 = 1 LIMIT %s", $this->configurationProvider->getGlobalLimit())];
            }

            foreach ($forceJoinAndWhere as $where) {
                $columnsKeys = [];
                $columns = [];

                $el = $this->tableModels[$tableName];
                $string = sprintf("%s %s ", $tableName, $el->alias);

                if (\in_array("id", $columnsLocales, true)) {
                    $columns[] = sprintf("%s.id as `%s`", $el->alias, $el->alias);
                    $columnsKeys[$el->alias] = $tableName;
                }

                foreach ($el->mandatoryJoinTables as $data) {
                    $columnsKeys[$data["aliasJoinTable"]] = $data["joinTable"];
                    $columns[] = sprintf("%s.%s as `%s`", $data["aliasJoinTable"], $data["joinColumn"], $data["aliasJoinTable"]);
                    $string .= sprintf("LEFT JOIN %s %s ON %s.%s = %s.%s ", $data["joinTable"], $data["aliasJoinTable"], $data["aliasJoinTable"], $data["joinColumn"], $data["aliasTable"], $data["column"]);
                    $string .= \PHP_EOL;
                }

                $string = sprintf("SELECT %s FROM %s ", implode(", ", $columns), $string);
                $string .= sprintf("%s", str_replace("#aliasTable#", $el->alias, $where));
                $results = $this->dbDistant->query($string)->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($results as $result) {
                    foreach ($columnsKeys as $key => $subTableName) {
                        if (null === $result[$key]) {
                            continue;
                        }
                        $this->tableModels[$subTableName]->forceIds[$tableName][$result[$key]] = $result[$key];
                    }
                }
            }
        }
    }
}
