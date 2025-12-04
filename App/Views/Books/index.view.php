<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $books */
/** @var string $q */
/** @var string|null $authorFilter */
?>

<div class="container-fluid">
    <?php if (!empty($authorFilter)): ?>
        <div class="authorFoundBooks mb-4 bg-light">
            <h3 class="mb-1 fw-bold">Autor: <?= htmlspecialchars($authorFilter, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="mb-2 small text-muted">Krátke info o autorovi (demo text) — legendárny spisovateľ známy svojimi bestsellermi a nezabudnuteľnými príbehmi.</p>
            <p class="mb-0 fw-semibold">Knihy od tohto autora: (<?= count($books) ?>)</p>
        </div>
    <?php elseif (isset($q) && $q !== ''): ?>
        <div class="alert alert-info">
            Výsledky vyhľadávania pre: <strong><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></strong>
            (nájdených <?= count($books) ?> kníh)
        </div>
    <?php endif; ?>

    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($books as $book): ?>
            <?php
            $detailUrl = $link->url('Books.detail', ['id' => $book['id']]);
            $author = $book['autor'] ?? '';
            $authorUrl = $author !== '' ? $link->url('Books.index', ['q' => $author, 'author' => 1]) : null;
            ?>
            <div class="col-md-4 p-0">
                <div class="card h-100 m-0 border-0 shadow-sm">
                    <div class="row g-0 h-100 ">
                        <div class="col-4 h-100 p-0">
                            <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="d-block h-100">
                                <img src="<?= $link->asset($book['obrazok']) ?>"
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
                                        <button type="button" role="button" class="btn btn-outline-danger btn-wishlist" aria-label="Pridať do wishlistu" title="Pridať do wishlistu"
                                                data-book-id="<?= htmlspecialchars($book['id'] ?? $book['ISBN'] ?? $book['nazov'], ENT_QUOTES, 'UTF-8') ?>" aria-pressed="false">
                                            <svg width="18" height="18" fill="currentColor" class="bi bi-heart heart-icon" viewBox="0 0 16 16" focusable="false" aria-hidden="true">
                                                <path d="m8 2.748-.717-.737C5.6.281 2.514.878 1.4 3.053c-.523 1.023-.641 2.5.314 4.385C3.12 10.286 8 13 8 13s4.88-2.714 6.286-5.562c.955-1.886.838-3.362.314-4.385C13.486.878 10.4.28 8.717 2.01L8 2.748zM8 15C-7.333 4.868 3.279-3.04 7.824 1.143c.06.055.119.112.176.171a3.12 3.12 0 0 1 .176-.17C12.72-3.042 23.333 4.867 8 15z"></path>
                                            </svg>
                                            <span class="visually-hidden">Pridať do wishlistu</span>
                                        </button>

                                        <form action="<?= $link->url('Cart.add') ?>" method="post" class="m-0">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($book['id'] ?? $book['ISBN'] ?? $book['nazov'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="qty" value="1">
                                            <button type="submit" class="btn btn-primary">Do košíka</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
