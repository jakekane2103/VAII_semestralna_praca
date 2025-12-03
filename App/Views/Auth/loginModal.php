<?php
/** @var string|null $message */ /**
 * @var \Framework\Support\LinkGenerator $link */ /**
 * @var \Framework\Support\View $view
 */

// Ensure session is active and read any auth error set by AuthController
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
$authError = $_SESSION['auth_login_error'] ?? null;
if ($authError) {
    // remove it so it doesn't persist
    unset($_SESSION['auth_login_error']);
}

$shouldOpen = (isset($_GET['openLogin']) && $_GET['openLogin'] == '1') || $authError !== null;
?>

    <!-- LOGIN MODAL -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="m-0">Prihlásenie</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <?php if ($authError): ?>
                <div class="alert alert-danger mx-3" role="alert">
                    <?= htmlspecialchars($authError, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $link->url('auth.login', [], true) ?>">
                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="text" name="username" class="form-control" id="email">
                </div>

                <div class="mb-3">
                    <label for="password-login" class="form-label">Heslo</label>
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

<?php if ($shouldOpen): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        try {
            var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
            loginModal.show();
        } catch (e) {
            // bootstrap not available or other error
            console.error(e);
        }
    });
</script>
<?php endif; ?>
