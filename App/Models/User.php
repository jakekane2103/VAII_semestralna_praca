<?php

namespace App\Models;

use Framework\Core\IIdentity;
use Framework\DB\Connection;

/**
 * Simple User value object representing an authenticated user.
 */
class User implements IIdentity
{
    public function __construct(
        public ?int $id = null,
        public string $username = '',
        public string $name = ''
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Convenience factory to load a user by id from the database.
     * Returns an instance of App\Models\User or null when not found.
     */
    public static function findById(int $id): ?self
    {
        try {
            $conn = Connection::getInstance();

            // Primary schema (zakaznik)
            // Note: avoid CONCAT(meno, " ", priezvisko) to keep IDE SQL inspection happy; concatenate safely in PHP.
            $stmt = $conn->prepare('SELECT id_zakaznik AS id, email AS username, meno, priezvisko FROM zakaznik WHERE id_zakaznik = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $first = trim((string)($row['meno'] ?? ''));
                $last = trim((string)($row['priezvisko'] ?? ''));
                $full = trim($first . ' ' . $last);
                return new self(id: (int)$row['id'], username: (string)($row['username'] ?? ''), name: $full);
            }
        } catch (\Throwable $e) {
            // silent - caller can handle null result
        }

        return null;
    }

    /**
     * Convenience factory to load a user by email/username from the database.
     *
     * NOTE: This method returns identity/profile fields only. Password hash is intentionally not exposed here.
     */
    public static function findByEmail(string $email): ?self
    {
        try {
            $conn = Connection::getInstance();

            // Primary schema (zakaznik) - build full name in PHP instead of CONCAT(" ")
            $stmt = $conn->prepare('SELECT id_zakaznik AS id, email AS username, meno, priezvisko FROM zakaznik WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['id'])) {
                $first = trim((string)($row['meno'] ?? ''));
                $last = trim((string)($row['priezvisko'] ?? ''));
                $full = trim($first . ' ' . $last);
                return new self(id: (int)$row['id'], username: (string)($row['username'] ?? ''), name: $full);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * Helper to fetch password hash for authentication.
     *
     * Returns null if the user doesn't exist or if the schema doesn't provide a password hash.
     */
    public static function findPasswordHashByEmail(string $email): ?string
    {
        try {
            $conn = Connection::getInstance();

            // Primary schema (zakaznik)
            $stmt = $conn->prepare('SELECT heslo FROM zakaznik WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['heslo']) && is_string($row['heslo']) && $row['heslo'] !== '') {
                return $row['heslo'];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }
}
