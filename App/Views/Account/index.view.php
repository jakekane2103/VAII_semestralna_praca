<?php

/** @var array $data */
/** @var string|null $success */
/** @var string|null $error */
/** @var \Framework\Support\LinkGenerator $link */

$data = $data ?? [];

$success = $success ?? null;
$error = $error ?? null;

?>

<div class="container mt-4 mb-5" style="max-width: 600px;">
    <div class="card p-4 shadow-sm no-hover">
        <h3 class="mb-4 text-center">Upraviť údaje účtu</h3>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form action="<?= $link->url('Account.update') ?>" method="post" class="account-form">
            <input type="hidden" name="id_zakaznik" value="<?= htmlspecialchars($data['id_zakaznik'] ?? '') ?>">

            <!-- username handled as email -->

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Meno</label>
                    <input type="text" name="meno" class="form-control" required value="<?= htmlspecialchars($data['meno'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Priezvisko</label>
                    <input type="text" name="priezvisko" class="form-control" required value="<?= htmlspecialchars($data['priezvisko'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($data['email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Krajina</label>
                <input type="text" name="krajina" class="form-control" value="<?= htmlspecialchars($data['krajina'] ?? '') ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mesto</label>
                    <input type="text" name="mesto" class="form-control" value="<?= htmlspecialchars($data['mesto'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">PSČ</label>
                    <input type="text" name="psc" class="form-control" value="<?= htmlspecialchars($data['psc'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Číslo domu</label>
                    <input type="text" name="cislo" class="form-control" value="<?= htmlspecialchars($data['cislo'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Ulica</label>
                <input type="text" name="ulica" class="form-control" value="<?= htmlspecialchars($data['ulica'] ?? '') ?>">
            </div>

            <hr>
            <h6 class="mb-3">Zmena hesla (nepovinné)</h6>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password-account" class="form-label">Nové heslo</label>
                    <div class="input-group">
                        <input type="password" name="heslo" class="form-control" id="password-account">
                        <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="password-account">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password-confirm-account" class="form-label">Potvrdiť heslo</label>
                    <div class="input-group">
                        <input type="password" name="heslo_confirm" class="form-control" id="password-confirm-account">
                        <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="password-confirm-account">
                            <i class="bi bi-eye-slash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-danger w-100 mt-2">Uložiť zmeny</button>

            <div class="text-center mt-3">
                <a href="<?= $link->url('home.index') ?>" class="text-decoration-none">Zrušiť a vrátiť sa</a>
            </div>
        </form>
    </div>
</div>
