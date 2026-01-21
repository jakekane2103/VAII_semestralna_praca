<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use App\Models\User;

class AccountController extends BaseController
{
    // Require user to be logged in for account actions
    public function authorize(Request $request, string $action): bool
    {
        // Avoid calling getAuth() twice; read once and test
        $auth = $this->app->getAuth();
        return $auth !== null && $auth->isLogged();
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
            // Use model helper to fetch profile
            $row = User::getProfile($user->getId());
            if ($row) {
                $data = $row;
            }
        }

        // Read any flash messages
        $session = $this->app->getSession();
        $success = $session->get('account_success', null);
        $error = $session->get('account_error', null);
        // Remove flash keys unconditionally after reading to simplify logic
        $session->remove('account_success');
        $session->remove('account_error');

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
        $data = [
            'meno' => trim((string)$request->value('meno')),
            'priezvisko' => trim((string)$request->value('priezvisko')),
            'email' => trim((string)$request->value('email')),
            'krajina' => trim((string)$request->value('krajina')) ?: null,
            'mesto' => trim((string)$request->value('mesto')) ?: null,
            'psc' => trim((string)$request->value('psc')) ?: null,
            'ulica' => trim((string)$request->value('ulica')) ?: null,
            'cislo' => trim((string)$request->value('cislo')) ?: null,
            'heslo' => (string)$request->value('heslo'),
            'heslo_confirm' => (string)$request->value('heslo_confirm'),
        ];

        // Delegate validation to model
        $error = User::validateProfile($data, false);
        if ($error !== null) {
            $this->app->getSession()->set('account_error', $error);
            return $this->redirect($this->url('Account.index'));
        }

        // Email uniqueness check via model
        if (User::isEmailTaken($data['email'], $uid)) {
            $this->app->getSession()->set('account_error', 'E-mail je už použitý iným účtom.');
            return $this->redirect($this->url('Account.index'));
        }

        try {
            $ok = User::updateProfile($uid, $data);

            if ($ok) {
                $this->app->getSession()->set('account_success', 'Údaje boli úspešne uložené.');

                // Refresh authenticated identity so navbar updates immediately
                try {
                    $auth = $this->app->getAuth();
                    if ($auth) {
                        $identity = $auth->getUser();
                        if ($identity instanceof User) {
                            // Reload identity from DB to pick up changes
                            // Use the profile row so we can control which piece of the name is used
                            $profile = User::getProfile($uid);
                            if ($profile !== null) {
                                // Keep identity name as first name only (meno). If you prefer full name, use: $profile['meno'] . ' ' . $profile['priezvisko']
                                $identity->setName(trim((string)($profile['meno'] ?? '')));
                                $identity->setUsername((string)($profile['email'] ?? ''));
                                // Persist updated identity into session so getUser() and future requests reflect changes
                                $this->app->getSession()->set('user', $identity);
                            }
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
