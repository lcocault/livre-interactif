<?php
/** @var array $flash */
/** @var string $csrf */
?>
<div class="page-header">
    <h1>📥 Importer un script SQL</h1>
    <a href="/admin" class="btn btn-secondary">&larr; Admin</a>
</div>

<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<div class="import-layout">
    <!-- Upload file -->
    <section class="import-section">
        <h2>Téléverser un fichier SQL</h2>
        <p>Importez un fichier <code>.sql</code> depuis votre ordinateur.</p>
        <form method="POST" action="/admin/import/file" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="sql_file">Fichier SQL</label>
                <input type="file" id="sql_file" name="sql_file" accept=".sql" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Importer le fichier</button>
        </form>
    </section>

    <!-- Paste SQL -->
    <section class="import-section">
        <h2>Coller un script SQL</h2>
        <p>Collez directement votre script SQL dans la zone ci-dessous.</p>
        <form method="POST" action="/admin/import">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="sql">Script SQL</label>
                <textarea id="sql" name="sql" class="form-control code-area"
                          rows="20" placeholder="-- Collez votre script SQL ici...
INSERT INTO ...
"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Exécuter le script</button>
        </form>
    </section>
</div>

<div class="import-help">
    <h3>Instructions</h3>
    <ul>
        <li>Pour importer un <strong>système de jeu</strong>, exécutez le fichier
            <code>db/seeds/game_system_ehp.sql</code> (fourni dans le dépôt).</li>
        <li>Pour importer un <strong>livre</strong>, utilisez un script SQL suivant la structure
            définie dans <code>db/schema.sql</code>.</li>
        <li>Les instructions <code>DROP</code>, <code>TRUNCATE</code>, <code>ALTER TABLE</code>
            et <code>GRANT</code> sont interdites pour des raisons de sécurité.</li>
        <li>Le script est exécuté dans une transaction : en cas d'erreur, toutes les modifications
            sont annulées.</li>
    </ul>
</div>
