<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Provider;

interface ConfigurationProviderInterface
{
    public function getLimit(string $tableName): null|int|bool;

    public function isDisabledTable(string $tableName): bool;

    public function forceSpecificData(string $tableName): ?array;

    public function getInvisibleConstraints(?string $tableName = null): ?array;

    public function resetTablesConstraintData(): void;

    public function addTableConstraint(string $tableName, array $config): void;

    public function getGlobalLimit(): int;

    public function setGlobalLimit(int $limit);

    public function setDisableRandomInsert(): void;

    public function isEnableRandomInsert(): bool;
}
