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

    /**
     * Normalize email for case-insensitive comparisons/storing.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Returns whether a customer with given email exists.
     */
    public static function emailExists(string $email): bool
    {
        $email = self::normalizeEmail($email);

        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT id_zakaznik FROM zakaznik WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return (bool)$stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new customer record in `zakaznik`.
     *
     * Expected keys in $data: meno, priezvisko, email, passwordHash.
     * Uses email as username (pouzivatelske_meno) to match current controller behavior.
     */
    public static function createCustomer(array $data): bool
    {
        $email = self::normalizeEmail((string)($data['email'] ?? ''));
        $meno = (string)($data['meno'] ?? '');
        $priezvisko = (string)($data['priezvisko'] ?? '');
        $passwordHash = (string)($data['passwordHash'] ?? '');

        $conn = Connection::getInstance();
        $stmt = $conn->prepare('INSERT INTO zakaznik (pouzivatelske_meno, meno, priezvisko, email, heslo, datum_registracie) VALUES (:uname, :meno, :priezvisko, :email, :heslo, NOW())');
        return $stmt->execute([
            ':uname' => $email,
            ':meno' => $meno,
            ':priezvisko' => $priezvisko,
            ':email' => $email,
            ':heslo' => $passwordHash
        ]);
    }
}
