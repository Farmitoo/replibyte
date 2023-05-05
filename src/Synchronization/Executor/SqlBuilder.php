<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Executor;

use Farmitoo\ReplibyteBundle\Connection\PDOConnectionInterface;
use Farmitoo\ReplibyteBundle\Synchronization\Model\TableModel;
use Farmitoo\ReplibyteBundle\Synchronization\Provider\ConfigurationProviderInterface;

class SqlBuilder
{
    protected ConfigurationProviderInterface $configurationProvider;
    protected \PDO $dbLocale;
    protected array $tableModels = [];
    protected PDOConnectionInterface $localPDOConnection;

    public function __construct(ConfigurationProviderInterface $configurationProvider, PDOConnectionInterface $localPDOConnection)
    {
        $this->configurationProvider = $configurationProvider;
        $this->localPDOConnection = $localPDOConnection;
    }

    public function selectQuery(array $tableModels, string $tableName, array $columns, array $forceIds = [], array $exceptsIds = []): string
    {
        $this->dbLocale = $this->localPDOConnection->getPDO();
        $this->tableModels = $tableModels;
        $andWhere = $this->getWhere($tableName, $forceIds);

        // @todo add #aliasTable# to the query to allow add more where constraint (to add pricing validity constraints and keep only newests or only available products)
        return sprintf("SELECT %s FROM %s WHERE 1 = 1 %s %s %s %s;",
            $this->getColumnsString($columns),
            $tableName,
            $exceptsIds ? sprintf(" AND id NOT IN (%s)", implode(", ", $exceptsIds)) : "", // NOT IN (exceptedIds)
            $andWhere, // WHERE
            isset($columns["id"]) ? "ORDER BY RAND()" : "", // ORDER
            $this->getLimit($tableName, $forceIds, !empty($andWhere)) // LIMIT
        );
    }

    public function insertQuery($tableName, $columns, $result): string
    {
        $stringValues = sprintf(" (%s)", implode(", ", array_map(fn ($val) => null !== $val ? $this->dbLocale->quote((string) $val) : "NULL", $result)));

        return sprintf("INSERT INTO %s (%s) VALUES %s",
            $tableName,
            $this->getColumnsString($columns),
            $stringValues
        );
    }

    protected function getColumnsString(array $columns): string
    {
        return sprintf("%s", implode(", ", $columns));
    }

    protected function getWhere(string $tableName, array $forceIds = []): string
    {
        $columnsNullable = $this->dbLocale->query(sprintf("SELECT `COLUMN_NAME`, `IS_NULLABLE` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_NAME`='%s'", $tableName))->fetchAll(\PDO::FETCH_KEY_PAIR);
        $where = "";
        $criteriaAnd = [];
        $circulars = [];
        if ($forceIds) {
            return sprintf(" AND id IN (%s)", implode(", ", $forceIds));
        }
        if (isset($this->tableModels[$tableName])) {
            $tableModel = $this->tableModels[$tableName];
            foreach ($tableModel->whereRestrictions as $restriction) {
                $column = $restriction["column"];
                if (TableModel::WHERE_VALUE_FORCED_NULL === $restriction["values"]) {
                    $circulars[] = sprintf(" (%s IS NULL OR id = %s) ", $column, $column);
                    continue;
                }

                $list = implode(", ", $restriction["values"]);

                if ($restriction["circular"]) {
                    $criteriaAnd[] = sprintf(" (id NOT IN (%s)) ", $list);
                    $criteriaAnd[] = sprintf(" (%s IN (%s)) ",
                        $column,
                        $list,
                    );
                    continue;
                }
                if (empty($restriction["values"])) {
                    $criterion = sprintf(" %s IS NULL ",
                        $column,
                    );
                } else {
                    $nullString = "";
                    if ("YES" === $columnsNullable[$column]) {
                        $nullString = sprintf(" OR %s IS NULL", $column);
                    }
                    $criterion = sprintf(" (%s IN (%s)%s) ",
                        $column,
                        $list,
                        $nullString
                    );
                }

                $criteriaAnd[] = $criterion;
            }

            $circularWhere = "";
            foreach ($circulars as $key => $circular) {
                if (0 !== $key) {
                    $circularWhere .= " AND ";
                }
                $circularWhere .= $circular;
            }

            if ($circularWhere) {
                $criteriaAnd[] = $circularWhere;
                $where .= sprintf("AND %s", $circularWhere);
            }

            foreach ($criteriaAnd as $criterion) {
                $where .= sprintf("AND %s", $criterion);
            }
        }

        return $where;
    }

    protected function getLimit(string $tableName, array $forceIds, $hasWhereConstraint): string
    {
        if ($forceIds) {
            return "";
        }

        $limit = $this->configurationProvider->getLimit($tableName);
        if (false === $limit) {
            return ""; // no limit
        }

        $globalLimit = $this->configurationProvider->getGlobalLimit();
        if ($hasWhereConstraint) { // where constraint from foreign key already inserted
            return sprintf("LIMIT %s", $globalLimit);
        }

        $limit = $limit ?: $globalLimit;

        return sprintf("LIMIT %s", $limit);
    }
}
