<?php

namespace App\Controllers;

use Framework\Core\BaseController;
use Framework\DB\Connection;
use Framework\Http\Request;
use Framework\Http\Responses\Response;

/**
 * Class AdminController
 *
 * This controller manages admin-related actions within the application.It extends the base controller functionality
 * provided by BaseController.
 *
 * @package App\Controllers
 */
class AdminController extends BaseController
{
    /**
     * Authorizes actions in this controller.
     *
     * This method checks if the user is logged in, allowing or denying access to specific actions based
     * on the authentication state.
     *
     * @param string $action The name of the action to authorize.
     * @return bool Returns true if the user is logged in; false otherwise.
     */
    public function authorize(Request $request, string $action): bool
    {
        return $this->app->getAuth()->isLogged();
    }

    /**
     * Displays the index page of the admin panel.
     *
     * This action requires authorization. It returns an HTML response for the admin dashboard or main page.
     *
     * @return \Framework\Http\Responses\Response Returns a response object containing the rendered HTML.
     */
    public function index(Request $request): Response
    {
        // Fetch all books to show in admin overview
        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare("SELECT id_kniha AS id, nazov, autor, cena, obrazok FROM kniha ORDER BY id_kniha DESC");
            $stmt->execute();
            $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $books = [];
        }

        // Read and clear flash message
        $flash = $this->app->getSession()->get('admin_flash');
        $this->app->getSession()->remove('admin_flash');

        return $this->html(['books' => $books, 'flash' => $flash]);
    }

    /**
     * Handle adding a new book (POST).
     */
    public function adminAdd(Request $request): Response
    {
        // Only accept POST
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $nazov = trim((string)$request->value('nazov'));
        $autor = trim((string)$request->value('autor'));
        $seria = trim((string)$request->value('seria')) ?: null;
        $cena = $request->value('cena');
        $obrazok = trim((string)$request->value('obrazok')) ?: null;
        $popis = trim((string)$request->value('popis')) ?: null;

        // Basic validation
        if ($nazov === '' || $autor === '' || $cena === null || $cena === '') {
            // missing required fields -> redirect back
            return $this->redirect($this->url('Admin.index'));
        }

        // Normalize price
        $cena = str_replace(',', '.', (string)$cena);
        if (!is_numeric($cena)) {
            return $this->redirect($this->url('Admin.index'));
        }

        try {
            $conn = Connection::getInstance();
            $sql = "INSERT INTO kniha (nazov, autor, seria, obrazok, popis, cena) VALUES (:nazov, :autor, :seria, :obrazok, :popis, :cena)";
            $stmt = $conn->prepare($sql);
            $ok = $stmt->execute([
                ':nazov' => $nazov,
                ':autor' => $autor,
                ':seria' => $seria,
                ':obrazok' => $obrazok,
                ':popis' => $popis,
                ':cena' => $cena,
            ]);

            if ($ok) {
                $this->app->getSession()->set('admin_flash', 'Kniha bola úspešne pridaná.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Pridanie knihy zlyhalo.');
            }
        } catch (\Exception $e) {
            // In case of DB error, set flash
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri pridávaní knihy.');
        }

        return $this->redirect($this->url('Admin.index'));
    }

    /**
     * Handle updating an existing book (POST). Only provided fields will be updated.
     */
    public function adminUpdate(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $id = $request->value('id_kniha');
        if ($id === null || !ctype_digit((string)$id)) {
            return $this->redirect($this->url('Admin.index'));
        }
        $id = (int)$id;

        $fields = [];
        $params = [':id' => $id];

        $nazov = trim((string)$request->value('nazov'));
        if ($nazov !== '') {
            $fields[] = 'nazov = :nazov';
            $params[':nazov'] = $nazov;
        }

        $autor = trim((string)$request->value('autor'));
        if ($autor !== '') {
            $fields[] = 'autor = :autor';
            $params[':autor'] = $autor;
        }

        $cena = $request->value('cena');
        if ($cena !== null && $cena !== '') {
            $cena = str_replace(',', '.', (string)$cena);
            if (is_numeric($cena)) {
                $fields[] = 'cena = :cena';
                $params[':cena'] = $cena;
            }
        }

        $obrazok = trim((string)$request->value('obrazok'));
        if ($obrazok !== '') {
            $fields[] = 'obrazok = :obrazok';
            $params[':obrazok'] = $obrazok;
        }

        $popis = trim((string)$request->value('popis'));
        if ($popis !== '') {
            $fields[] = 'popis = :popis';
            $params[':popis'] = $popis;
        }

        $seria = trim((string)$request->value('seria'));
        if ($seria !== '') {
            $fields[] = 'seria = :seria';
            $params[':seria'] = $seria;
        }

        if (empty($fields)) {
            // Nothing to update
            $this->app->getSession()->set('admin_flash', 'Žiadne polia na aktualizáciu.');
            return $this->redirect($this->url('Admin.index'));
        }

        $sql = 'UPDATE kniha SET ' . implode(', ', $fields) . ' WHERE id_kniha = :id';

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $this->app->getSession()->set('admin_flash', 'Kniha bola aktualizovaná.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Kniha nebola nájdená alebo žiadne zmeny.');
            }
        } catch (\Exception $e) {
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri aktualizácii knihy.');
        }

        return $this->redirect($this->url('Admin.index'));
    }

    /**
     * Handle deletion of a book (POST).
     */
    public function adminDelete(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $id = $request->value('id_kniha');
        if ($id === null || !ctype_digit((string)$id)) {
            return $this->redirect($this->url('Admin.index'));
        }
        $id = (int)$id;

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('DELETE FROM kniha WHERE id_kniha = :id');
            $stmt->execute([':id' => $id]);
            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $this->app->getSession()->set('admin_flash', 'Kniha bola odstránená.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Kniha neexistovala.');
            }
        } catch (\Exception $e) {
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri odstraňovaní knihy.');
        }

        return $this->redirect($this->url('Admin.index'));
    }
}
