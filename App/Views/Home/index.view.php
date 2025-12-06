<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<?php

use Framework\DB\Connection;

$sections = [
        ['id' => 'carouselSeriesBest', 'nazov' => 'Bestsellery'],
        ['id' => 'carouselSeriesNew', 'nazov' => 'Nové vydania'],
        ['id' => 'carouselSeriesUpcoming', 'nazov' => 'Nadchádzajúce vydania'],
];

foreach ($sections as $section):

    $conn = Connection::getInstance();
    $sql = "SELECT id_kniha, nazov, autor, cena, obrazok FROM kniha ORDER BY RAND() LIMIT 12";
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $books = $stmt->fetchAll();
    } catch (Exception $e) {
        die('Chyba pri práci s databázou: ' . $e->getMessage());
    }

    ?>
    <h2 class="mb-1 mt-5 carousel-title fs-1"><?= $section['nazov'] ?></h2>

    <div id="<?= $section['id'] ?>" class="carousel slide bg-light" data-bs-touch="false" data-bs-ride="carousel">
        <div class="carousel-inner mb-2">
            <?php
            $chunks = array_chunk($books, 4);
            foreach ($chunks as $index => $chunk): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> mb-4">
                    <div class="books-wrapper">
                        <div class="row mt-4">
                            <?php foreach ($chunk as $book): ?>
                                <?php $detailUrl = $link->url('Books.detail', ['id' => $book['id_kniha']]); ?>
                                <div class="col-6 col-md-3 mb-1 w-20">
                                    <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-reset d-block h-100">
                                        <div class="card text-center border-0 shadow-sm h-100">
                                            <img src="<?= $link->asset($book['obrazok']) ?>"
                                                 class="card-img-top book-cover mt-3 h-70"
                                                 alt="<?= htmlspecialchars($book['nazov'], ENT_QUOTES) ?>">
                                            <div class="card-body">
                                                <h5 class="card-title mb-1 fw-bold"><?= htmlspecialchars(mb_strimwidth($book['nazov'], 0, 23, '...'), ENT_QUOTES) ?></h5>
                                                <p class="card-subtitle text-muted mb-0"><?= htmlspecialchars($book['autor'], ENT_QUOTES) ?></p>
                                            </div>
                                            <div class="card-footer bg-transparent border-0">
                                                <strong class="book-price"><?= htmlspecialchars($book['cena'], ENT_QUOTES) ?></strong>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#<?= $section['id'] ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#<?= $section['id'] ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

<?php endforeach; ?>