<?php

namespace App\Models;

use Framework\DB\Connection;

/**
 * BaseModel - small helper for models to access DB and common fetch helpers.
 */
class BaseModel
{
    protected Connection $conn;

    public function __construct()
    {
        $this->conn = Connection::getInstance();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    protected function run(string $sql, array $params = []): bool
    {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    protected function lastInsertId(): string
    {
        return $this->conn->lastInsertId();
    }
}

