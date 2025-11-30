<?php
/** @var string|null $message */ /**
 * @var \Framework\Support\LinkGenerator $link */ /**
 * @var \Framework\Support\View $view
 */
$view->setLayout('auth');
?>

<div class="container mt-4 mb-5" style="max-width: 600px;">
    <div class="card p-4 shadow-sm">
        <h3 class="mb-4 text-center">Registrácia</h3>

        <form method="post" action="<?= $link->url('auth.register') ?>">

            <!-- Meno -->
            <div class="mb-3">
                <label for="firstName" class="form-label">Meno</label>
                <input type="text" name="name" class="form-control" id="name" required>
            </div>

            <!-- Priezvisko -->
            <div class="mb-3">
                <label for="lastName" class="form-label">Priezvisko</label>
                <input type="text" name="name" class="form-control" id="name" required>
            </div>

            <!-- E-mail -->
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" id="email" required>
            </div>

            <!-- Heslo -->
            <div class="mb-3">
                <label for="password" class="form-label">Heslo</label>
                <div class="input-group">
                    <input type="password" name="password" class="form-control" id="password-reg" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordReg">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Potvrdenie hesla -->
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Potvrdenie hesla</label>
                <div class="input-group">
                    <input type="password" name="password_confirm" class="form-control" id="password-confirm-reg" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmReg">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Newsletter -->
            <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="newsletter" name="newsletter">
                <label class="form-check-label" for="newsletter">
                    Mám záujem dostávať informácie o akciách a novinkách prostredníctvom newslettra
                </label>
            </div>

            <!-- GDPR súhlas (povinný) -->
            <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="gdpr" name="gdpr" required>
                <label class="form-check-label" for="gdpr">
                    Oboznámil som sa s informáciami o ochrane osobných údajov.
                    <a href="<?= $link->url('privacy.policy') ?>" target="_blank">Viac informácií o ochrane osobných údajov.</a> *
                </label>
            </div>

            <button type="submit" name="submit" class="btn btn-danger w-100 mt-2">
                Vytvoriť účet
            </button>

        </form>

        <div class="text-center mt-3">
            <small>Máte už účet?
                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Prihláste sa</a>
            </small>
        </div>
    </div>
</div>

