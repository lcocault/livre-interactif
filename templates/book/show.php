<?php
/** @var array $book */
/** @var array $characters */
/** @var array $flash */
/** @var string $csrf */
?>
<div class="book-detail">
    <div class="book-hero">
        <?php if ($book['cover_image']): ?>
            <img src="<?= htmlspecialchars($book['cover_image']) ?>"
                 alt="Couverture"
                 class="book-cover-large">
        <?php else: ?>
            <div class="book-cover-placeholder large">📚</div>
        <?php endif; ?>
        <div class="book-info">
            <h1><?= htmlspecialchars($book['title']) ?></h1>
            <?php if ($book['author']): ?>
                <p class="book-author">par <?= htmlspecialchars($book['author']) ?></p>
            <?php endif; ?>
            <p class="book-system">
                Système de jeu : <strong><?= htmlspecialchars($book['game_system_name']) ?></strong>
            </p>
            <?php if ($book['description']): ?>
                <div class="book-description">
                    <?= nl2br(htmlspecialchars($book['description'])) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach ($flash as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
            <?= htmlspecialchars($msg['message']) ?>
        </div>
    <?php endforeach; ?>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="alert alert-info">
            <a href="<?= url('login') ?>">Connectez-vous</a> ou
            <a href="<?= url('register') ?>">inscrivez-vous</a> pour commencer à lire.
        </div>
    <?php elseif (empty($characters)): ?>
        <div class="alert alert-info">
            Vous n'avez pas encore de personnage compatible avec ce système de jeu.
            <a href="<?= url('characters/create') ?>">Créez-en un</a> pour commencer l'aventure.
        </div>
    <?php else: ?>
        <div class="start-reading">
            <h2>Commencer l'aventure</h2>
                <form method="POST" action="<?= url('books/' . $book['slug'] . '/start') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label for="character_id">Choisir un personnage :</label>
                    <select name="character_id" id="character_id" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($characters as $char): ?>
                            <option value="<?= $char['id'] ?>">
                                <?= htmlspecialchars($char['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Action :</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="action" value="resume" checked>
                            Reprendre / Nouvelle partie
                        </label>
                        <label>
                            <input type="radio" name="action" value="new">
                            Recommencer depuis le début
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Lancer l'aventure !</button>
            </form>
        </div>
    <?php endif; ?>
</div>
