<?php

namespace App\Models;

use Framework\DB\Connection;

/**
 * Cart model.
 *
 * Handles cart operations for both guests (session-based) and logged-in users (DB-based).
 */
class Cart extends BaseModel
{
    /**
     * Get cart items for rendering cart index.
     *
     * @return array<int, array<string,mixed>> rows with keys compatible with cart views (id_kniha, nazov, autor, obrazok, cena, mnozstvo)
     */
    public function getItems(?int $userId, array $sessionCart = []): array
    {
        if ($userId !== null) {
            $sql = "SELECT k.id_kniha, k.nazov, k.autor, k.obrazok, k.cena, kk.mnozstvo
                    FROM kosik ko
                    JOIN kosikKniha kk ON ko.id_kosik = kk.id_kosik
                    JOIN kniha k ON kk.id_kniha = k.id_kniha
                    WHERE ko.id_zakaznik = :uid";
            return $this->fetchAll($sql, [':uid' => $userId]);
        }

        // Guest flow: cart is stored as [bookId => qty]
        if (empty($sessionCart)) {
            return [];
        }

        $ids = array_keys($sessionCart);
        $ids = array_values(array_unique(array_filter($ids, static fn($v) => ctype_digit((string)$v) || is_int($v))));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id_kniha AS id, nazov, autor, obrazok, cena FROM kniha WHERE id_kniha IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $byId = [];
        foreach ($rows as $r) {
            $byId[(string)$r['id']] = $r;
        }

        // preserve input order
        $items = [];
        foreach ($ids as $id) {
            $k = (string)$id;
            if (!isset($byId[$k])) {
                continue;
            }

            $row = $byId[$k];
            $row['mnozstvo'] = (int)($sessionCart[$k] ?? $sessionCart[(int)$k] ?? 1);
            $row['id_kniha'] = (int)$row['id'];
            unset($row['id']);
            $items[] = $row;
        }

        return $items;
    }

