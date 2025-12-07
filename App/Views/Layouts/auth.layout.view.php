<?php

/** @var string $contentHTML */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var \Framework\Support\LinkGenerator $link */

include __DIR__ . '/../Auth/loginModal.php';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <script src="<?= $link->asset('js/auth.js') ?>"></script>
</head>
<body class="d-flex flex-column min-vh-100">

<?php if ($auth?->isLogged()): ?>
    <script>
        (function () {
            // Compute home URL server-side and compare to current location client-side
            var home = <?= json_encode($link->url('home.index')) ?>;
            try {
                var homeUrl = new URL(home, window.location.origin);
                var curUrl = new URL(window.location.href);
                // Only redirect if we're not already at the home URL (path or query differ)
                if (curUrl.pathname !== homeUrl.pathname || curUrl.search !== homeUrl.search) {
                    window.location.replace(homeUrl.href);
                }
            } catch (e) {
                // Fallback: simple comparison
                if (window.location.href !== home) {
                    window.location.replace(home);
                }
            }
        })();
    </script>
<?php endif; ?>

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

            <ul class="navbar-nav">
                <?php if ($auth?->isLogged()) { ?>

                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?= $link->url('auth.logout') ?>">
                            <img src="<?= $link->asset('images/iconMan.png') ?>" alt="account" class="icon2 me-1 w-10"> <?= $auth?->user?->name ?>
                        </a>
                    </li>
                <?php } else { ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center"
                           data-bs-toggle="modal" data-bs-target="#loginModal"
                           href="<?= $link->url('auth.loginModal') ?>">
                            <img src="<?= $link->asset('images/iconMan.png') ?>" alt="login" class="icon2 me-1 w-10"> Prihlásiť
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>


<div class="container-fluid mt-3 flex-grow-1">
    <div class="web-content">
        <?= $contentHTML ?>
    </div>
</div>

</body>
</html>
