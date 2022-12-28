<?php

declare(strict_types=1);

namespace Farmitoo\ReplibyteBundle\Connection;

class PDOConnection implements PDOConnectionInterface
{
    protected string $dbHost;
    protected string $dbName;
    protected string $dbUser;
    protected string $dbPassword;
    protected ?\PDO $connection = null;

    public function __construct(string $dbHost, string $dbName, string $dbUser, string $dbPassword)
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPassword = $dbPassword;
    }

    public function getHost(): string
    {
        return $this->dbHost;
    }

    public function getPDO(): \PDO
    {
        if (null !== $this->connection) {
            return $this->connection;
        }
        $this->connection = new \PDO(sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4;collate=utf8mb4_unicode_ci", $this->dbHost, $this->dbName), $this->dbUser, $this->dbPassword);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

        return $this->connection;
    }

    public function testConnection(): bool
    {
        try {
            $this->getPDO()->exec("SHOW TABLES");

            return true;
        } catch (\PDOException $PDOException) {
            return false;
        }
    }
}
