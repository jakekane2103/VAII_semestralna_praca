<?php

/** @var array $data */
/** @var string|null $success */
/** @var string|null $error */
/** @var \Framework\Support\LinkGenerator $link */

$data = $data ?? [];

$success = $success ?? null;
$error = $error ?? null;

?>

<div class="container my-5 w-50">
    <h2 class="text-center">Upraviť údaje účtu</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="<?= $link->url('Account.update') ?>" method="post" class="account-form">
        <input type="hidden" name="id_zakaznik" value="<?= htmlspecialchars($data['id_zakaznik'] ?? '') ?>">

        <div class="mb-3">
            <label class="form-label">Používateľské meno</label>
            <input type="text" name="pouzivatelske_meno" class="form-control" required value="<?= htmlspecialchars($data['pouzivatelske_meno'] ?? '') ?>">
        </div>

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
        <h5>Zmena hesla (nepovinné)</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nové heslo</label>
                <input type="password" name="heslo" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Potvrdiť heslo</label>
                <input type="password" name="heslo_confirm" class="form-control">
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" name="submit" class="btn btn-primary">Uložiť zmeny</button>
            <a href="<?= $link->url('home.index') ?>" class="btn btn-secondary ms-2">Zrušiť</a>
        </div>
    </form>
</div>

