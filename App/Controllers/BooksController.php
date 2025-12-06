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
class BooksController extends BaseController
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
        // Read search query from GET (desktop and mobile forms submit here)
        $q = '';
        $raw = $request->get('q');
        if ($raw !== null) {
            $q = trim((string)$raw);
        }

        // Detect if this is an "author view" (clicked on author name)
        $authorFilter = null;
        $authorFlag = $request->get('author');
        if ($authorFlag !== null && $q !== '') {
            $authorFilter = $q; // we treat q as exact author name for header
        }

        $conn = Connection::getInstance();

        if ($q === '') {
            // No search term -> return all books (limited to reasonable count)
            $stmt = $conn->prepare("SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena, seria FROM kniha ORDER BY nazov LIMIT 200");
            $stmt->execute();
        } else {
            // Search by nazov, autor or seria (case-insensitive by DB collation)
            $sql = "SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena, seria
                    FROM kniha
                    WHERE nazov LIKE :q OR autor LIKE :q OR seria LIKE :q
                    ORDER BY nazov LIMIT 200";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':q' => '%' . $q . '%']);
        }

        $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get wishlist IDs from session so the view can mark hearts as filled
        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        // normalize to string keys for quick lookup
        $wishlistMap = array_flip(array_map('strval', $wishlist));

        return $this->html([
            'books'        => $books,
            'q'            => $q,
            'authorFilter' => $authorFilter,
            'wishlistMap'  => $wishlistMap,
        ]);
    }

    /**
     * Shows detail of a single book.
     */
    public function detail(Request $request): Response
    {
        $id = $request->get('id');
        if ($id === null || !ctype_digit((string)$id)) {
            // fallback: redirect back to books list
            return $this->redirect($this->url('Books.index'));
        }

        $conn = Connection::getInstance();
        $stmt = $conn->prepare("SELECT id_kniha AS id, nazov, autor, obrazok, popis, cena, seria, ISBN FROM kniha WHERE id_kniha = :id");
        $stmt->execute([':id' => $id]);
        $book = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$book) {
            // book not found, go back to list
            return $this->redirect($this->url('Books.index'));
        }

        return $this->html(['book' => $book], 'BookDetail');
    }
}
