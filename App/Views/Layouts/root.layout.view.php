<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */
?>
<!DOCTYPE html>
<html lang="sk">
<head>
    <title><?= App\Configuration::APP_NAME ?></title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $link->asset('favicons/kaneVeritasLogoSymbol_212x212.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $link->asset('favicons/kaneVeritasLogoSymbol_212x212.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $link->asset('favicons/kaneVeritasLogoSymbol_212x212.png') ?>">
    <link rel="manifest" href="<?= $link->asset('favicons/site.webmanifest') ?>">
    <link rel="shortcut icon" href="<?= $link->asset('favicons/kaneVeritasLogoSymbol_212x212.png') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
            crossorigin="anonymous"></script>
    <link rel="stylesheet" href="<?= $link->asset('css/styl.css') ?>">
    <link rel="stylesheet" href="<?= $link->asset('css/knihy.css') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script src="<?= $link->asset('js/script.js') ?>"></script>
</head>
<body>
<nav class="navbar navbar-expand-md">
    <div class="container-fluid">

        <!-- Logo -->
        <a class="navbar-brand" href="<?= $link->url('home.index') ?>">
            <img class="logo logo-symbol" src="<?= $link->asset('images/kaneVeritasLogoSymbol.png') ?>" alt="">
            <img class="logo logo-name" src="<?= $link->asset('images/kaneVeritasLogoName.png') ?>" alt="">
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible menu -->
        <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">

            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="<?= $link->url('home.index') ?>">
                        <img src="<?= $link->asset('images/homeIcon2.png') ?>" alt="Domov" class="icon me-1 w-10"> Domov
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="<?= $link->url('home.knihy') ?>">
                        <img src="<?= $link->asset('images/booksIcon.png') ?>" alt="Knihy" class="icon me-1 w-10"> Knihy
                    </a>

                </li>
            </ul>

            <!-- DESKTOP SEARCH BAR -->
            <form class="d-none d-md-flex flex-grow-1 mx-3" role="search"
                  method="GET" action="<?= $link->url('home.knihy') ?>">
                <input class="form-control me-2" type="search" name="q" placeholder="Hľadať knihy">
                <button class="btn btn-outline-success" type="submit">Hľadať</button>
            </form>

            <ul class="navbar-nav">
                <?php if ($auth?->isLogged()) { ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="#">
                            <i class="bi bi-person-fill me-1"></i> <?= $auth?->user?->name ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="#">
                            <i class="bi bi-heart-fill me-1"></i> Wishlist
                        </a>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?= App\Configuration::LOGIN_URL ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Prihlásiť
                        </a>
                    </li>
                <?php } ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="#">
                        <i class="bi bi-cart-fill me-1"></i> Košík
                    </a>
                </li>
            </ul>
        </div>

    </div>

    <!-- MOBILE search bar under navbar -->
    <div class="navbar-search-mobile d-md-none px-3 py-2 w-100">
        <form class="d-flex" role="search" method="GET" action="<?= $link->url('home.knihy') ?>">
            <input class="form-control me-2" type="search" name="q" placeholder="Hľadať knihy">
            <button class="btn btn-success" type="submit">Hľadať</button>
        </form>
    </div>
</nav>



<div class="container-fluid mt-3">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>
</body>
</html>
