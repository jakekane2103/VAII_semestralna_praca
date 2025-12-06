<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $book */
?>

<div class="page-book-detail bg-light min-vh-100">
    <div class="container py-3">
        <a href="<?= $link->url('Books.index') ?>" class="text-decoration-none fs-5 text-muted mb-3 d-inline-flex align-items-center back-link">
            <span class="me-1">&larr;</span> Späť na knihy
        </a>

        <div class="card shadow-sm book-detail-card border-0 p-md-4">
            <div class="row g-4 align-items-start">

                <!-- Book Cover -->
                <div class="col-md-5 text-center">
                    <?php $img = $book['obrazok'] ?? 'images/Real_Estate_(101).jpg'; ?>
                    <img src="<?= $link->asset($img) ?>"
                         alt="<?= htmlspecialchars($book['nazov'] ?? 'Bez názvu', ENT_QUOTES, 'UTF-8') ?>"
                         class="img-fluid rounded book-detail-cover">
                </div>

                <!-- Book Info -->
                <div class="col-md-7">
                    <header class="mb-4">
                        <h1 class="fw-bold h2 mb-1"><?= htmlspecialchars($book['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>

                        <?php if (!empty($book['autor'])): ?>
                            <?php $authorUrl = $link->url('Books.index', ['q' => $book['autor']]); ?>
                            <div class="text-muted fs-5">
                                <a href="<?= htmlspecialchars($authorUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-muted">
                                    <?= htmlspecialchars($book['autor'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($book['seria'])): ?>
                            <div class="text-muted small">Séria: <?= htmlspecialchars($book['seria'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </header>

                    <!-- Book metadata -->
                    <?php
                    $stats = [
                            'Počet strán'    => $book['pocet_stran']    ?? '352',
                            'Vydavateľstvo'  => $book['vydavatelstvo'] ?? 'Orbit Books',
                            'Jazyk'          => $book['jazyk']         ?? 'anglický',
                            'Rok vydania'    => $book['rok_vydania']   ?? '2004',
                    ];
                    ?>

                    <section class="mb-4">
                        <h6 class="fw-semibold mb-2 text-uppercase small text-muted">Detaily knihy</h6>
                        <dl class="row mb-0 book-detail-meta small">
                            <?php foreach ($stats as $label => $value): ?>
                                <dt class="col-5 col-sm-4 text-muted"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
                                <dd class="col-7 col-sm-8"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></dd>
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
                            <form action="<?= $link->url('Cart.add') ?>" method="post" class="m-0">
                                <input type="hidden" name="id"
                                       value="<?= htmlspecialchars($book['id'] ?? $book['ISBN'] ?? $book['nazov'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="qty" value="1">
                                <button type="submit" class="btn btn-primary px-4">Do košíka</button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

