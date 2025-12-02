<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<?php
$books = [
        ['img' => 'finalEmpire.jpg', 'title' => 'The Final Empire', 'author' => 'Brandon Sanderson', 'price' => '€12.99'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Well of Ascension', 'author' => 'Brandon Sanderson', 'price' => '€13.49'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Hero of Ages', 'author' => 'Brandon Sanderson', 'price' => '€14.00'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Hero of Ages', 'author' => 'Brandon Sanderson', 'price' => '€14.00'],
        ['img' => 'lotr.jpg', 'title' => 'Spoločenstvo prsteňa', 'author' => 'J. R. R. Tolkien', 'price' => '€15.50'],
        ['img' => 'lotr.jpg', 'title' => 'Dve Veže', 'author' => 'J. R. R. Tolkien', 'price' => '€16.00'],
        ['img' => 'lotr.jpg', 'title' => 'Návrat kráľa', 'author' => 'J. R. R. Tolkien', 'price' => '€16.50'],
        ['img' => 'lotr.jpg', 'title' => 'Návrat kráľa', 'author' => 'J. R. R. Tolkien', 'price' => '€16.50'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Lies of Locke Lamora', 'author' => 'Scott Lynch', 'price' => '€13.20'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Red Seas Under Red Skies', 'author' => 'Scott Lynch', 'price' => '€13.80'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'The Republic of Thieves', 'author' => 'Scott Lynch', 'price' => '€14.20'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'The Republic of Thieves', 'author' => 'Scott Lynch', 'price' => '€14.20'],
];
?>

<?php
$sections = [
        ['id' => 'carouselSeriesBest', 'title' => 'Bestsellery'],
        ['id' => 'carouselSeriesNew', 'title' => 'Nové vydania'],
        ['id' => 'carouselSeriesUpcoming', 'title' => 'Nadchádzajúce vydania'],
];


foreach ($sections as $section): ?>
    <h2 class="mb-1 mt-5 carousel-title fs-1"><?= $section['title'] ?></h2>

    <div id="<?= $section['id'] ?>" class="carousel slide" data-bs-touch="false" data-bs-ride="carousel">
        <div class="carousel-inner mb-2">
            <?php
            $chunks = array_chunk($books, 4); // 4 books per slide
            foreach ($chunks as $index => $chunk): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> mb-4">
                    <div class="books-wrapper">
                        <div class="row mt-4">
                            <?php foreach ($chunk as $book): ?>
                                <div class="col-6 col-md-3 mb-1 w-20">
                                    <div class="card text-center border-0 shadow-sm">
                                        <img src="<?= $link->asset('images/' . $book['img']) ?>"
                                             class="card-img-top book-cover mt-3 h-70"
                                             alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">
                                        <div class="card-body">
                                            <h5 class="card-title mb-1 fw-bold"><?= htmlspecialchars(mb_strimwidth($book['title'], 0, 23, '...'), ENT_QUOTES) ?></h5>
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