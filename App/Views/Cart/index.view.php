<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var array $items */

$total = 0;

// Read flash messages (controller sets these via session) and clear them so they only show once
$orderSuccess = $_SESSION['order_success'] ?? null;
$orderError = $_SESSION['order_error'] ?? null;
if ($orderSuccess) { unset($_SESSION['order_success']); }
if ($orderError) { unset($_SESSION['order_error']); }
?>

<div class="container my-5 w-50 bg-light p-4">
    <h2 class="mb-4">Tvoj košík</h2>
    <?php if (!empty($items)): ?>
        <div class="row g-3">
            <?php foreach ($items as $item): ?>
                <?php
                $quantity = (int)($item['mnozstvo'] ?? 1);
                $itemTotal = (float)$item['cena'] * $quantity;
                $total += $itemTotal;
                // Precompute book detail URL
                $detailUrl = $link->url('Books.detail', ['id' => (int)$item['id_kniha']]);
                // Normalize image path: if stored value is a bare filename, prefix with images/books/
                $rawImg = $item['obrazok'] ?? '';
                $imgPath = 'images/books/' . $rawImg;
                ?>
                <div class="col-12">
                    <div class="card cart-item border-0 shadow-sm p-3 d-flex flex-row align-items-center">
                         <a href="<?= $detailUrl ?>" class="d-inline-block">
                             <img src="<?= $link->asset($imgPath) ?>"
                                  alt="<?= htmlspecialchars($item['nazov'], ENT_QUOTES, 'UTF-8') ?>"
                                 class="cart-img me-3">
                         </a>

                         <div class="cart-details flex-grow-1">
                            <h5 class="mb-1">
                                <a href="<?= $detailUrl ?>" class="text-decoration-none text-dark fs-5 fw-bold">
                                    <?= htmlspecialchars($item['nazov'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h5>
                            <small class="text-muted fs-6"><?= htmlspecialchars($item['autor'], ENT_QUOTES, 'UTF-8') ?></small>
                        </div>

                        <div class="d-flex align-items-center me-3 gap-1">
                            <form action="<?= $link->url('Cart.update') ?>" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?= (int)$item['id_kniha'] ?>">
                                <input type="hidden" name="delta" value="-1">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">−</button>
                            </form>

                            <span class="form-control form-control-sm text-center" style="width: 50px;">
                                <?= $quantity ?>
                            </span>

                            <form action="<?= $link->url('Cart.update') ?>" method="post" class="m-0">
                                <input type="hidden" name="id" value="<?= (int)$item['id_kniha'] ?>">
                                <input type="hidden" name="delta" value="1">
                                <button type="submit" class="btn btn-outline-secondary btn-sm">+</button>
                            </form>
                        </div>

                        <div class="cart-price text-end me-3 fw-bold fs-5"><?= number_format($itemTotal, 2) ?> €</div>

                        <form action="<?= $link->url('Cart.remove') ?>" method="post" class="m-0">
                            <input type="hidden" name="id" value="<?= (int)$item['id_kniha'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Odstrániť</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-end mt-4">
            <div class="d-flex justify-content-end align-items-center">
                <h4 class="me-2 mb-0">Spolu:</h4>
                <h4 class="fw-bold mb-0"><?= number_format($total, 2) ?> €</h4>
            </div>

            <a href="<?= $link->url('Cart.checkout') ?>" class="btn btn-danger btn-lg mt-2">Pokračovať k platbe</a>
        </div>

    <?php else: ?>
        <p>Košík je prázdny.</p>
    <?php endif; ?>
</div>


<!-- Toast container for success/error messages (shows after placeOrder redirect) -->
<div aria-live="polite" aria-atomic="true" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div id="orderToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="orderToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<?php if ($orderSuccess || $orderError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toastEl = document.getElementById('orderToast');
            var bodyEl = document.getElementById('orderToastBody');
            <?php if ($orderSuccess): ?>
                bodyEl.textContent = <?= json_encode(is_string($orderSuccess) ? $orderSuccess : 'Objednávka úspešná') ?>;
                toastEl.classList.remove('text-bg-danger');
                toastEl.classList.add('text-bg-success');
            <?php else: ?>
                bodyEl.textContent = <?= json_encode(is_string($orderError) ? $orderError : 'Pri objednávke nastala chyba') ?>;
                toastEl.classList.remove('text-bg-success');
                toastEl.classList.add('text-bg-danger');
            <?php endif; ?>

            var toast = bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3000 });
            toast.show();
        });
    </script>
<?php endif; ?>
