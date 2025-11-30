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
<body class="d-flex flex-column min-vh-100">
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
                    <a class="nav-link d-flex align-items-center" href="<?= $link->url('books.index') ?>">
                        <img src="<?= $link->asset('images/booksIcon.png') ?>" alt="Knihy" class="icon me-1 w-10"> Knihy
                    </a>

                </li>
            </ul>

            <!-- DESKTOP SEARCH BAR -->
            <form class="d-none d-md-flex flex-grow-1 mx-3" role="search"
                  method="GET" action="<?= $link->url('books.index') ?>">
                <input class="form-control me-2" type="search" name="q" placeholder="Hľadať knihy">
                <button class="btn btn-outline-success" type="submit">Hľadať</button>
            </form>

            <ul class="navbar-nav">
                <?php if ($auth?->isLogged()) { ?>

                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?= $link->url('auth.logout') ?>">
                            <img src="<?= $link->asset('images/iconMan.png') ?>" alt="account" class="icon2 me-1 w-10"> <?= $auth?->user?->name ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="#">
                            <img src="<?= $link->asset('images/wishlistIconRed.png') ?>" alt="wish" class="icon2 me-1 w-10">
                        </a>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center"
                           data-bs-toggle="modal" data-bs-target="#loginModal"
                           href="<?= App\Configuration::LOGIN_URL ?>">
                            <img src="<?= $link->asset('images/iconMan.png') ?>" alt="login" class="icon2 me-1 w-10"> Prihlásiť
                        </a>
                    </li>
                <?php } ?>
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center" href="#">
                        <img src="<?= $link->asset('images/cartIcon.png') ?>" alt="cart" class="icon2 me-1 w-10">
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



<div class="container-fluid mt-3 flex-grow-1">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>


<!-- LOGIN MODAL -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="m-0">Prihlásenie</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="post" action="<?= $link->url('auth.login') ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="text" name="username" class="form-control" id="email">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Heslo</label>

                    <div class="input-group">
                        <input type="password" name="password" class="form-control" id="password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn btn-danger w-100">Prihlásiť sa</button>
            </form>

            <div class="text-center mt-3">
                <small>Nemáte u nás účet?
                    <a href="<?= $link->url('auth.signUp') ?>">Zaregistrujte sa</a>
                </small>
            </div>

        </div>
    </div>
</div>


<footer class="site-footer py-4 mt-auto w-100">
    <div class="container">
        <div class="row text-start">

            <!-- Column 1: Zákaznícka podpora -->
            <div class="col-md-3 mb-3">
                <h5>Zákaznícka podpora</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="footer-link">Poštovné a doprava</a></li>
                    <li><a href="#" class="footer-link">Spôsoby platby</a></li>
                    <li><a href="#" class="footer-link">Najčastejšie otázky (FAQ)</a></li>
                    <li><a href="#" class="footer-link">Reklamačný poriadok</a></li>
                    <li><a href="#" class="footer-link">Obchodné podmienky</a></li>
                    <li><a href="#" class="footer-link">Informácie o ochrane osobných údajov</a></li>
                    <li><a href="#" class="footer-link">Vyhlásenie o prístupnosti</a></li>
                </ul>
            </div>

            <!-- Column 2: Objavujte viac -->
            <div class="col-md-3 mb-3">
                <h5>Objavujte viac</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="footer-link">Autori</a></li>
                    <li><a href="#" class="footer-link">Vydavateľstvá</a></li>
                    <li><a href="#" class="footer-link">Novinky</a></li>
                    <li><a href="#" class="footer-link">Bestsellery</a></li>
                    <li><a href="#" class="footer-link">Predobjednávky</a></li>
                </ul>
            </div>

            <!-- Column 3: O nás -->
            <div class="col-md-3 mb-3">
                <h5>O nás</h5>
                <ul class="list-unstyled">
                    <li><a href="#" class="footer-link">Kontakty</a></li>
                    <li><a href="#" class="footer-link">Rezervácia v kníhkupectve</a></li>
                    <li><a href="#" class="footer-link">Kariéra</a></li>
                </ul>
            </div>

            <!-- Column 4: Newsletter -->
            <div class="col-md-3 mb-3">
                <h5>Newsletter</h5>
                <p>Prihláste sa na odber a neujdú vám žiadne knižné novinky.</p>
                <form class="d-flex" action="#" method="post">
                    <input type="email" class="form-control me-2" placeholder="Váš e-mail" required>
                    <button type="submit" class="btn btn-primary">Prihlásiť</button>
                </form>
            </div>

        </div>

        <!-- Copyright -->
        <div class="text-center text-muted mt-4">
            <img class="footerLogo" src="images/kaneVeritasLogoSymbol.png" alt="logo">
            © 2025 Kane Veritas. Internetové kníhkupectvo. Všetky práva vyhradené.<br>
            Engineered with coffee and too many late nights.
        </div>
    </div>
</footer>

</body>
</html>
