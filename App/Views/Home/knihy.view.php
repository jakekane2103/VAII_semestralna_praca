<?php
/** @var \Framework\Support\LinkGenerator $link */
?>


    <?php
    $books = [
            [
                    'title' => 'Mistborn: The Final Empire',
                    'author' => 'Brandon Sanderson',
                    'description' => 'An enthralling fantasy about revolution, magic, and a world of ash
                          ruled by a seemingly invincible Lord Ruler.',
                    'price' => '€14.99',
                    'image' => 'images/finalEmpire.jpg',
                    'alt' => 'Mistborn cover'
            ],
            [
                    'title' => 'The Lord of the Rings',
                    'author' => 'J.R.R. Tolkien',
                    'description' => 'The first volume of the epic journey that follows Frodo and the
                          Fellowship as they undertake a perilous quest.',
                    'price' => '€18.50',
                    'image' => 'images/lotr.jpg',
                    'alt' => 'Pán prsteňov cover'
            ],
            [
                    'title' => 'The Lies of Locke Lamora',
                    'author' => 'Scott Lynch',
                    'description' => 'A dark, witty tale of con-artists, friendship, and survival in a
                          corrupt city of thieves and nobles.',
                    'price' => '€16.00',
                    'image' => 'images/liesOfLockeLamora.jpg',
                    'alt' => 'Lies of Locke Lamora cover'
            ],
            [
                    'title' => 'The Eye of the World',
                    'author' => 'Robert Jordan',
                    'description' => 'The opening chapter of the legendary Wheel of Time series, following Rand al\'Thor as he is swept into a world of prophecy, danger, and destiny.',
                    'price' => '€16.00',
                    'image' => 'images/eyeOfTheWorld.jpg',
                    'alt' => 'The Eye of the World cover'
            ],
            [
                    'title' => 'The Way of Kings',
                    'author' => 'Brandon Sanderson',
                    'description' => 'An epic tale of war, honor, and mysterious magical storms as several characters struggle to survive on the shattered world of Roshar.',
                    'price' => '€19.99',
                    'image' => 'images/wayOfKings.jpg',
                    'alt' => 'The Way of Kings cover'
            ],
            [
                    'title' => 'The Name of the Wind',
                    'author' => 'Patrick Rothfuss',
                    'description' => 'A beautifully written story following Kvothe, a gifted young man who grows into the most powerful wizard the world has ever seen.',
                    'price' => '€17.50',
                    'image' => 'images/nameOfTheWind.jpg',
                    'alt' => 'The Name of the Wind cover'
            ],


    ];
    ?>

<div class="container-fluid">
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($books as $book): ?>
            <div class="col-md-4 p-0">
                <div class="card h-100 m-0 border-0 shadow-sm">
                    <div class="row g-0 h-100 ">
                        <div class="col-4 h-100 p-0">
                            <img src="<?= $link->asset($book['image']) ?>"
                                 class="img-fluid rounded-start book-cover h-100"
                                 alt="<?= $book['alt'] ?>">
                        </div>
                        <div class="col-8 p-3">
                            <div class="card-body d-flex flex-column p-0">
                                <h5 class="card-title fw-bold"><?= $book['title'] ?></h5>
                                <h6 class="card-subtitle text-muted"><?= $book['author'] ?></h6>
                                <p class="card-text mt-2"><?= $book['description'] ?></p>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <div class="fw-bold fs-5"><?= $book['price'] ?></div>
                                    <a href="#" class="btn btn-primary mr-50">Do košíka</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row mt-3">
        <div class="col text-center">
            <h4>Hunters</h4>
            <div>
                <a href="">Gehrman, The First Hunter</a><br>
                <a href="">Ludwig, The Holy Blade</a><br>
                <a href="">Lady Maria of the Astral Clocktower</a><br><br>
                &copy; 1329-<?= date('Y') ?> University of Yharnam, Faculty of Religion, Science and Cosmology,
                Department of Holy Hunters
            </div>
        </div>
    </div>
</div>
