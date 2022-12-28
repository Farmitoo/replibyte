<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Synchronization\Model;

/**
 * manage all table relationship with other & itself
 * and prepare Ids that will be insert into the local database.
 */
class TableModel
{
    public const WHERE_VALUE_FORCED_NULL = "force-null";

    public string $name = "";
    public string $alias = "";
    public array $referencedByTables = [];
    public array $whereRestrictions = [];
    public bool $hasCircularChild = false;
    public array $mandatoryJoinTables = [];
    public array $forceIds = [];

    public function __construct(string $name, array $referencedByTables = [], array $whereRestrictions = [], bool $hasCircularChild = false)
    {
        $this->name = $name;
        $this->alias = uniqid($name);
        $this->referencedByTables = $referencedByTables;
        $this->whereRestrictions = $whereRestrictions;
        $this->hasCircularChild = $hasCircularChild;
    }
}
