<?php
namespace App\Models;

/**
 * Book model.
 *
 * Encapsulates all DB access for books.
 */
class Book extends BaseModel
{
    /**
     * Fetch paginated books list (optionally filtered by a search query across title/author/series).
     *
     * @return array{books: array<int, array<string, mixed>>, page: int, totalPages: int, perPage: int, totalBooks: int}
     */
    public function getPaginatedList(?string $query, int $page = 1, int $perPage = 21): array
    {
        $q = trim((string)($query ?? ''));
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        if ($q === '') {
            $countStmt = $this->conn->prepare('SELECT COUNT(*) FROM kniha');
            $countStmt->execute();
            $totalBooks = (int)$countStmt->fetchColumn();

            $totalPages = (int)max(1, (int)ceil($totalBooks / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }
            $offset = ($page - 1) * $perPage;

            $sql = "SELECT b.id_kniha AS id, b.nazov, b.autor, b.obrazok, b.popis, b.cena, b.series_id, s.name AS series_name
                    FROM kniha b
                    LEFT JOIN serie s ON b.series_id = s.id
                    ORDER BY b.nazov
                    LIMIT :limit OFFSET :offset";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
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

        $like = '%' . $q . '%';

        $countSql = "SELECT COUNT(*)
                     FROM kniha b
                     LEFT JOIN serie s ON b.series_id = s.id
                     WHERE b.nazov LIKE :q OR b.autor LIKE :q OR s.name LIKE :q";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->execute([':q' => $like]);
        $totalBooks = (int)$countStmt->fetchColumn();

        $totalPages = (int)max(1, (int)ceil($totalBooks / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT b.id_kniha AS id, b.nazov, b.autor, b.obrazok, b.popis, b.cena, b.series_id, s.name AS series_name
                FROM kniha b
                LEFT JOIN serie s ON b.series_id = s.id
                WHERE b.nazov LIKE :q OR b.autor LIKE :q OR s.name LIKE :q
                ORDER BY b.nazov
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':q', $like, \PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
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

    /**
     * Load a single book including series name.
     */
    public function getDetailById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT b.id_kniha AS id, b.nazov, b.autor, b.obrazok, b.popis, b.cena, b.series_id, s.name AS series_name, b.ISBN FROM kniha b LEFT JOIN serie s ON b.series_id = s.id WHERE b.id_kniha = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Fetch list of series used by admin forms.
     */
    public function getAllSeries(): array
    {
        $stmt = $this->conn->prepare('SELECT id, name FROM serie ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Resolve an arbitrary identifier used in forms/sessions to a numeric DB id_kniha.
     *
     * In this project some places store book id as numeric id_kniha, but in a few UI flows it can be ISBN or title.
     *
     * @return int|null Numeric id_kniha when resolved, otherwise null.
     */
    public function resolveId(mixed $idOrIsbnOrTitle): ?int
    {
        if ($idOrIsbnOrTitle === null) {
            return null;
        }

        $v = trim((string)$idOrIsbnOrTitle);
        if ($v === '') {
            return null;
        }

        if (ctype_digit($v)) {
            return (int)$v;
        }

        // Try ISBN
        $row = $this->fetchOne('SELECT id_kniha FROM kniha WHERE ISBN = :v LIMIT 1', [':v' => $v]);
        if ($row && isset($row['id_kniha'])) {
            return (int)$row['id_kniha'];
        }

        // Finally fallback to title match (exact)
        $row = $this->fetchOne('SELECT id_kniha FROM kniha WHERE nazov = :v LIMIT 1', [':v' => $v]);
        if ($row && isset($row['id_kniha'])) {
            return (int)$row['id_kniha'];
        }

        return null;
    }

    /**
     * Fetch multiple books by their numeric ids.
     *
     * @param array<int,int> $ids
     * @return array<int, array<string,mixed>>
     */
    public function getByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, static fn($x) => $x > 0));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT b.id_kniha AS id, b.nazov, b.autor, b.obrazok, b.popis, b.cena, b.series_id, s.name AS series_name
                FROM kniha b
                LEFT JOIN serie s ON b.series_id = s.id
                WHERE b.id_kniha IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}

