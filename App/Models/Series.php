<?php

namespace App\Models;

use Framework\DB\Connection;

class Series
{
    // Return all series with a book count
    public static function allWithCounts(): array
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT s.id, s.name, COUNT(k.id_kniha) AS count FROM serie s LEFT JOIN kniha k ON k.series_id = s.id GROUP BY s.id, s.name ORDER BY s.name');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    // Create series and return new id (int) or null on failure
    public static function create(string $name): ?int
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('INSERT INTO serie (name) VALUES (:name)');
        if ($stmt->execute([':name' => $name])) {
            return (int)$conn->lastInsertId();
        }
        return null;
    }

    // Update existing series; returns affected rows
    public static function update(int $id, string $name): int
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('UPDATE serie SET name = :name WHERE id = :id');
        $stmt->execute([':name' => $name, ':id' => $id]);
        return $stmt->rowCount();
    }

    // Delete series by id; returns affected rows
    public static function delete(int $id): int
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('DELETE FROM serie WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // Count books in series
    public static function countBooks(int $id): int
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM kniha WHERE series_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
}

