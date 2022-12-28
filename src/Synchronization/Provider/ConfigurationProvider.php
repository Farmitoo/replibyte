<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Provider;

class ConfigurationProvider implements ConfigurationProviderInterface
{
    protected const DEFAULT_USER_LIMIT = 100;

    protected array $forceTableConstraints;
    protected array $tablesConfig;
    protected int $globalLimit;
    protected bool $disableRandomInsert = false;

    public function __construct(array $replibyteForceTableConstraints, array $replibyteTableCustomConfiguration)
    {
        $this->tablesConfig = $replibyteTableCustomConfiguration;
        $this->forceTableConstraints = $replibyteForceTableConstraints;
        $this->globalLimit = self::DEFAULT_USER_LIMIT;
    }

    public function getLimit(string $tableName): null|int|bool
    {
        if (!isset($this->tablesConfig[$tableName]["limit"])) {
            return $this->globalLimit;
        }

        return $this->tablesConfig[$tableName]["limit"];
    }

    public function isDisabledTable(string $tableName): bool
    {
        return $this->tablesConfig[$tableName]["disabled"] ?? false;
    }

    public function forceSpecificData(string $tableName): array
    {
        return $this->tablesConfig[$tableName]["data"] ?? [];
    }

    public function getInvisibleConstraints(?string $tableName = null): ?array
    {
        if (!$this->forceTableConstraints) {
            return [];
        }

        if ($tableName) {
            return array_filter($this->forceTableConstraints, function ($value) use ($tableName) { return $tableName === $value["TABLE_NAME"]; });
        }

        return $this->forceTableConstraints;
    }

    public function resetTablesConstraintData(): void
    {
        foreach ($this->tablesConfig as &$config) {
            $data = [];
            foreach ($config["data"] as $datum) {
                if ("WHERE 1 = 1" === $datum) {
                    $data[] = $datum;
                }
            }

            if (empty($data)) {
                unset($config["data"]);

                return;
            }

            $config["data"] = $data;
        }
    }

    public function addTableConstraint(string $tableName, array $config): void
    {
        $this->tablesConfig[$tableName] = array_merge($this->tablesConfig[$tableName] ?? [], $config);
    }

    public function getGlobalLimit(): int
    {
        return $this->globalLimit;
    }

    public function setGlobalLimit(int $limit): void
    {
        $this->globalLimit = $limit;
    }

    public function setDisableRandomInsert(): void
    {
        $this->disableRandomInsert = true;
    }

    public function isEnableRandomInsert(): bool
    {
        return !$this->disableRandomInsert;
    }
}
