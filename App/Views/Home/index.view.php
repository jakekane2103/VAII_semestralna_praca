<?php

/** @var \Framework\Support\LinkGenerator $link */
?>

<?php
$mistborn = [
        ['img' => 'finalEmpire.jpg', 'title' => 'The Final Empire', 'author' => 'Brandon Sanderson', 'price' => '€12.99'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Well of Ascension', 'author' => 'Brandon Sanderson', 'price' => '€13.49'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Hero of Ages', 'author' => 'Brandon Sanderson', 'price' => '€14.00'],
        ['img' => 'finalEmpire.jpg', 'title' => 'Hero of Ages', 'author' => 'Brandon Sanderson', 'price' => '€14.00'],
];

$lotr = [
        ['img' => 'lotr.jpg', 'title' => 'Spoločenstvo prsteňa', 'author' => 'J. R. R. Tolkien', 'price' => '€15.50'],
        ['img' => 'lotr.jpg', 'title' => 'Dve Veže', 'author' => 'J. R. R. Tolkien', 'price' => '€16.00'],
        ['img' => 'lotr.jpg', 'title' => 'Návrat kráľa', 'author' => 'J. R. R. Tolkien', 'price' => '€16.50'],
        ['img' => 'lotr.jpg', 'title' => 'Návrat kráľa', 'author' => 'J. R. R. Tolkien', 'price' => '€16.50'],
];

$locke = [
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Lies of Locke Lamora', 'author' => 'Scott Lynch', 'price' => '€13.20'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'Red Seas Under Red Skies', 'author' => 'Scott Lynch', 'price' => '€13.80'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'The Republic of Thieves', 'author' => 'Scott Lynch', 'price' => '€14.20'],
        ['img' => 'liesOfLockeLamora.jpg', 'title' => 'The Republic of Thieves', 'author' => 'Scott Lynch', 'price' => '€14.20'],
];

$books = [$mistborn, $lotr, $locke];
?>

<div class="container-fluid">

    <h2 class="mb-3 carousel-title fs-2">Bestsellery</h2>

    <div id="carouselSeries" class="carousel slide" data-bs-touch="false" data-bs-ride="carousel">
        <div class="carousel-inner">

            <?php
            $first = true; // označí prvý slide
            foreach ($books as $series):
                ?>
                <div class="carousel-item <?= $first ? 'active' : '' ?>">
                    <?php $first = false; ?>

                    <div class="books-wrapper">
                        <div class="row mt-4">
                            <?php foreach ($series as $book): ?>
                                <div class="col-6 col-md-3 mb-3">
                                <div class="card h-20 text-center border-0 shadow-sm">
                                        <img src="<?= $link->asset('images/' . $book['img']) ?>"
                                             class="card-img-top book-cover h-60"
                                             alt="<?= htmlspecialchars($book['title'], ENT_QUOTES) ?>">

                                        <div class="card-body">
                                            <h5 class="card-title mb-1 fw-bold"><?= htmlspecialchars($book['title'], ENT_QUOTES) ?></h5>
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

        <button class="carousel-control-prev" type="button" data-bs-target="#carouselSeries" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselSeries" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
    <script
            const carousel = document.querySelector('#carouselSeries');
            const nextBtn = carousel.querySelector('.carousel-control-next');
            const prevBtn = carousel.querySelector('.carousel-control-prev');
            const inner = carousel.querySelector('.carousel-inner');

            const itemWidth = inner.querySelector('.carousel-item').offsetWidth;

            nextBtn.addEventListener('click', () => {
        inner.scrollBy({ left: itemWidth, behavior: 'smooth' });
        });

        prevBtn.addEventListener('click', () => {
        inner.scrollBy({ left: -itemWidth, behavior: 'smooth' });
        });

    </script>

</div>

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
