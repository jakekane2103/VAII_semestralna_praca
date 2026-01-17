<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $items */
/** @var float $total */
/** @var int $totalQty */

// Use session for lightweight flash messages (controller sets these keys)
$orderSuccess = $_SESSION['order_success'] ?? null;
$orderError = $_SESSION['order_error'] ?? null;
// clear flash after reading
if ($orderSuccess) {
    unset($_SESSION['order_success']);
}
if ($orderError) {
    unset($_SESSION['order_error']);
}
?>

<div class="container my-5 w-75 bg-light p-4">
    <h2 class="mb-4">Pokladňa</h2>

    <div class="row">
        <div class="col-md-7">
            <h4>Adresné údaje</h4>
            <p>Pri tomto checkout-e použijeme uložené údaje účtu. Môžete ich upraviť v profile.</p>

            <h4 class="mt-4">Spôsob doručenia</h4>
            <form action="<?= $link->url('Cart.placeOrder') ?>" method="post">
                <div class="mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery" id="del1" value="store" checked>
                        <label class="form-check-label" for="del1">Doručenie na výdajné miesto (zadarmo)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery" id="del2" value="kurier">
                        <label class="form-check-label" for="del2">Kuriér (zvyčajne 3-5 €)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery" id="del3" value="posta">
                        <label class="form-check-label" for="del3">Slovenská pošta (na adresu)</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger btn-lg mt-3">Pokračovať v objednávke</button>
            </form>
        </div>

        <div class="col-md-5">
            <h4>Váš košík (<?php echo (int)($totalQty ?? count($items)); ?> položiek)</h4>
            <div class="list-group mb-3">
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $it): ?>
                        <?php
                        $qty = (int)($it['mnozstvo'] ?? 1);
                        $price = number_format((float)($it['cena'] ?? 0), 2);
                        $img = 'images/books/' . ($it['obrazok'] ?? '');
                        $detailUrl = $link->url('Books.detail', ['id' => (int)$it['id_kniha']]);
                        ?>
                        <div class="list-group-item d-flex align-items-stretch p-2">
                            <div style="flex: 0 0 80px; max-width:50px; display:flex; align-items:stretch;" class="me-3">
                                <img src="<?= $img ?>" alt="<?= htmlspecialchars($it['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="height:100%; width:100%; object-fit:cover; display:block;" />
                            </div>

                            <div class="flex-grow-1 d-flex flex-column justify-content-center">
                                <a href="<?= $detailUrl ?>" class="text-decoration-none fw-bold mb-1"><?= htmlspecialchars($it['nazov'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
                                <div class="text-muted small"><?= htmlspecialchars($it['autor'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>

                            <div class="d-flex align-items-center ms-3" style="min-width:120px; justify-content:space-between;">
                                <div class="text-muted small">x<?= $qty ?></div>
                                <div class="fw-bold"><?= $price ?> €</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item">Košík je prázdny.</div>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                <div>
                    <div class="fs-4 text-muted">Spolu položky</div>
                    <div class="fw-bold fs-4"><?php echo number_format($total ?? 0, 2); ?> €</div>
                </div>
            </div>
        </div>
    </div>
</div>
