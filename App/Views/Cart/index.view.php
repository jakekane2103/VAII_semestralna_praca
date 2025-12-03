<?php

/** @var \Framework\Support\LinkGenerator $link */
 $total = 0;

use Framework\DB\Connection;

$conn = Connection::getInstance();
$sql = "SELECT * FROM kniha  LIMIT 4";
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute(); // spustí dotaz
    $books = $stmt->fetchAll(); // získa dáta
} catch (Exception $e) {
    die('Chyba pri práci s databázou: ' . $e->getMessage());
}
?>

<div class="container my-5 w-50 bg-light p-4">
    <h2 class="mb-4">Tvoj košík</h2>
    <?php if (!empty( $books)): ?>
        <div class="row g-3">
            <?php $total = 0; ?>
            <?php foreach ($books as $item): ?>
                <?php
                $quantity = $item['mnozstvo'] ?? 1; // predvolené množstvo 1
                $itemTotal = $item['cena'] * $quantity;
                $total += $itemTotal;
                ?>
                <div class="col-12">
                    <div class="card cart-item border-0 shadow-sm p-3 d-flex flex-row align-items-center">
                        <img src="<?= $link->asset($item['obrazok']) ?>"
                             alt="<?= $item['nazov'] ?>"
                             class="cart-img me-3">

                        <div class="cart-details flex-grow-1">
                            <h5 class="mb-1"><?= $item['nazov'] ?></h5>
                            <small class="text-muted"><?= $item['autor'] ?></small>
                        </div>

                        <!-- Množstvo -->
                        <div class="d-flex align-items-center me-3">
                            <button class="btn btn-outline-secondary btn-sm me-1" onclick="updateQuantity(<?= $item['nazov'] ?>, -1)">-</button>
                            <input type="text" value="<?= $quantity ?>" readonly class="form-control form-control-sm text-center" style="width: 50px;">
                            <button class="btn btn-outline-secondary btn-sm ms-1" onclick="updateQuantity(<?= $item['nazov'] ?>, 1)">+</button>
                        </div>

                        <!-- Cena za položku -->
                        <div class="cart-price text-end me-3 fw-bold fs-5"><?= $itemTotal ?> €</div>

                        <a href="#" class="btn btn-danger btn-sm">Odstrániť</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Celková suma -->
        <div class="text-end mt-4">
            <div class="d-flex justify-content-end align-items-center">
                <h4 class="me-2 mb-0">Spolu:</h4>
                <h4 class="fw-bold mb-0"><?= $total ?> €</h4>
            </div>

            <a href="#" class="btn btn-danger btn-lg mt-2">Pokračovať k platbe</a>
        </div>

    <?php else: ?>
        <p>Košík je prázdny.</p>
    <?php endif; ?>
</div>

