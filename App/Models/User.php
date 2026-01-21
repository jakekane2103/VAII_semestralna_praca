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
     * Uses email as username where necessary
     */
    public static function createCustomer(array $data): bool
    {
        $email = self::normalizeEmail((string)($data['email'] ?? ''));
        $meno = (string)($data['meno'] ?? '');
        $priezvisko = (string)($data['priezvisko'] ?? '');
        $passwordHash = (string)($data['passwordHash'] ?? '');

        $conn = Connection::getInstance();
        $stmt = $conn->prepare('INSERT INTO zakaznik (meno, priezvisko, email, heslo, datum_registracie) VALUES (:meno, :priezvisko, :email, :heslo, NOW())');
        return $stmt->execute([
            ':meno' => $meno,
            ':priezvisko' => $priezvisko,
            ':email' => $email,
            ':heslo' => $passwordHash
        ]);
    }

    // Helper: fetch full profile row used by account edit view.
    public static function getProfile(int $id): ?array
    {
        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('SELECT id_zakaznik, meno, priezvisko, email, krajina, mesto, psc, ulica, cislo, datum_registracie FROM zakaznik WHERE id_zakaznik = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    // Check whether an email is taken by another user. If $excludeId is provided, exclude that user.
    public static function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        $email = self::normalizeEmail($email);
        $conn = Connection::getInstance();
        if ($excludeId === null) {
            $stmt = $conn->prepare('SELECT 1 FROM zakaznik WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
        } else {
            $stmt = $conn->prepare('SELECT 1 FROM zakaznik WHERE email = :email AND id_zakaznik != :id LIMIT 1');
            $stmt->execute([':email' => $email, ':id' => $excludeId]);
        }
        return (bool)$stmt->fetchColumn();
    }

    // Validate profile input; return null when valid or an error message string when invalid.
    public static function validateProfile(array $data, bool $passwordRequired = false): ?string
    {
        $meno = trim((string)($data['meno'] ?? ''));
        $priez = trim((string)($data['priezvisko'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));

        if ($meno === '' || $priez === '' || $email === '') {
            return 'Vyplňte povinné polia.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Neplatný formát e-mailu.';
        }

        $password = (string)($data['heslo'] ?? '');
        $passwordConfirm = (string)($data['heslo_confirm'] ?? '');

        if ($passwordRequired || $password !== '') {
            if ($password !== $passwordConfirm) {
                return 'Heslá sa nezhodujú.';
            }
            if (strlen($password) < 6) {
                return 'Heslo musí mať aspoň 6 znakov.';
            }
        }

        return null;
    }

    // Update profile record. $data may contain keys: meno, priezvisko, email, krajina, mesto, psc, ulica, cislo, heslo
    public static function updateProfile(int $id, array $data): bool
    {
        $conn = Connection::getInstance();

        $fields = [
            'meno' => $data['meno'] ?? null,
            'priezvisko' => $data['priezvisko'] ?? null,
            'email' => $data['email'] ?? null,
            'krajina' => $data['krajina'] ?? null,
            'mesto' => $data['mesto'] ?? null,
            'psc' => $data['psc'] ?? null,
            'ulica' => $data['ulica'] ?? null,
            'cislo' => $data['cislo'] ?? null,
        ];

        if (!empty($data['heslo'])) {
            $fields['heslo'] = password_hash((string)$data['heslo'], PASSWORD_DEFAULT);
        }

        $setParts = [];
        $params = [':id' => $id];
        foreach ($fields as $col => $val) {
            $setParts[] = "$col = :$col";
            $params[":$col"] = $val;
        }

        $sql = 'UPDATE zakaznik SET ' . implode(', ', $setParts) . ' WHERE id_zakaznik = :id';
        $stmt = $conn->prepare($sql);
        return (bool)$stmt->execute($params);
    }
}
