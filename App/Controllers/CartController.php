<?php

namespace App\Controllers;

use App\Models\Cart;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Cart controller.
 */
class CartController extends BaseController
{
    public function authorize(Request $request, string $action): bool
    {
        // Allow guest access to viewing and adding to cart (session-based). Other actions require login.
        $a = strtolower((string)$action);
        if (in_array($a, ['index', 'add', 'checkout'], true)) {
            return true;
        }

        return $this->app->getAuth() !== null && $this->app->getAuth()->isLogged();
    }

    public function index(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        $userId = ($user && $user->getId() !== null) ? (int)$user->getId() : null;

        $cartModel = new Cart();
        $sessionCart = $this->app->getSession()->get('cart', []);
        $items = $cartModel->getItems($userId, $userId === null ? $sessionCart : []);

        return $this->html(['items' => $items]);
    }

    /**
     * Add a book to the current user's cart (or increase quantity).
     */
    public function add(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        $userId = ($user && $user->getId() !== null) ? (int)$user->getId() : null;

        $bookId = (int)($request->value('id') ?? 0);
        $qty = (int)($request->value('qty') ?? 1);
        if ($bookId <= 0 || $qty <= 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        // Dedupe rapid adds
        $session = $this->app->getSession();
        $last = $session->get('last_add', null);
        $now = time();
        $dedupeWindow = 2;
        $isDuplicate = false;
        if (is_array($last) && isset($last['id'], $last['ts'])) {
            if ((int)$last['id'] === $bookId && ($now - (int)$last['ts']) < $dedupeWindow) {
                $isDuplicate = true;
            }
        }

        $cartModel = new Cart();

        // Guest flow -> session cart
        if ($userId === null) {
            $cart = $session->get('cart', []);

            if ($isDuplicate) {
                $cartTotal = $cartModel->getSessionCartTotal($cart);
                if ($request->isAjax()) {
                    return $this->json(['success' => true, 'cartTotal' => $cartTotal, 'note' => 'duplicate_skipped']);
                }
                return $this->redirect($this->url('Cart.index'));
            }

            $res = $cartModel->addToSessionCart($cart, $bookId, $qty);
            $session->set('cart', $res['cart']);
            $session->set('last_add', ['id' => $bookId, 'ts' => $now]);

            if ($request->isAjax()) {
                return $this->json(['success' => true, 'cartTotal' => $res['total']]);
            }

            $referer = $request->server('HTTP_REFERER') ?? $this->url('Cart.index');
            return $this->redirect($referer);
        }

        // Logged-in flow -> DB cart
        if ($isDuplicate) {
            $cartTotal = $cartModel->getDbCartTotal($userId);
            if ($request->isAjax()) {
                return $this->json(['success' => true, 'cartTotal' => $cartTotal, 'note' => 'duplicate_skipped']);
            }
            return $this->redirect($this->url('Cart.index'));
        }

        try {
            $cartTotal = $cartModel->addToDbCart($userId, $bookId, $qty);
            $session->set('last_add', ['id' => $bookId, 'ts' => $now]);
        } catch (\Throwable $e) {
            error_log('[Cart.add] Exception: ' . $e->getMessage());
            return $this->redirect($this->url('Cart.index'));
        }

        if ($request->isAjax()) {
            return $this->json(['success' => true, 'cartTotal' => $cartTotal]);
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
        $delta = (int)($request->value('delta') ?? 0);
        if ($bookId <= 0 || $delta === 0) {
            return $this->redirect($this->url('Cart.index'));
        }

        $cartModel = new Cart();
        $cartModel->updateDbQty((int)$user->getId(), $bookId, $delta);

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

        $cartModel = new Cart();
        $cartModel->removeFromDbCart((int)$user->getId(), $bookId);

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
            $cartModel = new Cart();
            $summary = $cartModel->getCheckoutSummary((int)$user->getId());
            $items = $summary['items'];
            $total = $summary['total'];
            $totalQty = $summary['totalQty'];
        }

        return $this->html(['items' => $items, 'total' => $total, 'totalQty' => $totalQty]);
    }

    /**
     * Place order (POST) - create objednavka and polozkaObjednavky, then clear the cart lines.
     */
    public function placeOrder(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        if (!$user || $user->getId() === null) {
            return $this->redirect($this->url('home.index', ['openLogin' => 1]));
        }

        $cartModel = new Cart();
        try {
            $orderId = $cartModel->placeOrder((int)$user->getId());
            $this->app->getSession()->set('order_success', "Objednávka prijatá. ID: $orderId");
        } catch (\Throwable $e) {
            error_log('[Cart.placeOrder] Exception: ' . $e->getMessage());
            $msg = str_contains(strtolower($e->getMessage()), 'empty') ? 'Košík je prázdny.' : 'Pri spracovaní objednávky nastala chyba.';
            $this->app->getSession()->set('order_error', $msg);
        }

        return $this->redirect($this->url('Cart.index'));
    }
}

