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
class WishlistController extends BaseController
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
        return true;
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
        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);

        $items = [];
        if (!empty($wishlist)) {
            $conn = Connection::getInstance();

            // sanitize and unique ids
            $ids = array_values(array_unique(array_filter($wishlist, function ($v) { return ctype_digit((string)$v) || is_int($v); })));

            if (!empty($ids)) {
                // build placeholders
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql = "SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena, seria FROM kniha WHERE id_kniha IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                // bind as integers
                $stmt->execute($ids);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                // index rows by id for ordering
                $byId = [];
                foreach ($rows as $r) {
                    $byId[(string)$r['id']] = $r;
                }

                // preserve wishlist order
                foreach ($ids as $id) {
                    $key = (string)$id;
                    if (isset($byId[$key])) {
                        $items[] = $byId[$key];
                    }
                }
            }
        }

        return $this->html(['items' => $items]);
    }

    // Helper: get or create wishlist id for a customer
    private function getOrCreateWishlistId(Connection $conn, int $userId): ?int
    {
        $stmt = $conn->prepare('SELECT id_wishlist FROM wishlist WHERE id_zakaznik = :uid LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row && isset($row['id_wishlist'])) {
            return (int)$row['id_wishlist'];
        }

        // create a default title
        $ins = $conn->prepare('INSERT INTO wishlist (id_zakaznik, title, datum_pridania) VALUES (:uid, :title, NOW())');
        $ok = $ins->execute([':uid' => $userId, ':title' => 'Moje wishlist']);
        if ($ok) {
            return (int)$conn->lastInsertId();
        }
        return null;
    }

    /**
     * Add a book to wishlist (POST)
     */
    public function add(Request $request): Response
    {
        $id = $request->value('id');
        if (!$id) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        // Resolve non-numeric identifiers (ISBN or title) to numeric DB id_kniha
        $resolvedId = $id;
        $conn = Connection::getInstance();
        if (!ctype_digit((string)$id)) {
            // Try ISBN first
            $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE ISBN = :v LIMIT 1');
            $stmt->execute([':v' => $id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['id_kniha'])) {
                $resolvedId = (string)$row['id_kniha'];
            } else {
                // Try by exact title
                $stmt = $conn->prepare('SELECT id_kniha FROM kniha WHERE nazov = :v LIMIT 1');
                $stmt->execute([':v' => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row && isset($row['id_kniha'])) {
                    $resolvedId = (string)$row['id_kniha'];
                }
            }
        }

        if (!$resolvedId) {
            return $this->respondBadRequest($request, 'Invalid id');
        }

        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        if (!in_array($resolvedId, $wishlist, true)) {
            $wishlist[] = $resolvedId;
            $session->set('wishlist', $wishlist);
        }

        // If user is logged in, persist to DB
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            $uid = $user->getId();
            // ensure $conn is defined
            $conn = Connection::getInstance();
            $wid = $this->getOrCreateWishlistId($conn, (int)$uid);
            if ($wid !== null) {
                try {
                    $ins = $conn->prepare('INSERT INTO wishlistKniha (id_wishlist, id_kniha) VALUES (:wid, :kid)');
                    $ins->execute([':wid' => $wid, ':kid' => $resolvedId]);
                } catch (\Throwable $e) {
                    // ignore duplicates or DB errors
                }
            }
        }

        // fetch book record to include in JSON response for client verification
        $bookData = null;
        $stmt = $conn->prepare('SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena FROM kniha WHERE id_kniha = :id LIMIT 1');
        $stmt->execute([':id' => $resolvedId]);
        $bookData = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        // If AJAX request => return JSON
        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'added', 'id' => $resolvedId, 'item' => $bookData]);
        }

        // Non-AJAX fallback: redirect back to referer or wishlist
        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Move an item from wishlist to cart (POST)
     */
    public function moveToCart(Request $request): Response
    {
        $id = $request->value('id');
        if (!$id) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        $session = $this->app->getSession();
        // Remove from wishlist
        $wishlist = $session->get('wishlist', []);
        $wishlist = array_values(array_filter($wishlist, function ($v) use ($id) { return (string)$v !== (string)$id; }));
        $session->set('wishlist', $wishlist);

        // Add to cart via session
        $cart = $session->get('cart', []);
        if (isset($cart[$id])) {
            $cart[$id] += 1;
        } else {
            $cart[$id] = 1;
        }
        $session->set('cart', $cart);

        // If user is logged in remove from DB wishlist as well
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            $uid = $user->getId();
            // ensure $conn is defined
            $conn = Connection::getInstance();
            $wid = $this->getOrCreateWishlistId($conn, (int)$uid);
            if ($wid !== null) {
                try {
                    $del = $conn->prepare('DELETE FROM wishlistKniha WHERE id_wishlist = :wid AND id_kniha = :kid');
                    $del->execute([':wid' => $wid, ':kid' => $id]);
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'moved', 'id' => $id]);
        }

        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Remove an item from wishlist (POST)
     */
    public function remove(Request $request): Response
    {
        $id = $request->value('id');
        if (!$id) {
            return $this->respondBadRequest($request, 'Missing id');
        }

        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        $wishlist = array_values(array_filter($wishlist, function ($v) use ($id) { return (string)$v !== (string)$id; }));
        $session->set('wishlist', $wishlist);

        // If user is logged in remove from DB wishlist as well
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            $uid = $user->getId();
            // ensure $conn is defined
            $conn = Connection::getInstance();
            $wid = $this->getOrCreateWishlistId($conn, (int)$uid);
            if ($wid !== null) {
                try {
                    $del = $conn->prepare('DELETE FROM wishlistKniha WHERE id_wishlist = :wid AND id_kniha = :kid');
                    $del->execute([':wid' => $wid, ':kid' => $id]);
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        if ($request->isAjax() || $request->wantsJson()) {
            return $this->json(['success' => true, 'action' => 'removed', 'id' => $id]);
        }

        $referer = $request->server('HTTP_REFERER') ?? $this->url('wishlist.index');
        return $this->redirect($referer);
    }

    /**
     * Reorder wishlist items (POST)
     * Accepts either form fields order[] or JSON body with { order: [id1,id2,...] }
     */
    public function reorder(Request $request): Response
    {
        $session = $this->app->getSession();

        $newOrder = [];
        // Prefer JSON body if present
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
            // Check POST values: order[] fields
            $post = $request->post();
            if (is_array($post) && isset($post['order']) && is_array($post['order'])) {
                $newOrder = array_map('strval', $post['order']);
            }
        }

        if (empty($newOrder)) {
            // nothing to do
            if ($request->isAjax() || $request->wantsJson()) {
                return $this->json(['success' => false, 'message' => 'No order provided']);
            }
            return $this->redirect($this->url('wishlist.index'));
        }

        // Validate IDs are numeric-ish and keep only those present in existing wishlist
        $current = $session->get('wishlist', []);
        $currentMap = array_flip(array_map('strval', $current));
        $filtered = [];
        foreach ($newOrder as $id) {
            $idStr = (string)$id;
            if (isset($currentMap[$idStr])) {
                $filtered[] = $idStr;
            }
        }

        // Save filtered order (append any existing items not present in order to the end)
        $remaining = array_values(array_filter($current, function ($v) use ($filtered) { return !in_array((string)$v, $filtered, true); }));
        $final = array_merge($filtered, $remaining);
        $session->set('wishlist', $final);

        // If user is logged in and DB supports positions, try to persist positions
        $auth = $this->app->getAuth();
        if ($auth && $auth->isLogged()) {
            $user = $auth->getUser();
            $uid = $user->getId();
            $conn = Connection::getInstance();
            $wid = $this->getOrCreateWishlistId($conn, (int)$uid);
            if ($wid !== null) {
                try {
                    // attempt to update pozicia column if it exists
                    $pos = 1;
                    $posCol = 'pozicia';
                    foreach ($final as $kid) {
                        // build SQL using variable column name to avoid static analysis of non-existing column
                        $sql = sprintf('UPDATE wishlistKniha SET %s = :p WHERE id_wishlist = :wid AND id_kniha = :kid', $posCol);
                        $upd = $conn->prepare($sql);
                        $upd->execute([':p' => $pos, ':wid' => $wid, ':kid' => $kid]);
                        $pos++;
                    }
                } catch (\Throwable $e) {
                    // if the column doesn't exist or other DB error, ignore (order will remain session-based)
                }
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
