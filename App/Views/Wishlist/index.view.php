<?php
/** @var \Framework\Support\LinkGenerator $link */
/** @var array $items */
?>

<link rel="stylesheet" href="<?= $link->asset('css/wishlist.css') ?>">

<div class="container-fluid">
    <div class="site-bg-light">
        <div class="wishlist-container">
            <h1 class="mb-4">My Wishlist</h1>

            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <p>Your wishlist is empty.</p>
                </div>
            <?php else: ?>
                <!-- Rows list for drag & drop ordering -->
                <div id="wishlist-grid" class="wishlist-rows" aria-live="polite">
                    <?php foreach ($items as $item):
                        $id = htmlspecialchars($item['id'] ?? $item['id_kniha'] ?? '', ENT_QUOTES, 'UTF-8');
                        $title = htmlspecialchars($item['nazov'] ?? $item['nazev'] ?? $item['title'] ?? 'Untitled', ENT_QUOTES, 'UTF-8');
                        $author = htmlspecialchars($item['autor'] ?? $item['author'] ?? '', ENT_QUOTES, 'UTF-8');
                        $price = isset($item['cena']) ? number_format((float)$item['cena'], 2) : (isset($item['price']) ? number_format((float)$item['price'],2) : null);
                        $coverPath = $item['obrazok'] ?? $item['obrazek'] ?? $item['cover'] ?? 'images/placeholder-book.png';
                        $cover = $link->asset($coverPath);
                        $detailUrl = $link->url('Books.detail', ['id' => $id]);
                    ?>
                    <div class="wishlist-row d-flex align-items-center p-2 mb-2 bg-white shadow-sm rounded fs-5" data-id="<?= $id ?>">
                        <div class="wishlist-handle me-3" title="Potiahni pre zmenu poradia" aria-hidden="true">☰</div>
                        <div class="wishlist-rank me-2">&nbsp;</div>
                        <a class="wishlist-thumb me-3 d-block" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars($cover, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" class="img-fluid" style="height:72px;width:auto;object-fit:cover;border-radius:.25rem;"/>
                        </a>

                        <div class="flex-grow-1">
                            <a class="fw-bold text-decoration-none text-dark fs-5" href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $title ?></a>
                            <?php if ($author): ?><div class="text-muted small fs-6"><?= $author ?></div><?php endif; ?>
                        </div>

                        <div class="me-3 text-end" style="min-width:110px;">
                            <?php if ($price !== null): ?>
                                <div class="fw-bold fs-5"><?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?> €</div>
                            <?php else: ?>
                                <div class="text-muted">Price unavailable</div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-1">
                            <form method="post" action="<?= $link->url('Wishlist.moveToCart') ?>" class="m-0">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn-wishlist-move btn btn-sm" aria-label="Move <?= $title ?> to cart">Presunúť do košíka</button>
                            </form>

                            <form method="post" action="<?= $link->url('Wishlist.remove') ?>" class="m-0">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn-wishlist-remove btn btn-sm btn-secondary" aria-label="Remove <?= $title ?> from wishlist">Odstrániť</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Provide reorder endpoint to wishlist.js
    window.WISHLIST_REORDER_URL = <?= json_encode($link->url('Wishlist.reorder')) ?>;
</script>
<script src="<?= $link->asset('js/wishlist.js') ?>"></script>
