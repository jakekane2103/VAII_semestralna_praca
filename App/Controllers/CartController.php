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
        // Require logged-in user for all cart actions
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

        if (!$user || $user->getId() === null) {
            // Not logged in: send to login modal via home with openLogin flag
            return $this->redirect($this->url('home.index', ['openLogin' => 1]));
        }

        $bookId = (int)($request->value('id') ?? 0);
        $qty = (int)($request->value('qty') ?? 1);
        if ($bookId <= 0 || $qty <= 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        $conn = Connection::getInstance();

        // Find or create user's cart
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

