<?php

namespace App\Models;

use Framework\DB\Connection;

class Book
{
    // Return all books with optional series name
    public static function allWithSeries(): array
    {
        $conn = Connection::getInstance();
        // Sort alphabetically by nazov !modify later so user may choose!
        $stmt = $conn->prepare("SELECT b.id_kniha AS id, b.nazov, b.autor, b.cena, b.obrazok, b.series_id, s.name AS series_name, b.popis FROM kniha b LEFT JOIN serie s ON b.series_id = s.id ORDER BY b.nazov");
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    // Create a new book record. $data must include keys: nazov, autor, series_id, obrazok, popis, cena
    public static function create(array $data): bool
    {
        $conn = Connection::getInstance();
        $sql = "INSERT INTO kniha (nazov, autor, series_id, obrazok, popis, cena) VALUES (:nazov, :autor, :series_id, :obrazok, :popis, :cena)";
        $stmt = $conn->prepare($sql);
        return (bool)$stmt->execute([
            ':nazov' => $data['nazov'] ?? null,
            ':autor' => $data['autor'] ?? null,
            ':series_id' => $data['series_id'] ?? null,
            ':obrazok' => $data['obrazok'] ?? null,
            ':popis' => $data['popis'] ?? null,
            ':cena' => $data['cena'] ?? null,
        ]);
    }

    // Update book by id using provided fields (associative column => value). Returns affected rows (int) or false on error.
    public static function update(int $id, array $fields)
    {
        if (empty($fields)) return 0;
        $conn = Connection::getInstance();
        $setParts = [];
        $params = [':id' => $id];
        foreach ($fields as $col => $val) {
            // allow null to set NULL
            if ($val === null) {
                $setParts[] = "$col = NULL";
            } else {
                $setParts[] = "$col = :$col";
                $params[":$col"] = $val;
            }
        }
        $sql = 'UPDATE kniha SET ' . implode(', ', $setParts) . ' WHERE id_kniha = :id';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // Count how many order items reference this book
    public static function countInOrders(int $id): int
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM polozkaObjednavky WHERE id_kniha = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }

    // Delete book and cleanup cart/wishlist references in a transaction. Returns number of deleted book rows.
    public static function deleteWithCleanup(int $id): int
    {
        $conn = Connection::getInstance();
        $conn->beginTransaction();
        try {
            $d1 = $conn->prepare('DELETE FROM kosikKniha WHERE id_kniha = :id');
            $d1->execute([':id' => $id]);

            $d2 = $conn->prepare('DELETE FROM wishlistKniha WHERE id_kniha = :id');
            $d2->execute([':id' => $id]);

            $d3 = $conn->prepare('DELETE FROM kniha WHERE id_kniha = :id');
            $d3->execute([':id' => $id]);
            $affected = $d3->rowCount();

            $conn->commit();
            return (int)$affected;
        } catch (\Exception $e) {
            try { $conn->rollBack(); } catch (\Exception $_) {}
            throw $e;
        }
    }

    // Instance methods expected by controllers (old code used instance model)
    public function getPaginatedList(?string $q, int $page, int $perPage, bool $authorOnly = false): array
    {
        $conn = Connection::getInstance();

        $params = [];
        $where = '';
        if ($q !== null && $q !== '') {
            $params[':q'] = '%' . $q . '%';
            if ($authorOnly) {
                // When explicitly searching by author only
                $where = 'WHERE b.autor LIKE :q';
            } else {
                // Search across title, author and series name
                $where = 'WHERE (b.nazov LIKE :q OR b.autor LIKE :q OR s.name LIKE :q)';
            }
        }

        // Count total matching rows
        $countSql = "SELECT COUNT(*) AS cnt FROM kniha b LEFT JOIN serie s ON b.series_id = s.id " . $where;
        $cstmt = $conn->prepare($countSql);
        $cstmt->execute($params);
        $crow = $cstmt->fetch(\PDO::FETCH_ASSOC);
        $totalBooks = isset($crow['cnt']) ? (int)$crow['cnt'] : 0;

        $perPage = max(1, (int)$perPage);
        $page = max(1, (int)$page);
        $totalPages = (int)max(1, ceil($totalBooks / $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT b.id_kniha AS id, b.nazov, b.autor, b.cena, b.obrazok, b.series_id, s.name AS series_name, b.popis
                FROM kniha b
                LEFT JOIN serie s ON b.series_id = s.id
                " . $where . "
                -- Order alphabetically by title
                ORDER BY b.nazov
                LIMIT :limit OFFSET :offset";

        // Add limit/offset as params (use ints)
        $params[':limit'] = $perPage;
        $params[':offset'] = $offset;

        $stmt = $conn->prepare($sql);
        // Bind parameters explicitly for robustness (especially limit/offset as integers)
        if (isset($params[':q'])) {
            $stmt->bindValue(':q', $params[':q'], \PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', (int)$params[':limit'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$params[':offset'], \PDO::PARAM_INT);

        $stmt->execute();
        $books = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return [
            'books' => $books,
            'page' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalBooks' => $totalBooks,
        ];
    }

    public function getAllSeries(): array
    {
        // Delegate to Series model
        return Series::allWithCounts();
    }

    public function getDetailById(int $id): ?array
    {
        $conn = Connection::getInstance();
        $stmt = $conn->prepare('SELECT b.id_kniha AS id, b.nazov, b.autor, b.cena, b.obrazok, b.series_id, s.name AS series_name, b.popis FROM kniha b LEFT JOIN serie s ON b.series_id = s.id WHERE b.id_kniha = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ------------------------------------------------------------------
    // Compatibility helpers expected by older controllers/models
    // ------------------------------------------------------------------

    /**
     * Resolve a book identifier which may be numeric id, ISBN or title into a numeric id.
     * Returns null if no matching book is found.
     *
     * @param mixed $idOrIsbnOrTitle
     * @return int|null
     */
    public function resolveId(mixed $idOrIsbnOrTitle): ?int
    {
        $conn = Connection::getInstance();

        if ($idOrIsbnOrTitle === null) return null;

        // If it's an integer or purely digits, treat as id
        if (is_int($idOrIsbnOrTitle) || (is_string($idOrIsbnOrTitle) && ctype_digit($idOrIsbnOrTitle))) {
            $id = (int)$idOrIsbnOrTitle;
            $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE id_kniha = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['id_kniha'])) return (int)$row['id_kniha'];
            return null;
        }

        $value = (string)$idOrIsbnOrTitle;

        // Try exact ISBN match if column exists
        try {
            $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE isbn = :v LIMIT 1');
            $stmt->execute([':v' => $value]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['id_kniha'])) return (int)$row['id_kniha'];
        } catch (\Throwable $e) {
            // ignore if column doesn't exist
        }

        // Fallback: try matching by exact title first, then LIKE
        $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE nazov = :v LIMIT 1');
        $stmt->execute([':v' => $value]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['id_kniha'])) return (int)$row['id_kniha'];

        $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE nazov LIKE :v LIMIT 1');
        $stmt->execute([':v' => '%' . $value . '%']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['id_kniha'])) return (int)$row['id_kniha'];

        return null;
    }

    /**
     * Fetch multiple books by numeric ids. Returns array of associative rows with `id` key.
     *
     * @param array<int> $ids
     * @return array<int, array<string,mixed>>
     */
    public function getByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn($v) => ctype_digit((string)$v) || is_int($v))));
        if (empty($ids)) return [];

        $conn = Connection::getInstance();

        // Build placeholders
        $placeholders = [];
        $params = [];
        foreach ($ids as $i => $val) {
            $ph = ':id' . $i;
            $placeholders[] = $ph;
            $params[$ph] = (int)$val;
        }

        $sql = 'SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena FROM kniha WHERE id_kniha IN (' . implode(',', $placeholders) . ')';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return $rows;
    }

    /**
     * Fetch a randomized list of books for carousels or featured sections.
     * Returns associative rows with keys matching the database columns used in views.
     *
     * @param int $limit
     * @return array<int, array<string,mixed>>
     */
    public function getRandomBooks(int $limit = 12): array
    {
        $conn = Connection::getInstance();
        $limit = max(1, (int)$limit);

        // Using explicit int interpolation for LIMIT to avoid driver quirks with bound params
        $sql = "SELECT id_kniha, nazov, autor, cena, obrazok FROM kniha ORDER BY RAND() LIMIT " . $limit;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }

}
