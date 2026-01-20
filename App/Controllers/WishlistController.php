<?php

namespace App\Controllers;

use App\Models\Cart;
use App\Models\Wishlist;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Wishlist controller.
 */
class WishlistController extends BaseController
{
    public function authorize(Request $request, string $action): bool
    {
        return true;
    }

    public function index(Request $request): Response
    {
        $session = $this->app->getSession();
        $wishlistIds = $session->get('wishlist', []);

        $wishlistModel = new Wishlist();
        $items = $wishlistModel->getItemsForSessionWishlist($wishlistIds);

        return $this->html(['items' => $items]);
    }

    /**
     * Add a book to wishlist (POST)
     */
    public function add(Request $request): Response
    {
        $rawId = $request->value('id');
        if (!$rawId) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        $wishlistModel = new Wishlist();
        $resolvedId = $wishlistModel->resolveBookId($rawId);
        if ($resolvedId === null) {
            return $this->respondBadRequest($request, 'Invalid id');
        }

        $resolvedIdStr = (string)$resolvedId;

        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        if (!in_array($resolvedIdStr, array_map('strval', $wishlist), true)) {
            $wishlist[] = $resolvedIdStr;
            $session->set('wishlist', $wishlist);
        }

        // If user is logged in, persist to DB
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            if ($user && $user->getId() !== null) {
                $wishlistModel->addToDb((int)$user->getId(), $resolvedId);
            }
        }

        // fetch book record to include in JSON response for client verification
        $bookData = $wishlistModel->getBookPreview($resolvedId);

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'added', 'id' => $resolvedIdStr, 'item' => $bookData]);
        }

        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Move an item from wishlist to cart (POST)
     */
    public function moveToCart(Request $request): Response
    {
        $rawId = $request->value('id');
        if (!$rawId) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        $wishlistModel = new Wishlist();
        $resolvedId = $wishlistModel->resolveBookId($rawId);
        if ($resolvedId === null) {
            return $this->respondBadRequest($request, 'Invalid id');
        }
        $resolvedIdStr = (string)$resolvedId;

        $session = $this->app->getSession();

        // Remove from wishlist (session)
        $wishlist = $session->get('wishlist', []);
        $wishlist = array_values(array_filter($wishlist, function ($v) use ($resolvedIdStr) {
            return (string)$v !== $resolvedIdStr;
        }));
        $session->set('wishlist', $wishlist);

        $auth = $this->app->getAuth();

        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            $uid = ($user && $user->getId() !== null) ? (int)$user->getId() : null;

            if ($uid !== null) {
                // DB: remove from DB wishlist + add to DB cart
                try {
                    $wishlistModel->removeFromDb($uid, $resolvedId);

                    $cartModel = new Cart();
                    $cartModel->addToDbCart($uid, $resolvedId, 1);
                } catch (\Throwable $e) {
                    // fall through; wishlist already removed from session
                }
            }
        } else {
            // Guest fallback: add to cart via session
            $cartModel = new Cart();
            $cart = $session->get('cart', []);
            $res = $cartModel->addToSessionCart($cart, $resolvedId, 1);
            $session->set('cart', $res['cart']);
        }

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'moved', 'id' => $resolvedIdStr]);
        }

        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Remove an item from wishlist (POST)
     */
    public function remove(Request $request): Response
    {
        $rawId = $request->value('id');
        if (!$rawId) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        $wishlistModel = new Wishlist();
        $resolvedId = $wishlistModel->resolveBookId($rawId);
        $resolvedIdStr = $resolvedId !== null ? (string)$resolvedId : (string)$rawId;

        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        $wishlist = array_values(array_filter($wishlist, function ($v) use ($resolvedIdStr) {
            return (string)$v !== $resolvedIdStr;
        }));
        $session->set('wishlist', $wishlist);

        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged() && $resolvedId !== null) {
            $user = $auth->getUser();
            if ($user && $user->getId() !== null) {
                $wishlistModel->removeFromDb((int)$user->getId(), $resolvedId);
            }
        }

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'removed', 'id' => $resolvedIdStr]);
        }

        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Reorder wishlist items (POST)
     */
    public function reorder(Request $request): Response
    {
        $session = $this->app->getSession();

        $newOrder = [];
        if ($request->isJson()) {
            try {
                $data = $request->json();
                if (isset($data->order) && is_array($data->order)) {
                    $newOrder = array_map('strval', $data->order);
                }
            } catch (\JsonException $e) {
                // ignore
            }
        } else {
            $post = $request->post();
            if (is_array($post) && isset($post['order']) && is_array($post['order'])) {
                $newOrder = array_map('strval', $post['order']);
            }
        }

        if (empty($newOrder)) {
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => false, 'message' => 'No order provided']);
            }
            return $this->redirect($this->url('wishlist.index'));
        }

        $current = $session->get('wishlist', []);
        $currentMap = array_flip(array_map('strval', $current));

        $filtered = [];
        foreach ($newOrder as $id) {
            $idStr = (string)$id;
            if (isset($currentMap[$idStr])) {
                $filtered[] = $idStr;
            }
        }

        $remaining = array_values(array_filter($current, function ($v) use ($filtered) {
            return !in_array((string)$v, $filtered, true);
        }));

        $final = array_merge($filtered, $remaining);
        $session->set('wishlist', $final);

        // If logged in, try to persist positions
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            if ($user && $user->getId() !== null) {
                $wishlistModel = new Wishlist();
                $wishlistModel->tryUpdatePositions((int)$user->getId(), $final);
            }
        }

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'order' => $final]);
        }

        return $this->redirect($this->url('wishlist.index'));
    }

    private function respondBadRequest(Request $request, string $message): Response
    {
        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => false, 'message' => $message]);
        }
        $referer = $request->server('HTTP_REFERER') ?? $this->url('books.index');
        return $this->redirect($referer);
    }
}
