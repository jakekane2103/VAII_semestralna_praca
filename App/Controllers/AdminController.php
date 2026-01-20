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
        // Fetch all books to show in admin overview and the list of series
        try {
            $conn = Connection::getInstance();
            // Join with serie table to get series name (if any)
            $stmt = $conn->prepare("SELECT b.id_kniha AS id, b.nazov, b.autor, b.cena, b.obrazok, b.series_id, s.name AS series_name, b.popis FROM kniha b LEFT JOIN serie s ON b.series_id = s.id ORDER BY b.id_kniha DESC");
            $stmt->execute();
            $books = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Fetch all series for dropdowns and overview including count of books
            $sstmt = $conn->prepare('SELECT s.id, s.name, COUNT(k.id_kniha) AS count FROM serie s LEFT JOIN kniha k ON k.series_id = s.id GROUP BY s.id, s.name ORDER BY s.name');
            $sstmt->execute();
            $series = $sstmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $books = [];
            $series = [];
        }

        // Read and clear flash message
        $flash = $this->app->getSession()->get('admin_flash');
        $this->app->getSession()->remove('admin_flash');

        // Fetch a random welcome message from the witWisdom table (fall back to the original line on error)
        $defaultWelcome = 'Správa kníh: pridávajte nové tituly, aktualizujte existujúce alebo ich odstraňujte.';
        $welcome = $defaultWelcome;
        try {
            $conn = Connection::getInstance();
            $wstmt = $conn->prepare('SELECT line FROM witWisdom ORDER BY RAND() LIMIT 1');
            $wstmt->execute();
            $row = $wstmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && isset($row['line']) && trim($row['line']) !== '') {
                $welcome = $row['line'];
            }
        } catch (\Exception $e) {
            // ignore and keep default welcome
            $welcome = $defaultWelcome;
        }

        return $this->html(['books' => $books, 'series' => $series, 'flash' => $flash, 'welcome' => $welcome]);
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
        $seriesId = $request->value('series_id');
        $newSeriesName = trim((string)$request->value('series_name_new')) ?: null;
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

        // Determine series_id: create new series if requested
        $seriesIdToStore = null;
        try {
            $conn = Connection::getInstance();
            if ($seriesId === 'new' && $newSeriesName) {
                $istmt = $conn->prepare('INSERT INTO serie (name) VALUES (:name)');
                $istmt->execute([':name' => $newSeriesName]);
                $seriesIdToStore = (int)$conn->lastInsertId();
            } elseif ($seriesId !== null && $seriesId !== '' && ctype_digit((string)$seriesId)) {
                $seriesIdToStore = (int)$seriesId;
            } else {
                $seriesIdToStore = null;
            }
        } catch (\Exception $e) {
            // ignore series creation error and proceed with null
            $seriesIdToStore = null;
        }

        try {
            $conn = Connection::getInstance();
            $sql = "INSERT INTO kniha (nazov, autor, series_id, obrazok, popis, cena) VALUES (:nazov, :autor, :series_id, :obrazok, :popis, :cena)";
            $stmt = $conn->prepare($sql);
            $ok = $stmt->execute([
                ':nazov' => $nazov,
                ':autor' => $autor,
                ':series_id' => $seriesIdToStore,
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

        // Handle series update: either existing series_id, new series, or empty -> no change
        $seriesId = $request->value('series_id');
        $newSeriesName = trim((string)$request->value('series_name_new')) ?: null;
        if ($seriesId !== null && $seriesId !== '') {
            try {
                $conn = Connection::getInstance();
                if ($seriesId === 'new' && $newSeriesName) {
                    $istmt = $conn->prepare('INSERT INTO serie (name) VALUES (:name)');
                    $istmt->execute([':name' => $newSeriesName]);
                    $newId = (int)$conn->lastInsertId();
                    $fields[] = 'series_id = :series_id';
                    $params[':series_id'] = $newId;
                } elseif (ctype_digit((string)$seriesId)) {
                    $fields[] = 'series_id = :series_id';
                    $params[':series_id'] = (int)$seriesId;
                } elseif ($seriesId === 'none') {
                    // Explicitly clear the series
                    $fields[] = 'series_id = NULL';
                }
            } catch (\Exception $e) {
                // ignore series creation errors
            }
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

        // Accept both id_kniha (default) and id (used when deleting a series or if JS switched names)
        $id = $request->value('id_kniha');
        if ($id === null) {
            $id = $request->value('id');
        }

        if ($id === null || !ctype_digit((string)$id)) {
            $this->app->getSession()->set('admin_flash', 'Neplatné ID knihy.');
            return $this->redirect($this->url('Admin.index'));
        }
        $id = (int)$id;

        // New logic: prevent deletion if book is present in finalized orders; otherwise remove cart/wishlist references first
        try {
            $conn = Connection::getInstance();

            // If book exists in order items, don't allow deletion
            try {
                $ostmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM polozkaObjednavky WHERE id_kniha = :id');
                $ostmt->execute([':id' => $id]);
                $orow = $ostmt->fetch(\PDO::FETCH_ASSOC);
                $orderCount = isset($orow['cnt']) ? (int)$orow['cnt'] : 0;
            } catch (\Exception $e) {
                $orderCount = 0; // On error, be conservative and allow further checks to proceed to catch DB errors later
            }

            if ($orderCount > 0) {
                $this->app->getSession()->set('admin_flash', 'Knihu nie je možné odstrániť — je použitá v existujúcich objednávkach.');
                return $this->redirect($this->url('Admin.index'));
            }

            // Proceed: remove references from cart and wishlist, then delete the book, all in a transaction
            $conn->beginTransaction();
            try {
                // Remove from carts
                $d1 = $conn->prepare('DELETE FROM kosikKniha WHERE id_kniha = :id');
                $d1->execute([':id' => $id]);

                // Remove from wishlists
                $d2 = $conn->prepare('DELETE FROM wishlistKniha WHERE id_kniha = :id');
                $d2->execute([':id' => $id]);

                // Finally delete the book
                $d3 = $conn->prepare('DELETE FROM kniha WHERE id_kniha = :id');
                $d3->execute([':id' => $id]);
                $affected = $d3->rowCount();

                $conn->commit();

                if ($affected > 0) {
                    $this->app->getSession()->set('admin_flash', 'Kniha bola odstránená (s vyčistenými košíkmi a wishlistami).');
                } else {
                    $this->app->getSession()->set('admin_flash', 'Kniha neexistovala.');
                }
            } catch (\Exception $inner) {
                try { $conn->rollBack(); } catch (\Exception $rb) { /* ignore */ }
                throw $inner;
            }

        } catch (\PDOException $e) {
            // Detect foreign key constraint (MySQL error 1451 / SQLSTATE 23000) and show a helpful message
            $sqlState = $e->getCode();
            $mysqlErrNo = null;
            try {
                $info = $e->errorInfo ?? null;
                if (is_array($info) && isset($info[1])) $mysqlErrNo = (int)$info[1];
            } catch (\Throwable $t) { /* ignore */ }

            // Log detailed exception for debugging
            try {
                $logDir = __DIR__ . '/../../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . '/admin_delete.log';
                $msg = sprintf("[%s] PDOException deleting book id=%s: SQLSTATE=%s, MySQLErr=%s, message=%s in %s:%s\n",
                    date('c'), var_export($id, true), var_export($sqlState, true), var_export($mysqlErrNo, true), $e->getMessage(), $e->getFile(), $e->getLine());
                @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
            } catch (\Exception $inner) {
                error_log('AdminController::adminDelete logging failed: ' . $inner->getMessage());
            }

            if ($sqlState === '23000' || $mysqlErrNo === 1451) {
                // FK constraint prevents deletion
                $this->app->getSession()->set('admin_flash', 'Knihu nie je možné odstrániť — je použitá v objednávkach/košíku alebo v wishliste. Odstráňte najprv súvisiace položky.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Chyba databázy pri odstraňovaní knihy. (podrobnosti v logs/admin_delete.log)');
            }
        } catch (\Exception $e) {
            // Generic exception logging
            try {
                $logDir = __DIR__ . '/../../logs';
                if (!is_dir($logDir)) {
                    @mkdir($logDir, 0755, true);
                }
                $logFile = $logDir . '/admin_delete.log';
                $msg = sprintf("[%s] Exception deleting book id=%s: %s in %s:%s\n", date('c'), var_export($id, true), $e->getMessage(), $e->getFile(), $e->getLine());
                @file_put_contents($logFile, $msg, FILE_APPEND | LOCK_EX);
            } catch (\Exception $inner) { error_log('AdminController::adminDelete logging failed: ' . $inner->getMessage()); }

            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri odstraňovaní knihy. (podrobnosti v logs/admin_delete.log)');
        }

        return $this->redirect($this->url('Admin.index'));
    }

    /**
     * Handle adding a new series (POST).
     */
    public function seriesAdd(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $name = trim((string)$request->value('name'));
        if ($name === '') {
            $this->app->getSession()->set('admin_flash', 'Názov série nemožno nechať prázdny.');
            return $this->redirect($this->url('Admin.index'));
        }

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('INSERT INTO serie (name) VALUES (:name)');
            $ok = $stmt->execute([':name' => $name]);
            if ($ok) {
                $this->app->getSession()->set('admin_flash', 'Séria bola pridaná.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Pridanie série zlyhalo.');
            }
        } catch (\Exception $e) {
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri pridávaní série.');
        }

        return $this->redirect($this->url('Admin.index'));
    }

    /**
     * Handle editing an existing series (POST).
     */
    public function seriesEdit(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $id = $request->value('id');
        $name = trim((string)$request->value('name'));
        if ($id === null || !ctype_digit((string)$id) || $name === '') {
            $this->app->getSession()->set('admin_flash', 'Neplatné údaje pre úpravu série.');
            return $this->redirect($this->url('Admin.index'));
        }
        $id = (int)$id;

        try {
            $conn = Connection::getInstance();
            $stmt = $conn->prepare('UPDATE serie SET name = :name WHERE id = :id');
            $stmt->execute([':name' => $name, ':id' => $id]);
            if ($stmt->rowCount() > 0) {
                $this->app->getSession()->set('admin_flash', 'Séria bola upravená.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Séria nebola nájdená alebo žiadne zmeny.');
            }
        } catch (\Exception $e) {
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri úprave série.');
        }

        return $this->redirect($this->url('Admin.index'));
    }

    /**
     * Handle deletion of a series (POST).
     * If deletion fails due to foreign key constraints, an informative message will be shown.
     */
    public function seriesDelete(Request $request): Response
    {
        if (!$request->isPost()) {
            return $this->redirect($this->url('Admin.index'));
        }

        $id = $request->value('id');
        if ($id === null || !ctype_digit((string)$id)) {
            $this->app->getSession()->set('admin_flash', 'Neplatné ID série.');
            return $this->redirect($this->url('Admin.index'));
        }
        $id = (int)$id;

        try {
            $conn = Connection::getInstance();

            // Guard: don't allow deleting a series that still has books assigned
            $cstmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM kniha WHERE series_id = :id');
            $cstmt->execute([':id' => $id]);
            $crow = $cstmt->fetch(\PDO::FETCH_ASSOC);
            $count = isset($crow['cnt']) ? (int)$crow['cnt'] : 0;
            if ($count > 0) {
                $this->app->getSession()->set('admin_flash', 'Sériu nie je možné odstrániť — existujú knihy priradené k tejto sérii. Najprv ich presuňte alebo odstráňte.');
                return $this->redirect($this->url('Admin.index'));
            }

            // Safe to delete
            $stmt = $conn->prepare('DELETE FROM serie WHERE id = :id');
            $stmt->execute([':id' => $id]);
            if ($stmt->rowCount() > 0) {
                $this->app->getSession()->set('admin_flash', 'Séria bola odstránená.');
            } else {
                $this->app->getSession()->set('admin_flash', 'Séria neexistovala.');
            }
        } catch (\PDOException $e) {
            // Likely foreign key constraint or DB error: inform the admin
            $this->app->getSession()->set('admin_flash', 'Sériu nie je možné odstrániť — existujú knihy priradené k tejto sérii. Najprv ich presuňte alebo odstráňte.');
        } catch (\Exception $e) {
            $this->app->getSession()->set('admin_flash', 'Chyba databázy pri odstraňovaní série.');
        }

        return $this->redirect($this->url('Admin.index'));
    }
}
