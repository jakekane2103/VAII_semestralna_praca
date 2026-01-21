<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $sections */
?>

<?php foreach ($sections as $section): ?>
    <h2 class="mb-1 mt-5 carousel-title fs-1"><?= htmlspecialchars($section['nazov'], ENT_QUOTES, 'UTF-8') ?></h2>

    <div id="<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>" class="carousel slide bg-light" data-bs-touch="false" data-bs-ride="carousel">
        <div class="carousel-inner mb-2">
            <div class="books-wrapper">
                <div class="carousel-cards row mt-4">
                    <?php foreach (($section['books'] ?? []) as $book): ?>
                        <?php
                            $bookId = $book['id_kniha'] ?? $book['id'] ?? null;
                            $detailUrl = $link->url('Books.detail', ['id' => $bookId]);

                            $rawImg = $book['obrazok'] ?? $book['obrazek'] ?? '';
                            $imgPath = $rawImg === '' ? 'images/placeholder-book.png' : ('images/books/' . $rawImg);
                        ?>
                        <div class="carousel-card col-6 col-md-3 mb-1">
                            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-reset d-block h-100">
                                <div class="card text-center border-0 shadow-sm h-100">
                                    <img src="<?= $link->asset($imgPath) ?>"
                                         class="card-img-top book-cover mt-3 img-fluid"
                                         alt="<?= htmlspecialchars($book['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1 fw-bold"><?= htmlspecialchars(mb_strimwidth((string)($book['nazov'] ?? ''), 0, 23, '...'), ENT_QUOTES, 'UTF-8') ?></h5>
                                        <p class="card-subtitle text-muted mb-0"><?= htmlspecialchars($book['autor'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <strong class="book-price"><?= htmlspecialchars($book['cena'] ?? '', ENT_QUOTES, 'UTF-8') ?> €</strong>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?= htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

<?php endforeach; ?>