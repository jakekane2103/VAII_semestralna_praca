<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $book */
/** @var bool|null $inWishlist */
/** @var \Framework\Core\IAuthenticator $auth */

$isAdmin = $auth?->isLogged() && strtolower((string)($auth->user->name ?? '')) === 'admin';

// Fallbacks if controller did not provide them (keeps backward compatibility)
$imgPath = $book['imgPath'] ?? ($book['obrazok'] ?? 'images/Real_Estate_(101).jpg');
$authorUrl = $book['authorUrl'] ?? (!empty($book['autor']) ? $link->url('Books.index', ['q' => $book['autor']]) : null);
$bookId = htmlspecialchars((string)($book['bookId'] ?? ($book['id'] ?? $book['ISBN'] ?? $book['nazov'] ?? '')), ENT_QUOTES, 'UTF-8');
$isIn = filter_var($book['inWishlist'] ?? ($inWishlist ?? false), FILTER_VALIDATE_BOOLEAN);
$btnClass = $book['btnClass'] ?? ($isIn ? 'btn btn-danger px-4 btn-wishlist' : 'btn btn-outline-danger px-4 btn-wishlist');
$wishIconOn = $wishIconOn ?? ($book['wishIconOn'] ?? 'images/wishlistIconRed-outlineWhite.png');
$wishIconOff = $wishIconOff ?? ($book['wishIconOff'] ?? 'images/wishlistIconWhite.png');
$cartIcon = $cartIcon ?? ($book['cartIcon'] ?? 'images/cartIcon.png');
$wishlistAddUrl = $wishlistAddUrl ?? ($book['wishlistAddUrl'] ?? $link->url('Wishlist.add'));
$wishlistRemoveUrl = $wishlistRemoveUrl ?? ($book['wishlistRemoveUrl'] ?? $link->url('Wishlist.remove'));
?>

<div class="page-book-detail bg-light min-vh-100"
     data-wishlist-add-url="<?= htmlspecialchars($wishlistAddUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-wishlist-remove-url="<?= htmlspecialchars($wishlistRemoveUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-wish-icon-on="<?= htmlspecialchars($link->asset($wishIconOn), ENT_QUOTES, 'UTF-8') ?>"
     data-wish-icon-off="<?= htmlspecialchars($link->asset($wishIconOff), ENT_QUOTES, 'UTF-8') ?>"
     data-cart-icon="<?= htmlspecialchars($link->asset($cartIcon), ENT_QUOTES, 'UTF-8') ?>">
    <div class="container py-3">
        <a href="<?= $link->url('Books.index') ?>" class="text-decoration-none fs-5 text-muted mb-3 d-inline-flex align-items-center back-link">
            <span class="me-1">&larr;</span> Späť na knihy
        </a>

        <div class="card shadow-sm book-detail-card border-0 p-md-4">
            <div class="row g-4 align-items-start">

                <!-- Book Cover -->
                <div class="col-md-5 text-center">
                    <img src="<?= $link->asset($imgPath) ?>"
                         alt="<?= htmlspecialchars($book['nazov'] ?? 'Bez názvu', ENT_QUOTES, 'UTF-8') ?>"
                         class="img-fluid rounded book-detail-cover">
                </div>

                <!-- Book Info -->
                <div class="col-md-7">
                    <header class="mb-4">
                        <h1 class="fw-bold h2 mb-1"><?= htmlspecialchars($book['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>

                        <?php if (!empty($book['autor'])): ?>
                            <div class="text-muted fs-5">
                                <a href="<?= htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">
                                    <?= htmlspecialchars($book['autor'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($book['series_name']) || !empty($book['series_id'])): ?>
                            <div class="text-muted small">Séria: <?= htmlspecialchars($book['series_name'] ?? $book['series_id'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </header>

                    <!-- Book metadata -->
                    <?php
                    $stats = [
                        'Počet strán' => $book['pocet_stran'] ?? '352',
                        'Vydavateľstvo' => $book['vydavatelstvo'] ?? 'Orbit Books',
                        'Jazyk' => $book['jazyk'] ?? 'anglický',
                        'Rok vydania' => $book['rok_vydania'] ?? '2004',
                    ];
                    ?>

                    <section class="mb-4">
                        <h6 class="fw-semibold mb-2 text-uppercase small text-muted">Detaily knihy</h6>
                        <dl class="row mb-0 book-detail-meta small">
                            <?php foreach ($stats as $label => $value): ?>
                                <dt class="col-5 col-sm-4 text-muted"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-7 col-sm-8"><?= htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    </section>

                    <?php if (!empty($book['popis'])): ?>
                        <section class="mb-4">
                            <h6 class="fw-semibold mb-2 text-uppercase small text-muted">O knihe</h6>
                            <div class="text-muted small lh-base book-detail-description">
                                <?= nl2br(htmlspecialchars($book['popis'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Price + CTA -->
                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mt-4">
                        <div>
                            <div class="h3 fw-bold mb-0"><?= htmlspecialchars($book['cena'] ?? '', ENT_QUOTES, 'UTF-8') ?> €</div>

                            <?php if (!empty($book['ISBN'])): ?>
                                <div class="text-muted small">ISBN: <?= htmlspecialchars($book['ISBN'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <?php
                            // $bookId already computed above
                            ?>

                            <?php if (!($isAdmin ?? false)) { ?>
                                <form action="<?= $link->url('Wishlist.add') ?>" method="post" class="m-0">
                                    <input type="hidden" name="id" value="<?= $bookId ?>">
                                    <button type="submit" role="button" class="<?= $btnClass ?>" aria-label="Pridať do wishlistu" title="Pridať do wishlistu"
                                            data-book-id="<?= $bookId ?>" aria-pressed="<?= $isIn ? 'true' : 'false' ?>"
                                            data-icon-on="<?= htmlspecialchars($link->asset($wishIconOn), ENT_QUOTES, 'UTF-8') ?>" data-icon-off="<?= htmlspecialchars($link->asset($wishIconOff), ENT_QUOTES, 'UTF-8') ?>">
                                        <img src="<?= $link->asset($isIn ? $wishIconOn : $wishIconOff) ?>" alt="" class="icon2 w-16 wishlist-icon-white" aria-hidden="true">
                                        <span class="visually-hidden">Pridať do wishlistu</span>
                                    </button>
                                </form>

                                <form action="<?= $link->url('Cart.add') ?>" method="post" class="m-0">
                                    <input type="hidden" name="id" value="<?= $bookId ?>">
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <img src="<?= $link->asset($cartIcon) ?>" alt="" class="icon2 w-16 btn-cart-icon" aria-hidden="true">
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
