<?php

namespace App\Models;

/**
 * Wishlist model.
 *
 * Encapsulates wishlist DB access. Session wishlist order is still handled by controller/session,
 * but this model provides helpers to keep DB in sync when the user is logged in.
 */
class Wishlist extends BaseModel
{
    /**
     * Get or create wishlist id for a customer.
     */
    public function getOrCreateWishlistId(int $userId, string $defaultTitle = 'Moje wishlist'): ?int
    {
        $row = $this->fetchOne('SELECT id_wishlist FROM wishlist WHERE id_zakaznik = :uid LIMIT 1', [':uid' => $userId]);
        if ($row && isset($row['id_wishlist'])) {
            return (int)$row['id_wishlist'];
        }

        $ins = $this->conn->prepare('INSERT INTO wishlist (id_zakaznik, title, datum_pridania) VALUES (:uid, :title, NOW())');
        if ($ins->execute([':uid' => $userId, ':title' => $defaultTitle])) {
            return (int)$this->conn->lastInsertId();
        }

        return null;
    }

    /**
     * Resolve wishlist/book id that may come as numeric id, ISBN or title.
     */
    public function resolveBookId(mixed $idOrIsbnOrTitle): ?int
    {
        $bookModel = new Book();
        return $bookModel->resolveId($idOrIsbnOrTitle);
    }

    /**
     * Fetch wishlist items for display, preserving given wishlist order.
     *
     * @param array<int, string|int> $wishlistIds
     * @return array<int, array<string,mixed>>
     */
    public function getItemsForSessionWishlist(array $wishlistIds): array
    {
        if (empty($wishlistIds)) {
            return [];
        }

        $ids = array_values(array_unique(array_filter($wishlistIds, static fn($v) => ctype_digit((string)$v) || is_int($v))));
        $ids = array_map('intval', $ids);
        if (empty($ids)) {
            return [];
        }

        $bookModel = new Book();
        $rows = $bookModel->getByIds($ids);

        $byId = [];
        foreach ($rows as $r) {
            if (isset($r['id'])) {
                $byId[(string)$r['id']] = $r;
            }
        }

        // preserve wishlist order
        $items = [];
        foreach ($ids as $id) {
            $k = (string)$id;
            if (isset($byId[$k])) {
                $items[] = $byId[$k];
            }
        }

        return $items;
    }

    /**
     * Persist a wishlist add to DB for logged-in user.
     */
    public function addToDb(int $userId, int $bookId): void
    {
        $wid = $this->getOrCreateWishlistId($userId);
        if ($wid === null) {
            return;
        }

        // Avoid blowing up on duplicates: app may not have unique constraint
        try {
            $stmt = $this->conn->prepare('INSERT INTO wishlistKniha (id_wishlist, id_kniha) VALUES (:wid, :kid)');
            $stmt->execute([':wid' => $wid, ':kid' => $bookId]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Persist wishlist remove to DB for logged-in user.
     */
    public function removeFromDb(int $userId, int $bookId): void
    {
        $wid = $this->getOrCreateWishlistId($userId);
        if ($wid === null) {
            return;
        }

        try {
            $del = $this->conn->prepare('DELETE FROM wishlistKniha WHERE id_wishlist = :wid AND id_kniha = :kid');
            $del->execute([':wid' => $wid, ':kid' => $bookId]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Attempt to persist wishlist item order to DB using a "pozicia" column if it exists.
     *
     * @param array<int, string|int> $orderedBookIds
     */
    public function tryUpdatePositions(int $userId, array $orderedBookIds): void
    {
        $wid = $this->getOrCreateWishlistId($userId);
        if ($wid === null) {
            return;
        }

        $posCol = 'pozicia';
        $pos = 1;

        foreach ($orderedBookIds as $kid) {
            $kid = (string)$kid;
            if (!ctype_digit($kid)) {
                continue;
            }

            try {
                $sql = sprintf('UPDATE wishlistKniha SET %s = :p WHERE id_wishlist = :wid AND id_kniha = :kid', $posCol);
                $upd = $this->conn->prepare($sql);
                $upd->execute([':p' => $pos, ':wid' => $wid, ':kid' => (int)$kid]);
            } catch (\Throwable $e) {
                // Column probably doesn't exist; stop trying.
                break;
            }

            $pos++;
        }
    }

    /**
     * Fetch book record for JSON response.
     */
    public function getBookPreview(int $bookId): ?array
    {
        return $this->fetchOne('SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena FROM kniha WHERE id_kniha = :id LIMIT 1', [':id' => $bookId]);
    }
}

