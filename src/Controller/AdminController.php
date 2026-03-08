<?php

namespace App\Controller;

use App\Model\Book;
use App\Model\GameSystem;
use App\Database;

class AdminController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $flash = $this->getFlash();
        $books   = Book::all();
        $systems = GameSystem::all();
        $this->render('admin/index', compact('books', 'systems', 'flash'));
    }

    // ── SQL Import ────────────────────────────────────────────────────────────

    public function importForm(): void
    {
        $this->requireAdmin();
        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('admin/import', compact('flash', 'csrf'));
    }

    public function importSql(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $sql = $this->input('sql');
        if (empty($sql)) {
            $this->flash('danger', 'Veuillez saisir ou coller un script SQL.');
            $this->redirect('/admin/import');
        }

        // Basic safety: disallow dangerous/destructive statements
        $forbidden = ['DROP ', 'TRUNCATE ', 'DELETE ', 'ALTER TABLE', 'GRANT ', 'REVOKE ', 'CREATE USER', 'DROP USER'];
        foreach ($forbidden as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                $this->flash('danger', 'Le script SQL contient des instructions non autorisées (' . htmlspecialchars($keyword) . ').');
                $this->redirect('/admin/import');
            }
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $db->exec($sql);
            $db->commit();
            $this->flash('success', 'Script SQL importé avec succès.');
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->flash('danger', 'Erreur lors de l\'exécution du script : ' . $e->getMessage());
        }

        $this->redirect('/admin/import');
    }

    /** Upload and execute an SQL file. */
    public function importSqlFile(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('danger', 'Erreur lors du téléversement du fichier.');
            $this->redirect('/admin/import');
        }

        $file = $_FILES['sql_file'];

        // Only accept .sql files
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $this->flash('danger', 'Seuls les fichiers .sql sont acceptés.');
            $this->redirect('/admin/import');
        }

        $sql = file_get_contents($file['tmp_name']);
        if ($sql === false) {
            $this->flash('danger', 'Impossible de lire le fichier.');
            $this->redirect('/admin/import');
        }

        // Reuse the same safety check
        $forbidden = ['DROP ', 'TRUNCATE ', 'DELETE ', 'ALTER TABLE', 'GRANT ', 'REVOKE ', 'CREATE USER', 'DROP USER'];
        foreach ($forbidden as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                $this->flash('danger', 'Le script SQL contient des instructions non autorisées (' . htmlspecialchars($keyword) . ').');
                $this->redirect('/admin/import');
            }
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            $db->exec($sql);
            $db->commit();
            $this->flash('success', 'Fichier SQL "' . htmlspecialchars($file['name']) . '" importé avec succès.');
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->flash('danger', 'Erreur lors de l\'exécution du script : ' . $e->getMessage());
        }

        $this->redirect('/admin/import');
    }

    // ── Book management ───────────────────────────────────────────────────────

    public function togglePublish(array $params): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $bookId = (int)$params['id'];
        $book   = Book::find($bookId);
        if (!$book) {
            $this->notFound('Livre introuvable.');
        }

        $newState = $book['is_published'] ? 'FALSE' : 'TRUE';
        Database::getConnection()->prepare(
            'UPDATE books SET is_published = :state WHERE id = :id'
        )->execute([':state' => $newState, ':id' => $bookId]);

        $label = $book['is_published'] ? 'dépublié' : 'publié';
        $this->flash('success', 'Livre "' . htmlspecialchars($book['title']) . '" ' . $label . '.');
        $this->redirect('/admin');
    }
}
