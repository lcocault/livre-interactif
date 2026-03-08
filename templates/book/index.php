<?php
/** @var array $books */
/** @var array $flash */
?>
<div class="page-header">
    <h1>Bibliothèque</h1>
    <p>Choisissez votre prochaine aventure.</p>
</div>

<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<?php if (empty($books)): ?>
    <div class="empty-state">
        <p>Aucun livre disponible pour le moment.</p>
        <?php if (!empty($_SESSION['is_admin'])): ?>
            <a href="<?= url('admin/import') ?>" class="btn btn-primary">Importer un livre</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="books-grid">
        <?php foreach ($books as $book): ?>
            <article class="book-card">
                <?php if ($book['cover_image']): ?>
                    <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                         alt="Couverture de <?= htmlspecialchars($book['title']) ?>"
                         class="book-cover">
                <?php else: ?>
                    <div class="book-cover-placeholder">📚</div>
                <?php endif; ?>
                <div class="book-card-body">
                    <h3><?= htmlspecialchars($book['title']) ?></h3>
                    <?php if ($book['author']): ?>
                        <p class="book-author">par <?= htmlspecialchars($book['author']) ?></p>
                    <?php endif; ?>
                    <p class="book-system">Système : <?= htmlspecialchars($book['game_system_name']) ?></p>
                    <?php if ($book['description']): ?>
                        <p class="book-description"><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                    <?php endif; ?>
                    <a href="<?= url('books/' . $book['slug']) ?>" class="btn btn-primary">
                        Lire ce livre
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
