<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Connection;

interface PDOConnectionInterface
{
    public function getPDO(): \PDO;

    public function getHost(): string;

    public function testConnection(): bool;
}
