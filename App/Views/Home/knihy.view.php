<?php
/** @var \Framework\Support\LinkGenerator $link */
?>


<div class="container-fluid">
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="row g-0">
                    <div class="col-4">
                        <img src="<?= $link->asset('images/finalEmpire.jpg') ?>" class="img-fluid rounded-start book-cover"
                             alt="Mistborn cover">
                    </div>
                    <div class="col-8">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">Mistborn: The Final Empire</h5>
                            <h6 class="card-subtitle text-muted">Brandon Sanderson</h6>
                            <p class="card-text mt-2">An enthralling fantasy about revolution, magic, and a world of ash
                                ruled by a seemingly invincible Lord Ruler.</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="fw-bold">€14.99</div>
                                <a href="#" class="btn btn-primary">Do košíka</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="row g-0">
                    <div class="col-4">
                        <img src="<?= $link->asset('images/lotr.jpg') ?>" class="img-fluid rounded-start book-cover"
                             alt="Pán prsteňov cover">
                    </div>
                    <div class="col-8">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">The Lord of the Rings</h5>
                            <h6 class="card-subtitle text-muted">J.R.R. Tolkien</h6>
                            <p class="card-text mt-2">The first volume of the epic journey that follows Frodo and the
                                Fellowship as they undertake a perilous quest.</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="fw-bold">€18.50</div>
                                <a href="#" class="btn btn-primary">Do košíka</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="row g-0">
                    <div class="col-4">
                        <img src="<?= $link->asset('images/liesOfLockeLamora.jpg') ?>" class="img-fluid rounded-start book-cover"
                             alt="Lies of Locke Lamora cover">
                    </div>
                    <div class="col-8">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">The Lies of Locke Lamora</h5>
                            <h6 class="card-subtitle text-muted">Scott Lynch</h6>
                            <p class="card-text mt-2">A dark, witty tale of con-artists, friendship, and survival in a
                                corrupt city of thieves and nobles.</p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <div class="fw-bold">€16.00</div>
                                <a href="#" class="btn btn-primary">Do košíka</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
