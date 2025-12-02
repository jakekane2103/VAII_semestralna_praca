<?php
/** @var \Framework\Support\LinkGenerator $link */

    use Framework\DB\Connection;

    $conn = Connection::getInstance();
    $sql = "SELECT * FROM kniha";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute(); // spustí dotaz
        $books = $stmt->fetchAll(); // získa dáta
    } catch (Exception $e) {
        die('Chyba pri práci s databázou: ' . $e->getMessage());
    }

?>


<div class="container-fluid">
    <?php for ($i = 0; $i < 3; $i++): ?>
    <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
        <?php foreach ($books as $book): ?>
            <div class="col-md-4 p-0">
                <div class="card h-100 m-0 border-0 shadow-sm">
                    <div class="row g-0 h-100 ">
                        <div class="col-4 h-100 p-0">
                            <img src="<?= $link->asset($book['obrazok']) ?>"
                                 class="img-fluid rounded-start book-cover h-100"
                                 alt="<?= $book['nazov'] ?>">
                        </div>
                        <div class="col-8 p-3 d-flex flex-column">
                            <div class="card-body d-flex flex-column p-0 flex-grow-1">
                                <h5 class="card-title fw-bold"><?= $book['nazov'] ?></h5>
                                <h6 class="card-subtitle text-muted"><?= $book['autor'] ?></h6>
                                <p class="card-text mt-2"><?= $book['popis'] ?></p>

                                <!-- Footer s cenou a tlačidlom -->
                                <div class="book-footer mt-auto d-flex justify-content-between align-items-center">
                                    <div class="book-price fw-bold fs-5"><?= $book['cena'] ?> €</div>
                                    <a href="#" class="btn btn-primary">Do košíka</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endfor; ?>
</div>