    /**
     * Compute cart total from DB cart (logged in).
     */
    public function getDbCartTotal(int $userId): float
    {
        $cartId = $this->getOrCreateCartId($userId);
        if ($cartId === null) {
            return 0.0;
        }

        $stmt = $this->conn->prepare('SELECT SUM(k.cena * kk.mnozstvo) AS total
                                      FROM kosikKniha kk
                                      JOIN kniha k ON kk.id_kniha = k.id_kniha
                                      WHERE kk.id_kosik = :cid');
        $stmt->execute([':cid' => $cartId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0];
        return (float)($row['total'] ?? 0);
    }

    /**
     * Compute cart total from session cart (guest).
     */
    public function getSessionCartTotal(array $sessionCart): float
    {
        if (empty($sessionCart)) {
            return 0.0;
        }

        $ids = array_keys($sessionCart);
        if (empty($ids)) {
            return 0.0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("SELECT id_kniha, cena FROM kniha WHERE id_kniha IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $priceMap = [];
        foreach ($rows as $r) {
            $priceMap[(string)$r['id_kniha']] = (float)$r['cena'];
        }

        $total = 0.0;
        foreach ($sessionCart as $k => $q) {
            $total += ($priceMap[(string)$k] ?? 0.0) * (int)$q;
        }

        return $total;
    }

    /**
     * Add a book to guest cart (session) and return updated cart + total.
     *
     * @return array{cart: array<string,int>, total: float}
     */
    public function addToSessionCart(array $sessionCart, int $bookId, int $qty): array
    {
        $key = (string)$bookId;
        if (isset($sessionCart[$key])) {
            $sessionCart[$key] = (int)$sessionCart[$key] + $qty;
        } else {
            $sessionCart[$key] = $qty;
        }

        return ['cart' => $sessionCart, 'total' => $this->getSessionCartTotal($sessionCart)];
    }

    /**
     * Add a book to DB cart (logged in), incrementing quantity if present.
     * Returns updated cart total.
     */
    public function addToDbCart(int $userId, int $bookId, int $qty): float
    {
        $this->conn->beginTransaction();
        try {
            $cartId = $this->getOrCreateCartId($userId);
            if ($cartId === null) {
                throw new \RuntimeException('Cannot create cart');
            }

            $line = $this->conn->prepare('SELECT mnozstvo FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
            $line->execute([':cid' => $cartId, ':bid' => $bookId]);
            $existing = $line->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                $newQty = (int)$existing['mnozstvo'] + $qty;
                $upd = $this->conn->prepare('UPDATE kosikKniha SET mnozstvo = :q WHERE id_kosik = :cid AND id_kniha = :bid');
                $upd->execute([':q' => $newQty, ':cid' => $cartId, ':bid' => $bookId]);
            } else {
                $ins = $this->conn->prepare('INSERT INTO kosikKniha (id_kosik, id_kniha, mnozstvo) VALUES (:cid, :bid, :q)');
                $ins->execute([':cid' => $cartId, ':bid' => $bookId, ':q' => $qty]);
            }

            $total = $this->getDbCartTotal($userId);
            $this->conn->commit();
            return $total;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Update quantity by delta for a DB cart line; removes line when qty <= 0.
     */
    public function updateDbQty(int $userId, int $bookId, int $delta): void
    {
        $cartId = $this->getOrCreateCartId($userId);
        if ($cartId === null) {
            return;
        }

        $stmt = $this->conn->prepare('SELECT mnozstvo FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
        $stmt->execute([':cid' => $cartId, ':bid' => $bookId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }

        $newQty = (int)$row['mnozstvo'] + $delta;
        if ($newQty <= 0) {
            $del = $this->conn->prepare('DELETE FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
            $del->execute([':cid' => $cartId, ':bid' => $bookId]);
        } else {
            $upd = $this->conn->prepare('UPDATE kosikKniha SET mnozstvo = :q WHERE id_kosik = :cid AND id_kniha = :bid');
            $upd->execute([':q' => $newQty, ':cid' => $cartId, ':bid' => $bookId]);
        }
    }

    /**
     * Remove a DB cart line.
     */
    public function removeFromDbCart(int $userId, int $bookId): void
    {
        $cartId = $this->getOrCreateCartId($userId);
        if ($cartId === null) {
            return;
        }

        $del = $this->conn->prepare('DELETE FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
        $del->execute([':cid' => $cartId, ':bid' => $bookId]);
    }

    /**
     * Get checkout summary for logged-in user.
     *
     * @return array{items: array<int,array<string,mixed>>, total: float, totalQty: int}
     */
    public function getCheckoutSummary(int $userId): array
    {
        $items = $this->getItems($userId, []);
        $total = 0.0;
        $totalQty = 0;

        foreach ($items as $it) {
            $qty = (int)($it['mnozstvo'] ?? 1);
            $price = (float)($it['cena'] ?? 0);
            $total += $price * $qty;
            $totalQty += $qty;
        }

        return ['items' => $items, 'total' => $total, 'totalQty' => $totalQty];
    }

    /**
     * Place order from DB cart (logged-in only). Clears cart lines on success.
     *
     * @return int created order id
     */
    public function placeOrder(int $userId): int
    {
        $cartId = $this->getOrCreateCartId($userId);
        if ($cartId === null) {
            throw new \RuntimeException('Cart not found');
        }

        $stmt = $this->conn->prepare('SELECT kk.id_kniha, kk.mnozstvo, k.cena FROM kosikKniha kk JOIN kniha k ON kk.id_kniha = k.id_kniha WHERE kk.id_kosik = :cid');
        $stmt->execute([':cid' => $cartId]);
        $lines = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if (empty($lines)) {
            throw new \RuntimeException('Cart is empty');
        }

        $total = 0.0;
        $totalQty = 0;
        foreach ($lines as $l) {
            $qty = (int)($l['mnozstvo'] ?? 1);
            $price = (float)($l['cena'] ?? 0);
            $total += $price * $qty;
            $totalQty += $qty;
        }

        $this->conn->beginTransaction();
        try {
            $ins = $this->conn->prepare('INSERT INTO objednavka (id_zakaznik, datum_vytvorenia, stav, mnozstvo, celkova_cena) VALUES (:uid, :dt, :stav, :mnozstvo, :celkova)');
            $now = date('Y-m-d H:i:s');
            $ins->execute([
                ':uid' => $userId,
                ':dt' => $now,
                ':stav' => 'nova',
                ':mnozstvo' => $totalQty,
                ':celkova' => $total,
            ]);

            $orderId = (int)$this->conn->lastInsertId();

            $pstmt = $this->conn->prepare('INSERT INTO polozkaObjednavky (id_kniha, id_objednavka) VALUES (:kid, :oid)');
            foreach ($lines as $l) {
                $pstmt->execute([':kid' => $l['id_kniha'], ':oid' => $orderId]);
            }

            $del = $this->conn->prepare('DELETE kk FROM kosikKniha kk JOIN kosik k ON kk.id_kosik = k.id_kosik WHERE k.id_zakaznik = :uid');
            $del->execute([':uid' => $userId]);

            $this->conn->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Helper: get existing cart id for user or create a new one.
     */
    public function getOrCreateCartId(int $userId): ?int
    {
        $stmt = $this->conn->prepare('SELECT id_kosik FROM kosik WHERE id_zakaznik = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && isset($row['id_kosik'])) {
            return (int)$row['id_kosik'];
        }

        $ins = $this->conn->prepare('INSERT INTO kosik (id_zakaznik) VALUES (:uid)');
        if ($ins->execute([':uid' => $userId])) {
            return (int)$this->conn->lastInsertId();
        }

        return null;
    }
}

