<?php

namespace App\Controllers;

use App\Configuration;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;

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
     * @throws Exception If the parameter for the URL generator is invalid throws an exception.
     */
    public function login(Request $request): Response
    {
        // If this is not a POST submit, redirect to home — login is handled via the modal
        if (!$request->hasValue('submit')) {
            return $this->redirect($this->url('home.index'));
        }

        // Handle login POST
        $username = (string)$request->value('username');
        $password = (string)$request->value('password');
        $ok = $this->app->getAuth()->login($username, $password);
        if ($ok) {
            // Redirect back to referer so navbar updates; fallback to home
            $referer = $request->server('HTTP_REFERER');
            if ($referer && is_string($referer) && $referer !== '') {
                return $this->redirect($referer);
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
     * @return ViewResponse The response object that renders the logout view.
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
            $message = 'Vyplňte všetky povinné polia.';
            return $this->html(['message' => $message], 'signUp');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Neplatná e-mailová adresa.';
            return $this->html(['message' => $message], 'signUp');
        }

        if ($password !== $passwordConfirm) {
            $message = 'Heslá sa nezhodujú.';
            return $this->html(['message' => $message], 'signUp');
        }

        if (strlen($password) < 6) {
            $message = 'Heslo musí mať aspoň 6 znakov.';
            return $this->html(['message' => $message], 'signUp');
        }

        if (!$gdpr) {
            $message = 'Musíte súhlasiť s ochranou osobných údajov.';
            return $this->html(['message' => $message], 'signUp');
        }

        // Check for existing email
        try {
            $conn = \Framework\DB\Connection::getInstance();
            $check = $conn->prepare('SELECT id_zakaznik FROM zakaznik WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);
            $exists = $check->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($exists)) {
                $message = 'E-mail už existuje. Ak máte účet, prihláste sa.';
                return $this->html(['message' => $message], 'signUp');
            }

            // Hash password and insert
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $username = $email; // use email as username by default

            $insert = $conn->prepare('INSERT INTO zakaznik (pouzivatelske_meno, meno, priezvisko, email, heslo, datum_registracie) VALUES (:uname, :meno, :priezvisko, :email, :heslo, NOW())');
            $ok = $insert->execute([
                ':uname' => $username,
                ':meno' => $meno,
                ':priezvisko' => $priezvisko,
                ':email' => $email,
                ':heslo' => $hash
            ]);

            if ($ok) {
                // Redirect to login page after registration
                return $this->redirect($this->url('auth.login'));
            }

            $message = 'Registrácia zlyhala. Skúste to neskôr.';
            return $this->html(['message' => $message], 'signUp');

        } catch (\Exception $e) {
            $message = 'Chyba pri ukladaní do databázy: ' . $e->getMessage();
            return $this->html(['message' => $message], 'signUp');
        }
    }
}
