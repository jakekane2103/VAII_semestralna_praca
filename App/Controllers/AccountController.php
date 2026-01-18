<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\DB\Connection;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Models\User;

class AccountController extends BaseController
{
    // Require user to be logged in for account actions
    public function authorize(Request $request, string $action): bool
    {
        return $this->app->getAuth() !== null && $this->app->getAuth()->isLogged();
    }

    // Implement BaseController::index - delegate to edit page
    public function index(Request $request): Response
    {
        return $this->edit($request);
    }

    // Show edit form
    public function edit(Request $request): Response
    {
        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        $data = [];

        if ($user && $user->getId() !== null) {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('SELECT id_zakaznik, pouzivatelske_meno, meno, priezvisko, email, krajina, mesto, psc, ulica, cislo, datum_registracie FROM zakaznik WHERE id_zakaznik = :id LIMIT 1');
            $stmt->execute([':id' => $user->getId()]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) {
                $data = $row;
            }
        }

        // Read any flash messages
        $session = $this->app->getSession();
        $success = $session->get('account_success', null);
        $error = $session->get('account_error', null);
        if ($success) { $session->remove('account_success'); }
        if ($error) { $session->remove('account_error'); }

        return $this->html(['data' => $data, 'success' => $success, 'error' => $error]);
    }

    // Handle update POST
    public function update(Request $request): Response
    {
        // Expect a form submit
        if (!$request->hasValue('submit')) {
            return $this->redirect($this->url('Account.index'));
        }

        $auth = $this->app->getAuth();
        $user = $auth?->getUser();
        if (!$user || $user->getId() === null) {
            return $this->redirect($this->url('home.index'));
        }

        $uid = $user->getId();
        $pouz = trim((string)$request->value('pouzivatelske_meno'));
        $meno = trim((string)$request->value('meno'));
        $priez = trim((string)$request->value('priezvisko'));
        $email = trim((string)$request->value('email'));
        $krajina = trim((string)$request->value('krajina')) ?: null;
        $mesto = trim((string)$request->value('mesto')) ?: null;
        $psc = trim((string)$request->value('psc')) ?: null;
        $ulica = trim((string)$request->value('ulica')) ?: null;
        $cislo = trim((string)$request->value('cislo')) ?: null;
        $password = (string)$request->value('heslo');
        $passwordConfirm = (string)$request->value('heslo_confirm');

        // Basic validation
        if ($pouz === '' || $meno === '' || $priez === '' || $email === '') {
            $this->app->getSession()->set('account_error', 'Vyplňte povinné polia.');
            return $this->redirect($this->url('Account.index'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->app->getSession()->set('account_error', 'Neplatný formát e-mailu.');
            return $this->redirect($this->url('Account.index'));
        }

        $conn = Connection::getInstance();
        // Check email uniqueness (exclude current user)
        $check = $conn->prepare('SELECT id_zakaznik FROM zakaznik WHERE email = :email AND id_zakaznik != :id LIMIT 1');
        $check->execute([':email' => $email, ':id' => $uid]);
        if ($check->fetch(\PDO::FETCH_ASSOC)) {
            $this->app->getSession()->set('account_error', 'E-mail je už použitý iným účtom.');
            return $this->redirect($this->url('Account.index'));
        }

        // If password provided, validate
        $passwordHash = null;
        if ($password !== '') {
            if ($password !== $passwordConfirm) {
                $this->app->getSession()->set('account_error', 'Heslá sa nezhodujú.');
                return $this->redirect($this->url('Account.index'));
            }
            if (strlen($password) < 6) {
                $this->app->getSession()->set('account_error', 'Heslo musí mať aspoň 6 znakov.');
                return $this->redirect($this->url('Account.index'));
            }
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        }

        try {
            // Build update SQL with only allowed columns
            $fields = [
                'pouzivatelske_meno' => $pouz,
                'meno' => $meno,
                'priezvisko' => $priez,
                'email' => $email,
                'krajina' => $krajina,
                'mesto' => $mesto,
                'psc' => $psc,
                'ulica' => $ulica,
                'cislo' => $cislo
            ];
            if ($passwordHash !== null) {
                $fields['heslo'] = $passwordHash;
            }

            $setParts = [];
            $params = [':id' => $uid];
            foreach ($fields as $col => $val) {
                $setParts[] = "$col = :$col";
                $params[":$col"] = $val;
            }
            $sql = 'UPDATE zakaznik SET ' . implode(', ', $setParts) . ' WHERE id_zakaznik = :id';
            $stmt = $conn->prepare($sql);
            $ok = $stmt->execute($params);

            if ($ok) {
                $this->app->getSession()->set('account_success', 'Údaje boli úspešne uložené.');

                // Refresh authenticated identity so navbar updates immediately
                try {
                    $auth = $this->app->getAuth();
                    if ($auth) {
                        $identity = $auth->getUser();
                        if ($identity instanceof User) {
                            // Update name and username/email on the identity object
                            // Keep compatibility with existing behavior (name was previously first name)
                            $identity->setName($meno);
                            $identity->setUsername($email);

                            // Persist updated identity into session so getUser() and future requests reflect changes
                            $this->app->getSession()->set('user', $identity);
                        }
                    }
                } catch (\Throwable $e) {
                    // don't block the success flow; optionally log
                    error_log('[Account.update] refresh identity failed: ' . $e->getMessage());
                }

            } else {
                $this->app->getSession()->set('account_error', 'Nastala chyba pri ukladaní údajov.');
            }
        } catch (\Throwable $e) {
            $this->app->getSession()->set('account_error', 'Nastala neočakávaná chyba.');
            error_log('[Account.update] ' . $e->getMessage());
        }

        return $this->redirect($this->url('Account.index'));
    }
}
