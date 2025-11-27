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
    <script src="<?= $link->asset('js/script.js') ?>"></script>
</head>
<body>
<nav class="navbar navbar-expand-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $link->url('home.index') ?>">
            <!-- Logo symbol and logo name rendered side-by-side (styled via CSS) -->
            <img class="logo logo-symbol" src="<?= $link->asset('images/kaneVeritasLogoSymbol.png') ?>" title="<?= App\Configuration::APP_NAME ?>" alt="Logo symbol">
            <img class="logo logo-name" src="<?= $link->asset('images/kaneVeritasLogoName.png') ?>" title="<?= App\Configuration::APP_NAME ?>" alt="Logo name">
        </a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link" href="<?= $link->url('home.index') ?>">Domov</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= $link->url('home.knihy') ?>">Knihy</a>
            </li>
        </ul>

        <form class="d-flex" role="search" method="GET" action="<?= $link->url('home.knihy') ?>">
            <input class="form-control me-2" type="search" name="q" placeholder="Hľadať knihy" aria-label="Search">
            <button class="btn btn-outline-success" type="submit">Hľadať</button>
        </form>

        <?php if ($auth?->isLogged()) { ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('auth.logout') ?>"><?= $auth?->user?->name ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $link->url('auth.logout') ?>">Wishlist</a>
                </li>

        <?php } else { ?>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= App\Configuration::LOGIN_URL ?>">Prihlásiť</a>
                </li>

        <?php } ?>

            <li class="nav-item">
                <a class="nav-link" href="<?= App\Configuration::LOGIN_URL ?>">Košík</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container-fluid mt-3">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>
</body>
</html>
