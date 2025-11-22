<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<?php
// Define the series data once and reuse it in loops below. Change images/titles/prices here.
$mistborn = [
    ['img' => 'finalEmpire.jpg', 'title' => 'The Final Empire', 'author' => 'Brandon Sanderson', 'price' => '€12.99'],
    ['img' => 'finalEmpire.jpg', 'title' => 'Well of Ascension', 'author' => 'Brandon Sanderson', 'price' => '€13.49'],
    ['img' => 'finalEmpire.jpg', 'title' => 'Hero of Ages', 'author' => 'Brandon Sanderson', 'price' => '€14.00'],
];

$lotr = [
    ['img' => 'lotr.jpg', 'title' => 'Spoločenstvo prsteňa', 'author' => 'J. R. R. Tolkien', 'price' => '€15.50'],
    ['img' => 'lotr.jpg', 'title' => 'Dve Veže', 'author' => 'J. R. R. Tolkien', 'price' => '€16.00'],
    ['img' => 'lotr.jpg', 'title' => 'Návrat kráľa', 'author' => 'J. R. R. Tolkien', 'price' => '€16.50'],
];

$locke = [
    ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Lies of Locke Lamora', 'author' => 'Scott Lynch', 'price' => '€13.20'],
    ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Red Seas Under Red Skies', 'author' => 'Scott Lynch', 'price' => '€13.80'],
    ['img' => 'liesOfLockeLamora.jpg', 'title' => 'The Republic of Thieves', 'author' => 'Scott Lynch', 'price' => '€14.20'],
];
?>

<div class="container-fluid">

    <!-- Carousel with 3 slides, each slide shows 3 books from a series -->
    <div id="carouselSeries" class="carousel slide" data-bs-touch="false" data-bs-ride="carousel">
        <div class="carousel-inner">

            <!-- Mistborn slide -->
            <div class="carousel-item active">
                <div class="books-wrapper">
                    <div class="row mt-4">
                        <div class="col-12">
                            <h3 class="mb-3">Mistborn</h3>
                        </div>
                        <?php foreach ($mistborn as $book): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 text-center">
                                    <img src="<?= $link->asset('images/' . $book['img']) ?>" class="card-img-top book-cover" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h5>
                                        <p class="card-subtitle text-muted mb-0"><?= htmlspecialchars($book['author'], ENT_QUOTES) ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <strong class="book-price"><?= htmlspecialchars($book['price'], ENT_QUOTES) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- LOTR slide -->
            <div class="carousel-item">
                <div class="books-wrapper">
                    <div class="row mt-4">
                        <div class="col-12">
                            <h3 class="mb-3">Pán prsteňov</h3>
                        </div>
                        <?php foreach ($lotr as $book): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 text-center">
                                    <img src="<?= $link->asset('images/' . $book['img']) ?>" class="card-img-top book-cover" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h5>
                                        <p class="card-subtitle text-muted mb-0"><?= htmlspecialchars($book['author'], ENT_QUOTES) ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <strong class="book-price"><?= htmlspecialchars($book['price'], ENT_QUOTES) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Locke Lamora slide -->
            <div class="carousel-item">
                <div class="books-wrapper">
                    <div class="row mt-4">
                        <div class="col-12">
                            <h3 class="mb-3">Gentleman Bastards</h3>
                        </div>
                        <?php foreach ($locke as $book): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 text-center">
                                    <img src="<?= $link->asset('images/' . $book['img']) ?>" class="card-img-top book-cover" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                    <div class="card-body">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h5>
                                        <p class="card-subtitle text-muted mb-0"><?= htmlspecialchars($book['author'], ENT_QUOTES) ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <strong class="book-price"><?= htmlspecialchars($book['price'], ENT_QUOTES) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div> <!-- .carousel-inner -->

        <button class="carousel-control-prev" type="button" data-bs-target="#carouselSeries" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselSeries" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div> <!-- #carouselSeries -->


    <div class="row mt-3">
        <div class="col text-center">
            <h4>Authors</h4>
            <div>
                <a href="">Artorias the Abysswalker</a><br>
                <a href="">Michael Corleone</a><br>
                <a href="">Geralt of Rivia</a><br><br>
                &copy; 2020-<?= date('Y') ?> University of Žilina, Faculty of Management Science and Informatics,
                Department of Software Technologies
            </div>
        </div>
    </div>

</div> <!-- .container-fluid -->
