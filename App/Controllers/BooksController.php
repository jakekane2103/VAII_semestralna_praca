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
        // If the `author` flag is present (user clicked an author link), restrict search to authors only.
        $authorOnly = ($authorFlag !== null);
        $result = $bookModel->getPaginatedList($q !== '' ? $q : null, $page, $perPage, $authorOnly);

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

        // --- Prepare presentation-specific fields for each book to keep the view thin ---
        $books = $result['books'];
        foreach ($books as &$b) {
            // detail URL
            $b['detailUrl'] = $this->url('Books.detail', ['id' => $b['id'] ?? '']);

            // author link (search-by-author)
            $author = $b['autor'] ?? '';
            $b['authorUrl'] = $author !== '' ? $this->url('Books.index', ['q' => $author, 'author' => 1]) : null;

            // normalize image path (same rules as previous view code)
            $rawImg = $b['obrazok'] ?? '';
            if ($rawImg === null || $rawImg === '') {
                $imgPath = 'images/placeholder-book.png';
            } elseif (strpos($rawImg, '/') === false) {
                $imgPath = 'images/books/' . $rawImg;
            } else {
                $imgPath = $rawImg;
            }
            $b['imgPath'] = $imgPath;

            // book id used in forms and data attributes (keep as string)
            $bookIdRaw = $b['id'] ?? $b['ISBN'] ?? $b['nazov'] ?? '';
            $bookIdRaw = (string)$bookIdRaw;
            $b['bookId'] = $bookIdRaw;

            // wishlist state (boolean) and small helpers for classes/aria
            $inWishlist = isset($wishlistMap[$bookIdRaw]);
            $b['inWishlist'] = (bool)$inWishlist;
            $b['btnClass'] = $inWishlist ? 'btn btn-danger' : 'btn btn-outline-danger';
            $b['ariaPressed'] = $inWishlist ? 'true' : 'false';
        }
        unset($b);

        // Build base params preserving the search query and author flag (used by pagination links)
        $baseParams = [];
        if ($q !== '') {
            $baseParams['q'] = $q;
        }
        if (!empty($authorFlag)) {
            $baseParams['author'] = 1;
        }

        // Asset paths used by the view
        $wishIconOn = 'images/wishlistIconRed-outlineWhite.png';
        $wishIconOff = 'images/wishlistIconWhite.png';
        $cartIcon = 'images/cartIcon.png';

        return $this->html([
            'books'        => $books,
            'q'            => $q,
            'authorFilter' => $authorFilter,
            'authorFlag'   => $authorFlag,
            'wishlistMap'  => $wishlistMap,
            'series'       => $series,
            'page'         => $result['page'],
            'totalPages'   => $result['totalPages'],
            'perPage'      => $result['perPage'],
            'totalBooks'   => $result['totalBooks'],

            // presentation helpers
            'baseParams'   => $baseParams,
            'wishIconOn'   => $wishIconOn,
            'wishIconOff'  => $wishIconOff,
            'cartIcon'     => $cartIcon,
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

        // --- Prepare presentation-specific fields for the detail view ---
        // normalize image path
        $rawImg = $book['obrazok'] ?? '';
        if ($rawImg === null || $rawImg === '') {
            $imgPath = 'images/Real_Estate_(101).jpg';
        } elseif (strpos($rawImg, '/') === false) {
            $imgPath = 'images/books/' . $rawImg;
        } else {
            $imgPath = $rawImg;
        }
        $book['imgPath'] = $imgPath;

        // author search link
        $author = $book['autor'] ?? '';
        $book['authorUrl'] = $author !== '' ? $this->url('Books.index', ['q' => $author]) : null;

        // book id and wishlist state
        $bookIdRaw = $book['id'] ?? $book['ISBN'] ?? $book['nazov'] ?? '';
        $bookIdRaw = (string)$bookIdRaw;
        $book['bookId'] = $bookIdRaw;
        $book['inWishlist'] = (bool)$inWishlist;
        $book['btnClass'] = $inWishlist ? 'btn btn-danger px-4 btn-wishlist' : 'btn btn-outline-danger px-4 btn-wishlist';
        $book['ariaPressed'] = $inWishlist ? 'true' : 'false';

        // assets and urls used by the view
        $wishIconOn = 'images/wishlistIconRed-outlineWhite.png';
        $wishIconOff = 'images/wishlistIconWhite.png';
        $cartIcon = 'images/cartIcon.png';

        return $this->html([
            'book' => $book,
            'inWishlist' => $inWishlist,
            'wishIconOn' => $wishIconOn,
            'wishIconOff' => $wishIconOff,
            'cartIcon' => $cartIcon,
            'wishlistAddUrl' => $this->url('Wishlist.add'),
            'wishlistRemoveUrl' => $this->url('Wishlist.remove'),
        ], 'BookDetail');
    }
}
