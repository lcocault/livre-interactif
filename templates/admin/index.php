<?php
/** @var array $books */
/** @var array $systems */
/** @var array $flash */
/** @var string $csrf */
?>
<div class="page-header">
    <h1>⚙ Administration</h1>
</div>

<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<div class="admin-nav">
    <a href="/admin/import" class="btn btn-primary">📥 Importer SQL</a>
</div>

<div class="admin-sections">
    <!-- Game systems -->
    <section class="admin-section">
        <h2>Systèmes de jeu (<?= count($systems) ?>)</h2>
        <?php if (empty($systems)): ?>
            <p class="empty-state">Aucun système de jeu. Importez le script SQL des graines.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr><th>Nom</th><th>Slug</th><th>Créé le</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($systems as $sys): ?>
                        <tr>
                            <td><?= htmlspecialchars($sys['name']) ?></td>
                            <td><code><?= htmlspecialchars($sys['slug']) ?></code></td>
                            <td><?= date('d/m/Y', strtotime($sys['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <!-- Books -->
    <section class="admin-section">
        <h2>Livres (<?= count($books) ?>)</h2>
        <?php if (empty($books)): ?>
            <p class="empty-state">Aucun livre. Importez un script SQL de livre.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Système de jeu</th>
                        <th>Publié</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['title']) ?></td>
                            <td><?= htmlspecialchars($book['game_system_name']) ?></td>
                            <td>
                                <?= $book['is_published']
                                    ? '<span class="badge badge-success">Oui</span>'
                                    : '<span class="badge badge-secondary">Non</span>'
                                ?>
                            </td>
                            <td><?= date('d/m/Y', strtotime($book['created_at'])) ?></td>
                            <td>
                                <form method="POST"
                                      action="/admin/books/<?= $book['id'] ?>/toggle-publish"
                                      class="inline-form">
                                    <input type="hidden" name="_csrf"
                                           value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <?= $book['is_published'] ? 'Dépublier' : 'Publier' ?>
                                    </button>
                                </form>
                                <a href="/books/<?= htmlspecialchars($book['slug']) ?>"
                                   class="btn btn-secondary btn-sm">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
