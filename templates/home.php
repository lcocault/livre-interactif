<?php
/** @var array $flash */
/** @var array $books */
/** @var array $characters */
?>
<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<section class="hero">
    <h1>Livres Dont Vous Êtes Le Héros</h1>
    <p class="hero-subtitle">
        Plongez dans des aventures interactives. Chaque décision compte.
        Votre courage, votre Habileté et votre Psi seront mis à rude épreuve.
    </p>
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="hero-actions">
            <a href="<?= url('register') ?>" class="btn btn-primary">Commencer l'aventure</a>
            <a href="<?= url('login') ?>" class="btn btn-secondary">Se connecter</a>
        </div>
    <?php endif; ?>
</section>

<section class="books-section">
    <h2>Aventures disponibles</h2>
    <?php if (empty($books)): ?>
        <p class="empty-state">Aucun livre disponible pour l'instant.</p>
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
                        <p class="book-system">
                            Système : <?= htmlspecialchars($book['game_system_name']) ?>
                        </p>
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
</section>

<?php if (isset($_SESSION['user_id']) && !empty($characters)): ?>
<section class="characters-section">
    <h2>Mes personnages</h2>
    <div class="characters-list">
        <?php foreach ($characters as $char): ?>
            <div class="character-card <?= $char['is_alive'] ? '' : 'character-dead' ?>">
                <strong><?= htmlspecialchars($char['name']) ?></strong>
                <span class="character-system"><?= htmlspecialchars($char['game_system_name']) ?></span>
                <?php if (!$char['is_alive']): ?>
                    <span class="badge badge-danger">Mort</span>
                <?php endif; ?>
                <a href="<?= url('characters/' . $char['id']) ?>" class="btn btn-secondary btn-sm">Feuille</a>
            </div>
        <?php endforeach; ?>
        <a href="<?= url('characters/create') ?>" class="btn btn-primary">+ Nouveau personnage</a>
    </div>
</section>
<?php endif; ?>
