<?php

namespace App\Controllers;

use App\Configuration;
use App\Models\User;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AuthController
 *
 * This controller handles authentication actions such as login, logout, and redirection to the login page. It manages
 * user sessions and interactions with the authentication system.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Redirects to the login page.
     *
     * This action serves as the default landing point for the authentication section of the application, directing
     * users to the login URL specified in the configuration.
     *
     * @return \Framework\Http\Responses\Response The response object for the redirection to the login page.
     */
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    /**
     * Authenticates a user and processes the login request.
     *
     * This action handles user login attempts. If the login form is submitted, it attempts to authenticate the user
     * with the provided credentials. Upon successful login, the user is redirected to the admin dashboard.
     * If authentication fails, an error message is displayed on the login page.
     *
     * @return Response The response object which can either redirect on success or render the login view with
     *                  an error message on failure.
     */
    public function login(Request $request): Response
    {
        // If this is not a POST submit, redirect to home — login is handled via the modal
        if (!$request->hasValue('submit')) {
            return $this->redirect($this->url('home.index'));
        }

        $username = (string)$request->value('username');
        $password = (string)$request->value('password');

        if ($this->app->getAuth()->login($username, $password)) {
            // Redirect back to referer so navbar updates; but if referer is an auth page, send to home instead
            $referer = $request->server('HTTP_REFERER');
            if (is_string($referer) && $referer !== '') {
                $isAuthReferer = false;
                try {
                    $query = (string)(parse_url($referer, PHP_URL_QUERY) ?: '');
                    parse_str($query, $qs);
                    if (isset($qs['c']) && strtolower((string)$qs['c']) === 'auth') {
                        $isAuthReferer = true;
                    }

                    $path = (string)(parse_url($referer, PHP_URL_PATH) ?: '');
                    if (!$isAuthReferer && stripos($path, '/auth') !== false) {
                        $isAuthReferer = true;
                    }

                    if (!$isAuthReferer && (
                        stripos($referer, 'auth.login') !== false ||
                        stripos($referer, 'auth.signUp') !== false ||
                        stripos($referer, 'auth.loginModal') !== false
                    )) {
                        $isAuthReferer = true;
                    }
                } catch (\Throwable $e) {
                    // ignore and fall back to safe behavior
                }

                if (!$isAuthReferer) {
                    return $this->redirect($referer);
                }
            }

            return $this->redirect($this->url('home.index'));
        }

        // On failure: store error in session and redirect to home with openLogin to auto-open modal
        $this->app->getSession()->set('auth_login_error', 'Neplatné prihlasovacie údaje. Skúste znova.');
        return $this->redirect($this->url('home.index', ['openLogin' => 1]));
    }

    /**
     * Logs out the current user.
     *
     * This action terminates the user's session and redirects them to a view. It effectively clears any authentication
     * tokens or session data associated with the user.
     *
     * @return Response
     */
    public function logout(Request $request): Response
    {
        $this->app->getAuth()->logout();
        return $this->redirect($this->url("home.index"));
    }

    public function signUp(): Response
    {
        return $this->html();
    }

    /**
     * Handle registration POST from sign-up form.
     */
    public function register(Request $request): Response
    {
        // Only handle form submissions
        if (!$request->hasValue('submit')) {
            return $this->redirect($this->url('auth.signUp'));
        }

        $meno = trim((string)$request->value('meno'));
        $priezvisko = trim((string)$request->value('priezvisko'));
        $email = trim((string)$request->value('email'));
        $password = (string)$request->value('password');
        $passwordConfirm = (string)$request->value('password_confirm');
        $gdpr = $request->value('gdpr');

        // Basic validation
        if ($meno === '' || $priezvisko === '' || $email === '' || $password === '') {
            return $this->html(['message' => 'Vyplňte všetky povinné polia.'], 'signUp');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->html(['message' => 'Neplatná e-mailová adresa.'], 'signUp');
        }

        if ($password !== $passwordConfirm) {
            return $this->html(['message' => 'Heslá sa nezhodujú.'], 'signUp');
        }

        if (strlen($password) < 6) {
            return $this->html(['message' => 'Heslo musí mať aspoň 6 znakov.'], 'signUp');
        }

        if (!$gdpr) {
            return $this->html(['message' => 'Musíte súhlasiť s ochranou osobných údajov.'], 'signUp');
        }

        $emailNormalized = User::normalizeEmail($email);

        try {
            if (User::emailExists($emailNormalized)) {
                return $this->html(['message' => 'E-mail už existuje. Ak máte účet, prihláste sa.'], 'signUp');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $ok = User::createCustomer([
                'meno' => $meno,
                'priezvisko' => $priezvisko,
                'email' => $emailNormalized,
                'passwordHash' => $hash,
            ]);

            if ($ok) {
                // Redirect to login page after registration
                return $this->redirect($this->url('auth.login'));
            }

            return $this->html(['message' => 'Registrácia zlyhala. Skúste to neskôr.'], 'signUp');
        } catch (\PDOException $e) {
            // Look for SQLSTATE codes that indicate unique constraint violation.
            // Common codes: '23000' (MySQL), '23505' (Postgres).
            $sqlState = isset($e->errorInfo[0]) ? $e->errorInfo[0] : $e->getCode();
            if (in_array($sqlState, ['23000', '23505'], true)) {
                return $this->html(['message' => 'E-mail už existuje. Ak máte účet, prihláste sa.'], 'signUp');
            }

            return $this->html(['message' => 'Chyba pri ukladaní do databázy: ' . $e->getMessage()], 'signUp');
        } catch (\Exception $e) {
            return $this->html(['message' => 'Chyba pri ukladaní do databázy: ' . $e->getMessage()], 'signUp');
        }
    }
}
