<?php
/** @var string|null $message */ /**
 * @var \Framework\Support\LinkGenerator $link */ /**
 * @var \Framework\Support\View $view
 */
?>

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
                        <input type="password" name="password" class="form-control" id="password-login">
                        <button class="btn btn-outline-secondary" type="button"
                                data-toggle="password" data-target="password-login">
                            <i class="bi bi-eye-slash"></i>
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
<?php
