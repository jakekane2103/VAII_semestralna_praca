<?php

namespace App\Controllers;

use App\Models\Book;
use Framework\Core\BaseController;
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
        $q = trim((string)($request->get('q') ?? ''));

        // "author view" (clicked on author)
        $authorFlag = $request->get('author');
        $authorFilter = ($authorFlag !== null && $q !== '') ? $q : null;

        // Pagination: page parameter 'p', default 1
        $pageRaw = $request->get('p');
        $page = 1;
        if ($pageRaw !== null && ctype_digit((string)$pageRaw)) {
            $page = max(1, (int)$pageRaw);
        }
        $perPage = 21;

        $bookModel = new Book();
        $result = $bookModel->getPaginatedList($q !== '' ? $q : null, $page, $perPage);

        // Get wishlist IDs from session so the view can mark hearts as filled
        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        $wishlistMap = array_flip(array_map('strval', $wishlist));

        // Series list is used by some UI/admin widgets; keep it available
        $series = [];
        try {
            $series = $bookModel->getAllSeries();
        } catch (\Throwable $e) {
            $series = [];
        }

        return $this->html([
            'books'        => $result['books'],
            'q'            => $q,
            'authorFilter' => $authorFilter,
            'authorFlag'   => $authorFlag,
            'wishlistMap'  => $wishlistMap,
            'series'       => $series,
            'page'         => $result['page'],
            'totalPages'   => $result['totalPages'],
            'perPage'      => $result['perPage'],
            'totalBooks'   => $result['totalBooks'],
        ]);
    }

    /**
     * Shows detail of a single book.
     */
    public function detail(Request $request): Response
    {
        $id = $request->get('id');
        if ($id === null || !ctype_digit((string)$id)) {
            return $this->redirect($this->url('Books.index'));
        }

        $bookModel = new Book();
        $book = $bookModel->getDetailById((int)$id);

        if (!$book) {
            return $this->redirect($this->url('Books.index'));
        }

        // Determine if the book is already in wishlist to reflect heart style
        $session = $this->app->getSession();
        $wishlist = $session->get('wishlist', []);
        $inWishlist = in_array((string)($book['id'] ?? $id), array_map('strval', $wishlist), true);

        return $this->html(['book' => $book, 'inWishlist' => $inWishlist], 'BookDetail');
    }
}
