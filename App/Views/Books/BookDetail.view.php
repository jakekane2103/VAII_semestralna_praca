<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $book */
?>

<div class="container py-3">
    <a href="<?= $link->url('Books.index') ?>" class="btn px-2 mb-2">&larr; Späť na knihy</a>

    <div class="card shadow-sm book-detail-card border-0">
        <div class="row g-0">
            <div class="col-md-4 d-flex align-items-center justify-content-center p-3">
                <?php $img = $book['obrazok'] ?? 'images/Real_Estate_(101).jpg'; ?>
                <img src="<?= $link->asset($img) ?>"
                     alt="<?= htmlspecialchars($book['nazov'] ?? 'Bez názvu', ENT_QUOTES, 'UTF-8') ?>"
                     class="img-fluid book-detail-cover">
            </div>

            <div class="col-md-8">
                <div class="card-body d-flex flex-column h-100 p-3 p-md-4">
                    <header class="mb-3">
                        <h2 class="fw-bold mb-1 h3"><?= htmlspecialchars($book['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php $author = $book['autor'] ?? ''; ?>
                        <?php if ($author !== ''): ?>
                            <?php $authorUrl = $link->url('Books.index', ['q' => $author]); ?>
                            <h5 class="mb-1">
                                <a href="<?= htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">
                                    <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h5>
                        <?php else: ?>
                            <h5 class="text-muted mb-1"></h5>
                        <?php endif; ?>
                        <?php if (!empty($book['seria'])): ?>
                            <div class="text-muted small">Séria: <?= htmlspecialchars($book['seria'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </header>

                    <?php
                    // Stats with default values if DB fields are missing
                    $stats = [
                        'Počet strán'    => $book['pocet_stran']    ?? '352',
                        'Vydavateľstvo'  => $book['vydavatelstvo'] ?? 'Orbit Books',
                        'Jazyk'          => $book['jazyk']         ?? 'anglický',
                        'Rok vydania'    => $book['rok_vydania']   ?? '2004',
                    ];
                    ?>

                    <dl class="row small mb-3 book-detail-meta">
                        <?php foreach ($stats as $label => $value): ?>
                            <dt class="col-5 col-sm-4 text-muted mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd class="col-7 col-sm-8 mb-1"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></dd>
                        <?php endforeach; ?>
                    </dl>

                    <?php if (!empty($book['popis'])): ?>
                        <div class="mb-3 small text-muted book-detail-description">
                            <?= nl2br(htmlspecialchars($book['popis'], ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-auto pt-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <div class="book-price h3 fw-bold mb-0"><?= htmlspecialchars($book['cena'] ?? '', ENT_QUOTES, 'UTF-8') ?> €</div>
                            <?php if (!empty($book['ISBN'])): ?>
                                <div class="text-muted small">ISBN: <?= htmlspecialchars($book['ISBN'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <form action="<?= $link->url('Cart.add') ?>" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($book['id'] ?? $book['ISBN'] ?? $book['nazov'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn btn-primary">Do košíka</button>
                            </form>

                            <button type="button" class="btn btn-outline-danger btn-wishlist" title="Pridať do wishlistu"
                                    data-book-id="<?= htmlspecialchars($book['id'] ?? $book['ISBN'] ?? $book['nazov'], ENT_QUOTES, 'UTF-8') ?>">
                                <svg width="18" height="18" fill="currentColor" class="bi bi-heart" viewBox="0 0 16 16" aria-hidden="true">
                                    <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385C3.12 10.286 8 13 8 13s4.88-2.714 6.286-5.562c.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Compact book detail layout */
.book-detail-card {
    max-width: 960px;
    margin: 0 auto;
    background-color: #ffffff !important;
    color: inherit;
}

.book-detail-cover {
    max-height: 420px;
    object-fit: contain;
}

.book-detail-meta dt {
    font-weight: 500;
}

.book-detail-description {
    max-height: 180px;
    overflow-y: auto;
}

/* Ensure the detail card does not use hover effects from generic .card styles */
.book-detail-card,
.book-detail-card:hover,
.book-detail-card:focus,
.book-detail-card:active {
    transform: none !important;
    box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .05) !important;
    background-color: #ffffff !important;
}
</style>
