<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\DB\Connection;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class HomeController
 * Handles actions related to the home page and other public actions.
 *
 * This controller includes actions that are accessible to all users, including a default landing page and a contact
 * page. It provides a mechanism for authorizing actions based on user permissions.
 *
 * @package App\Controllers
 */
class CartController extends BaseController
{
    /**
     * Authorizes controller actions based on the specified action name.
     *
     * In this implementation, all actions are authorized unconditionally.
     *
     * @param string $action The action name to authorize.
     * @return bool Returns true, allowing all actions.
     */
    public function authorize(Request $request, string $action): bool
    {
        // Allow guest access to viewing and adding to cart (session-based). Other actions require login.
        $a = strtolower((string)$action);
        if (in_array($a, ['index', 'add', 'checkout'], true)) {
            return true;
        }

        // Require logged-in user for all other cart actions
        return $this->app->getAuth() !== null && $this->app->getAuth()->isLogged();
    }

    /**
     * Displays the default home page.
     *
     * This action serves the main HTML view of the home page.
     *
     * @return Response The response object containing the rendered HTML for the home page.
     */
    public function index(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        $items = [];

        if ($user && $user->getId() !== null) {
            $conn = Connection::getInstance();

            $sql = "SELECT k.id_kniha, k.nazov, k.autor, k.obrazok, k.cena, kk.mnozstvo
                    FROM kosik ko
                    JOIN kosikKniha kk ON ko.id_kosik = kk.id_kosik
                    JOIN kniha k ON kk.id_kniha = k.id_kniha
                    WHERE ko.id_zakaznik = :uid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':uid' => $user->getId()]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } else {
            // Guest: load cart from session
            $session = $this->app->getSession();
            $cart = $session->get('cart', []);
            if (!empty($cart)) {
                $conn = Connection::getInstance();
                // sanitize ids and preserve order
                $ids = array_values(array_unique(array_filter(array_keys($cart), function ($v) { return ctype_digit((string)$v) || is_int($v); })));
                if (!empty($ids)) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql = "SELECT id_kniha AS id, nazov, autor, obrazok, cena FROM kniha WHERE id_kniha IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($ids);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    $byId = [];
                    foreach ($rows as $r) {
                        $byId[(string)$r['id']] = $r;
                    }
                    foreach ($ids as $id) {
                        $key = (string)$id;
                        if (isset($byId[$key])) {
                            $row = $byId[$key];
                            $row['mnozstvo'] = (int)($cart[$key] ?? $cart[(int)$key] ?? 1);
                            // normalize id key name to match logged-in rows
                            $row['id_kniha'] = $row['id'];
                            $items[] = $row;
                        }
                    }
                }
            }
        }

        return $this->html(['items' => $items]);
    }

    /**
     * Add a book to the current user's cart (or increase quantity).
     */
    public function add(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();

        $bookId = (int)($request->value('id') ?? 0);
        $qty = (int)($request->value('qty') ?? 1);
        if ($bookId <= 0 || $qty <= 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        // Protect against duplicate rapid adds from the UI (e.g., double-click or racing requests)
        $session = $this->app->getSession();
        $last = $session->get('last_add', null);
        $now = time();
        $dedupeWindow = 2; // seconds
        $isDuplicate = false;
        if (is_array($last) && isset($last['id']) && isset($last['ts'])) {
            if ((int)$last['id'] === $bookId && ($now - (int)$last['ts']) < $dedupeWindow) {
                $isDuplicate = true;
            }
        }

        $conn = Connection::getInstance();

        // If user not logged in => store in session cart (guest flow)
        if (!$user || $user->getId() === null) {
            // If duplicate, skip mutation but compute cart total for response
            if ($isDuplicate) {
                // compute current cart total from session + DB prices
                $cart = $session->get('cart', []);
                $cartTotal = 0.0;
                if (!empty($cart)) {
                    $ids = array_keys($cart);
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $conn->prepare("SELECT id_kniha, cena FROM kniha WHERE id_kniha IN ($placeholders)");
                    $stmt->execute($ids);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                    $priceMap = [];
                    foreach ($rows as $r) { $priceMap[(string)$r['id_kniha']] = (float)$r['cena']; }
                    foreach ($cart as $k => $q) {
                        $cartTotal += ($priceMap[(string)$k] ?? 0.0) * (int)$q;
                    }
                }

                if ($request->isAjax()) {
                    return $this->json(['success' => true, 'cartTotal' => $cartTotal, 'note' => 'duplicate_skipped']);
                }
                return $this->redirect($this->url('Cart.index'));
            }

            // Not duplicate: update session cart
            $cart = $session->get('cart', []);
            $key = (string)$bookId;
            if (isset($cart[$key])) {
                $cart[$key] = (int)$cart[$key] + $qty;
            } else {
                $cart[$key] = $qty;
            }
            $session->set('cart', $cart);

            // compute cart total to return
            $cartTotal = 0.0;
            if (!empty($cart)) {
                $ids = array_keys($cart);
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $conn->prepare("SELECT id_kniha, cena FROM kniha WHERE id_kniha IN ($placeholders)");
                $stmt->execute($ids);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                $priceMap = [];
                foreach ($rows as $r) { $priceMap[(string)$r['id_kniha']] = (float)$r['cena']; }
                foreach ($cart as $k => $q) {
                    $cartTotal += ($priceMap[(string)$k] ?? 0.0) * (int)$q;
                }
            }

            // record last_add
            $session->set('last_add', ['id' => $bookId, 'ts' => $now]);

            if ($request->isAjax()) {
                return $this->json(['success' => true, 'cartTotal' => $cartTotal]);
            }

            $referer = $request->server('HTTP_REFERER') ?? $this->url('Cart.index');
            return $this->redirect($referer);
        }

        // --- existing logged-in behavior follows ---
        // If duplicate, skip DB mutation but still compute cart total to return consistent response
        if ($isDuplicate) {
            // compute current cart total
            $cartId = $this->getOrCreateCartId($conn, $user->getId());
            $totalStmt = $conn->prepare('SELECT SUM(k.cena * kk.mnozstvo) AS total
                                         FROM kosikKniha kk
                                         JOIN kniha k ON kk.id_kniha = k.id_kniha
                                         WHERE kk.id_kosik = :cid');
            $totalStmt->execute([':cid' => $cartId]);
            $totalRow = $totalStmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0];
            $cartTotal = (float)($totalRow['total'] ?? 0);

            if ($request->isAjax()) {
                return $this->json(['success' => true, 'cartTotal' => $cartTotal, 'note' => 'duplicate_skipped']);
            }

            return $this->redirect($this->url('Cart.index'));
        }

        // Not duplicate — proceed normally and record last_add
        $conn->beginTransaction();
        try {
            $cartId = $this->getOrCreateCartId($conn, $user->getId());
            if ($cartId === null) {
                error_log('[Cart.add] Failed to get/create cart for customerId=' . $user->getId());
                $conn->rollBack();
                return $this->redirect($this->url('Cart.index'));
            }

            $line = $conn->prepare('SELECT mnozstvo FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
            $line->execute([':cid' => $cartId, ':bid' => $bookId]);
            $existing = $line->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                $newQty = (int)$existing['mnozstvo'] + $qty;
                $upd = $conn->prepare('UPDATE kosikKniha SET mnozstvo = :q WHERE id_kosik = :cid AND id_kniha = :bid');
                $upd->execute([':q' => $newQty, ':cid' => $cartId, ':bid' => $bookId]);
                error_log('[Cart.add] Updated existing line cartId=' . $cartId . ' bookId=' . $bookId . ' qty=' . $newQty);
            } else {
                $ins = $conn->prepare('INSERT INTO kosikKniha (id_kosik, id_kniha, mnozstvo) VALUES (:cid, :bid, :q)');
                $ins->execute([':cid' => $cartId, ':bid' => $bookId, ':q' => $qty]);
                error_log('[Cart.add] Inserted new line cartId=' . $cartId . ' bookId=' . $bookId . ' qty=' . $qty);
            }

            // Compute full cart total after change
            $totalStmt = $conn->prepare('SELECT SUM(k.cena * kk.mnozstvo) AS total
                                         FROM kosikKniha kk
                                         JOIN kniha k ON kk.id_kniha = k.id_kniha
                                         WHERE kk.id_kosik = :cid');
            $totalStmt->execute([':cid' => $cartId]);
            $totalRow = $totalStmt->fetch(\PDO::FETCH_ASSOC) ?: ['total' => 0];
            $cartTotal = (float)($totalRow['total'] ?? 0);

            $conn->commit();

            // record last_add
            $session->set('last_add', ['id' => $bookId, 'ts' => $now]);

        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('[Cart.add] Exception: ' . $e->getMessage());
            return $this->redirect($this->url('Cart.index'));
        }

        // If the request was AJAX (from JS fetch), return a tiny JSON payload including full cart total
        if ($request->isAjax()) {
            return $this->json([
                'success' => true,
                'cartTotal' => $cartTotal,
            ]);
        }

        return $this->redirect($this->url('Cart.index'));
    }

    /**
     * Increase or decrease quantity of a book in the cart.
     */
    public function update(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        if (!$user || $user->getId() === null) {
            return $this->redirect($this->url('home.index', ['openLogin' => 1]));
        }

        $bookId = (int)($request->value('id') ?? 0);
        $delta  = (int)($request->value('delta') ?? 0);
        if ($bookId <= 0 || $delta === 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        $conn = Connection::getInstance();
        $cartId = $this->getOrCreateCartId($conn, $user->getId());
        if ($cartId === null) {
            return $this->redirect($this->url('Cart.index'));
        }

        $stmt = $conn->prepare('SELECT mnozstvo FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
        $stmt->execute([':cid' => $cartId, ':bid' => $bookId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->redirect($this->url('Cart.index'));
        }

        $newQty = (int)$row['mnozstvo'] + $delta;
        if ($newQty <= 0) {
            $del = $conn->prepare('DELETE FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
            $del->execute([':cid' => $cartId, ':bid' => $bookId]);
        } else {
            $upd = $conn->prepare('UPDATE kosikKniha SET mnozstvo = :q WHERE id_kosik = :cid AND id_kniha = :bid');
            $upd->execute([':q' => $newQty, ':cid' => $cartId, ':bid' => $bookId]);
        }

        return $this->redirect($this->url('Cart.index'));
    }

    /**
     * Remove a given book from cart completely.
     */
    public function remove(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        if (!$user || $user->getId() === null) {
            return $this->redirect($this->url('home.index', ['openLogin' => 1]));
        }

        $bookId = (int)($request->value('id') ?? 0);
        if ($bookId <= 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        $conn = Connection::getInstance();
        $cartId = $this->getOrCreateCartId($conn, $user->getId());
        if ($cartId !== null) {
            $del = $conn->prepare('DELETE FROM kosikKniha WHERE id_kosik = :cid AND id_kniha = :bid');
            $del->execute([':cid' => $cartId, ':bid' => $bookId]);
        }

        return $this->redirect($this->url('Cart.index'));
    }

    /**
     * Simple checkout page rendering (GET)
     */
    public function checkout(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        $items = [];
        $total = 0.0;
        $totalQty = 0;

        if ($user && $user->getId() !== null) {
            $conn = Connection::getInstance();
            $sql = "SELECT k.id_kniha, k.nazov, k.autor, k.obrazok, k.cena, kk.mnozstvo
                    FROM kosik ko
                    JOIN kosikKniha kk ON ko.id_kosik = kk.id_kosik
                    JOIN kniha k ON kk.id_kniha = k.id_kniha
                    WHERE ko.id_zakaznik = :uid";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':uid' => $user->getId()]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            foreach ($items as $it) {
                $qty = (int)($it['mnozstvo'] ?? 1);
                $price = (float)($it['cena'] ?? 0);
                $total += $price * $qty;
                $totalQty += $qty;
            }
        }

        return $this->html(['items' => $items, 'total' => $total, 'totalQty' => $totalQty]);
    }

    /**
     * Place order (POST) - simple flow: create objednavka and polozkaObjednavky, then clear the cart lines.
     */
    public function placeOrder(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        if (!$user || $user->getId() === null) {
            return $this->redirect($this->url('home.index', ['openLogin' => 1]));
        }

        $conn = Connection::getInstance();
        $cartId = $this->getOrCreateCartId($conn, $user->getId());
        if ($cartId === null) {
            return $this->redirect($this->url('Cart.index'));
        }

        // Fetch current cart lines
        $stmt = $conn->prepare('SELECT kk.id_kniha, kk.mnozstvo, k.cena FROM kosikKniha kk JOIN kniha k ON kk.id_kniha = k.id_kniha WHERE kk.id_kosik = :cid');
        $stmt->execute([':cid' => $cartId]);
        $lines = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if (empty($lines)) {
            // nothing to order
            $this->app->getSession()->set('order_error', 'Košík je prázdny.');
            return $this->redirect($this->url('Cart.index'));
        }

        $total = 0.0;
        $totalQty = 0;
        foreach ($lines as $l) {
            $qty = (int)($l['mnozstvo'] ?? 1);
            $price = (float)($l['cena'] ?? 0);
            $total += $price * $qty;
            $totalQty += $qty;
        }

        $conn->beginTransaction();
        try {
            $ins = $conn->prepare('INSERT INTO objednavka (id_zakaznik, datum_vytvorenia, stav, mnozstvo, celkova_cena) VALUES (:uid, :dt, :stav, :mnozstvo, :celkova)');
            $now = date('Y-m-d H:i:s');
            $ins->execute([
                ':uid' => $user->getId(),
                ':dt' => $now,
                ':stav' => 'nova',
                ':mnozstvo' => $totalQty,
                ':celkova' => $total,
            ]);

            $orderId = (int)$conn->lastInsertId();

            $pstmt = $conn->prepare('INSERT INTO polozkaObjednavky (id_kniha, id_objednavka) VALUES (:kid, :oid)');
            foreach ($lines as $l) {
                $pstmt->execute([':kid' => $l['id_kniha'], ':oid' => $orderId]);
            }

            // Clear cart lines for user
            $del = $conn->prepare('DELETE kk FROM kosikKniha kk JOIN kosik k ON kk.id_kosik = k.id_kosik WHERE k.id_zakaznik = :uid');
            $del->execute([':uid' => $user->getId()]);

            $conn->commit();

            $this->app->getSession()->set('order_success', "Objednávka prijatá. ID: $orderId");

        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('[Cart.placeOrder] Exception: ' . $e->getMessage());
            $this->app->getSession()->set('order_error', 'Pri spracovaní objednávky nastala chyba.');
            return $this->redirect($this->url('Cart.index'));
        }

        return $this->redirect($this->url('Cart.index'));
    }

    /**
     * Helper: get existing cart id for user or create a new one.
     */
    private function getOrCreateCartId(Connection $conn, int $userId): ?int
    {
        $stmt = $conn->prepare('SELECT id_kosik FROM kosik WHERE id_zakaznik = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return (int)$row['id_kosik'];
        }

        $ins = $conn->prepare('INSERT INTO kosik (id_zakaznik) VALUES (:uid)');
        if ($ins->execute([':uid' => $userId])) {
            return (int)$conn->lastInsertId();
        }

        return null;
    }
}

