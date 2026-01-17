<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $books */
/** @var string $q */
/** @var string|null $authorFilter */
/** @var \Framework\Core\IAuthenticator $auth */

// detect admin in the view (match project's simple username check)
$isAdmin = $auth?->isLogged() && strtolower((string)($auth->user->name ?? '')) === 'admin';
?>

<div class="container-fluid books-index">
    <?php if (!empty($authorFilter)): ?>
        <div class="authorFoundBooks mb-4 bg-light">
            <h3 class="mb-1 fw-bold">Autor: <?= htmlspecialchars($authorFilter, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-2 small text-muted">Krátke info o autorovi (demo text) — legendárny spisovateľ známy svojimi bestsellermi a nezabudnuteľnými príbehmi.</p>
            <p class="mb-0 fw-semibold">Knihy od tohto autora: (<?= isset($totalBooks) ? $totalBooks : count($books) ?>)</p>
        </div>
    <?php elseif (isset($q) && $q !== ''): ?>
        <div class="alert alert-info">
            Výsledky vyhľadávania pre: <strong><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></strong>
            (nájdených <?= isset($totalBooks) ? $totalBooks : count($books) ?> kníh)
        </div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($books as $book): ?>
            <?php
            $detailUrl = $link->url('Books.detail', ['id' => $book['id']]);
            $author = $book['autor'] ?? '';
            $authorUrl = $author !== '' ? $link->url('Books.index', ['q' => $author, 'author' => 1]) : null;
            // normalize obrazok for display: prefix with images/books/ when value is a bare filename
            $rawImg = $book['obrazok'] ?? '';
            if ($rawImg === null || $rawImg === '') {
                $imgPath = 'images/placeholder-book.png';
            } elseif (strpos($rawImg, '/') === false) {
                $imgPath = 'images/books/' . $rawImg;
            } else {
                $imgPath = $rawImg;
            }
            ?>
            <div class="col-md-4 p-0">
                <div class="card h-100 m-0 border-0 shadow-sm">
                    <div class="row g-0 h-100 ">
                        <div class="col-4 h-100 p-0">
                            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="d-block h-100">
                                <img src="<?= $link->asset($imgPath) ?>"
                                     class="img-fluid rounded-start book-cover h-100"
                                     alt="<?= htmlspecialchars($book['nazov'], ENT_QUOTES, 'UTF-8') ?>">
                            </a>
                        </div>
                        <div class="col-8 p-3 d-flex flex-column">
                            <div class="card-body d-flex flex-column p-0 flex-grow-1">
                                <h5 class="card-title fw-bold mb-1">
                                    <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-reset">
                                        <?= htmlspecialchars($book['nazov'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h5>
                                <?php if ($authorUrl !== null): ?>
                                    <h6 class="card-subtitle mb-2">
                                        <a href="<?= htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-muted text-decoration-none">
                                            <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </h6>
                                <?php else: ?>
                                    <h6 class="card-subtitle text-muted mb-2"><?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?></h6>
                                <?php endif; ?>
                                <p class="card-text mt-1 mb-2"><?= htmlspecialchars($book['popis'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

                                <div class="book-footer mt-auto d-flex justify-content-between align-items-center">
                                    <div class="book-price fw-bold fs-5"><?= htmlspecialchars($book['cena'], ENT_QUOTES, 'UTF-8') ?> €</div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <?php
                                        $bookIdRaw = $book['id'] ?? $book['ISBN'] ?? $book['nazov'];
                                        $bookId = htmlspecialchars($bookIdRaw, ENT_QUOTES, 'UTF-8');
                                        $inWishlist = isset($wishlistMap[(string)$bookIdRaw]);
                                        $btnClass = $inWishlist ? 'btn btn-danger' : 'btn btn-outline-danger';
                                        $ariaPressed = $inWishlist ? 'true' : 'false';
                                        ?>

                                        <?php if (!($isAdmin ?? false)) { ?>
                                            <!-- Wishlist + Cart controls (hidden for admin users) -->
                                            <form action="<?= $link->url('Wishlist.add') ?>" method="post" class="m-0">
                                                <input type="hidden" name="id" value="<?= $bookId ?>">
                                                <button type="submit" role="button" class="<?= $btnClass ?> btn-wishlist" aria-label="Pridať do wishlistu" title="Pridať do wishlistu"
                                                        data-book-id="<?= $bookId ?>" aria-pressed="<?= $ariaPressed ?>"
                                                        data-icon-on="<?= $link->asset('images/wishlistIconRed-outlineWhite.png') ?>"
                                                        data-icon-off="<?= $link->asset('images/wishlistIconWhite.png') ?>">
                                                    <img src="<?= $link->asset($inWishlist ? 'images/wishlistIconRed-outlineWhite.png' : 'images/wishlistIconWhite.png') ?>" alt="" class="icon2 w-16 wishlist-icon-white" aria-hidden="true">
                                                    <span class="visually-hidden">Pridať do wishlistu</span>
                                                </button>
                                            </form>

                                            <form action="<?= $link->url('Cart.add') ?>" method="post" class="m-0 js-add-to-cart"
                                                  data-book-title="<?= htmlspecialchars($book['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                  data-book-author="<?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>"
                                                  data-book-image="<?= $link->asset($imgPath) ?>"
                                                  data-book-price="<?= htmlspecialchars($book['cena'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($book['id'], ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="qty" value="1">
                                                <button type="submit" class="btn btn-primary">
                                                    <img src="<?= $link->asset('images/cartIcon.png') ?>" alt="" class="icon2 w-16 btn-cart-icon" aria-hidden="true">
                                                    <span class="visually-hidden btn-label">Do košíka</span>
                                                </button>
                                            </form>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php // Pagination controls: show only when more than 1 page ?>
    <?php if (isset($totalPages) && $totalPages > 1): ?>
        <?php
        // Build base params preserving the search query and author flag
        $baseParams = [];
        if (isset($q) && $q !== '') {
            $baseParams['q'] = $q;
        }
        if (!empty($authorFlag)) {
            $baseParams['author'] = 1;
        }
        $currentPage = isset($page) ? (int)$page : 1;
        $maxPagesToShow = 10; // simple cap to avoid overwhelming the UI
        $start = 1;
        $end = $totalPages;
        if ($totalPages > $maxPagesToShow) {
            // center current page in the range when possible
            $half = (int)floor($maxPagesToShow / 2);
            $start = max(1, $currentPage - $half);
            $end = $start + $maxPagesToShow - 1;
            if ($end > $totalPages) {
                $end = $totalPages;
                $start = $end - $maxPagesToShow + 1;
            }
        }
        ?>

        <?php // Styles moved to public/css/books.css - no inline styles here anymore ?>

        <nav aria-label="Pages" class="books-pagination my-4">
            <ul class="pagination pagination-lg justify-content-center">
                <?php // Previous button ?>
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <?php
                    if ($currentPage <= 1):
                        // disabled prev
                    ?>
                        <span class="page-link">&laquo;</span>
                    <?php else:
                        $params = array_merge($baseParams, ['p' => $currentPage - 1]);
                        $prevUrl = $link->url('Books.index', $params);
                    ?>
                        <a class="page-link" href="<?= htmlspecialchars($prevUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Previous">&laquo;</a>
                    <?php endif; ?>
                </li>

                <?php if ($start > 1): ?>
                    <?php $params = array_merge($baseParams, ['p' => 1]); ?>
                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($link->url('Books.index', $params), ENT_QUOTES, 'UTF-8') ?>">1</a></li>
                    <?php if ($start > 2): ?>
                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <?php $params = array_merge($baseParams, ['p' => $i]); ?>
                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                        <?php if ($i === $currentPage): ?>
                            <span class="page-link"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-link" href="<?= htmlspecialchars($link->url('Books.index', $params), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                    <?php endif; ?>
                    <?php $params = array_merge($baseParams, ['p' => $totalPages]); ?>
                    <li class="page-item"><a class="page-link" href="<?= htmlspecialchars($link->url('Books.index', $params), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>

                <?php // Next button ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <?php if ($currentPage >= $totalPages): ?>
                        <span class="page-link">&raquo;</span>
                    <?php else:
                        $params = array_merge($baseParams, ['p' => $currentPage + 1]);
                        $nextUrl = $link->url('Books.index', $params);
                    ?>
                        <a class="page-link" href="<?= htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Next">&raquo;</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Add to Cart confirmation modal (richer design) -->
    <div class="modal fade" id="addToCartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-success mb-0">Do košíka ste práve pridali:</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvoriť"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <img id="addToCartModalImg" src="" alt="" />
                        <div class="flex-grow-1">
                            <div id="addToCartModalTitle" class="fw-bold"></div>
                            <div id="addToCartModalAuthor" class="text-muted small"></div>
                            <div id="addToCartModalPrice" class="text-success fw-bold mt-2"></div>
                        </div>
                    </div>

                    <div class="text-center my-3">
                        <a href="#" id="addToCartModalViewAll" class="text-decoration-none">Zobraziť všetky →</a>
                    </div>

                    <div class="small text-muted text-center" id="addToCartModalShippingText">
                        Nakúpte ešte za <strong id="addToCartModalRemaining">0,00 €</strong> a dopravu do výdajných miest máte zadarmo.
                    </div>
                    <div class="progress mt-2">
                        <div id="addToCartModalProgress" class="progress-bar bg-success" role="progressbar"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <div class="small text-muted">Spolu</div>
                            <div id="addToCartModalTotal" class="fw-bold fs-4"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" id="addToCartModalEdit" class="btn btn-outline-secondary" data-bs-dismiss="modal">Pokračovať v nákupe</button>
                            <button type="button" id="addToCartModalCheckout" class="btn btn-danger">Košík</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables used by the global cart/wishlist JS (loaded from layout as cart.js)
        window.BOOKS_CART_URL = <?= json_encode($link->url('Cart.index')) ?>;
        window.WISHLIST_ADD_URL = <?= json_encode($link->url('Wishlist.add')) ?>;
        window.WISHLIST_REMOVE_URL = <?= json_encode($link->url('Wishlist.remove')) ?>;
    </script>
</div>