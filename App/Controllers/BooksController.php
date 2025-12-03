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

        return $this->html(['books' => $books, 'q' => $q]);
    }
}
