<?php
/** @var array $characters */
/** @var array $flash */
?>
<div class="page-header">
    <h1>Mes Personnages</h1>
    <a href="<?= url('characters/create') ?>" class="btn btn-primary">+ Créer un personnage</a>
</div>

<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<?php if (empty($characters)): ?>
    <div class="empty-state">
        <p>Vous n'avez pas encore de personnage.</p>
        <a href="<?= url('characters/create') ?>" class="btn btn-primary">Créer mon premier personnage</a>
    </div>
<?php else: ?>
    <div class="characters-grid">
        <?php foreach ($characters as $char): ?>
            <div class="character-card <?= $char['is_alive'] ? '' : 'character-dead' ?>">
                <div class="character-card-header">
                    <h3><?= htmlspecialchars($char['name']) ?></h3>
                    <?php if (!$char['is_alive']): ?>
                        <span class="badge badge-danger">💀 Mort</span>
                    <?php else: ?>
                        <span class="badge badge-success">✓ Vivant</span>
                    <?php endif; ?>
                </div>
                <p class="character-system"><?= htmlspecialchars($char['game_system_name']) ?></p>
                <p class="character-date">Créé le <?= date('d/m/Y', strtotime($char['created_at'])) ?></p>
                <a href="<?= url('characters/' . $char['id']) ?>" class="btn btn-secondary btn-sm">
                    Voir la feuille
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
