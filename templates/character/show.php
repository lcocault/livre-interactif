<?php
/** @var array $character */
/** @var array $stats */
/** @var array $inventory */
/** @var array $sessions */
/** @var array $gameSystemConfig */
/** @var array $flash */
/** @var string $csrf */
?>
<div class="character-sheet">
    <div class="character-sheet-header">
        <div>
            <h1><?= htmlspecialchars($character['name']) ?></h1>
            <p class="character-system"><?= htmlspecialchars($character['game_system_name']) ?></p>
            <?php if (!$character['is_alive']): ?>
                <span class="badge badge-danger badge-lg">💀 Personnage décédé</span>
            <?php endif; ?>
        </div>
        <a href="<?= url('characters') ?>" class="btn btn-secondary">&larr; Mes personnages</a>
    </div>

    <?php foreach ($flash as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
            <?= htmlspecialchars($msg['message']) ?>
        </div>
    <?php endforeach; ?>

    <div class="sheet-columns">
        <!-- Left column: stats -->
        <section class="sheet-stats">
            <h2>Caractéristiques</h2>
            <?php if (empty($stats)): ?>
                <p class="empty-state">Aucune caractéristique.</p>
            <?php else: ?>
                <?php foreach ($stats as $stat): ?>
                    <?php
                    $pct = $stat['initial_value'] > 0
                        ? round(($stat['current_value'] / $stat['initial_value']) * 100)
                        : 0;
                    $barClass = $pct > 60 ? 'bar-good' : ($pct > 30 ? 'bar-warn' : 'bar-danger');

                    // Find stat description in game system config
                    $statDesc = '';
                    foreach ($gameSystemConfig['stats'] ?? [] as $cfgStat) {
                        if ($cfgStat['name'] === $stat['stat_name']) {
                            $statDesc = $cfgStat['description'] ?? '';
                            break;
                        }
                    }
                    ?>
                    <div class="stat-block-large">
                        <div class="stat-header">
                            <span class="stat-name"><?= htmlspecialchars($stat['stat_name']) ?></span>
                            <span class="stat-values">
                                <span class="stat-current"><?= $stat['current_value'] ?></span>
                                <span class="stat-sep">/</span>
                                <span class="stat-initial"><?= $stat['initial_value'] ?></span>
                            </span>
                        </div>
                        <div class="stat-bar">
                            <div class="stat-bar-fill <?= $barClass ?>"
                                 style="width: <?= min(100, $pct) ?>%"></div>
                        </div>
                        <?php if ($statDesc): ?>
                            <p class="stat-desc"><?= htmlspecialchars($statDesc) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- Right column: inventory -->
        <section class="sheet-inventory">
            <h2>Équipement &amp; Inventaire</h2>

            <?php if (empty($inventory)): ?>
                <p class="empty-state">Inventaire vide.</p>
            <?php else: ?>
                <ul class="inventory-list-full">
                    <?php foreach ($inventory as $item): ?>
                        <li class="inventory-item">
                            <span class="item-name"><?= htmlspecialchars($item['item_name']) ?></span>
                            <?php if ($item['quantity'] > 1): ?>
                                <span class="item-qty">&times;<?= $item['quantity'] ?></span>
                            <?php endif; ?>
                            <?php if ($item['notes']): ?>
                                <span class="item-notes"><?= htmlspecialchars($item['notes']) ?></span>
                            <?php endif; ?>
                            <?php if ($character['is_alive']): ?>
                                <form method="POST"
                                      action="<?= url('characters/' . $character['id'] . '/inventory/' . $item['id'] . '/remove') ?>"
                                      class="inline-form">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                                    <button type="submit" class="btn-icon btn-danger-sm"
                                            title="Retirer"
                                            onclick="return confirm('Retirer cet objet ?')">✕</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($character['is_alive']): ?>
                <details class="add-item-details">
                    <summary>+ Ajouter un objet</summary>
                    <form method="POST" action="<?= url('characters/' . $character['id'] . '/inventory') ?>"
                          class="add-item-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <div class="form-group">
                            <input type="text" name="item_name" class="form-control"
                                   placeholder="Nom de l'objet" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="notes" class="form-control"
                                   placeholder="Notes (optionnel)">
                        </div>
                        <button type="submit" class="btn btn-secondary btn-sm">Ajouter</button>
                    </form>
                </details>
            <?php endif; ?>
        </section>
    </div>

    <!-- Reading history -->
    <?php if (!empty($sessions)): ?>
        <section class="reading-history">
            <h2>Historique de lecture</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Livre</th>
                        <th>Paragraphe actuel</th>
                        <th>Démarré le</th>
                        <th>Dernière activité</th>
                        <th>Résultat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $sess): ?>
                        <tr>
                            <td><?= htmlspecialchars($sess['book_title']) ?></td>
                            <td>§ <?= $sess['current_paragraph_number'] ?></td>
                            <td><?= date('d/m/Y', strtotime($sess['started_at'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($sess['last_activity_at'])) ?></td>
                            <td>
                                <?php if ($sess['completed_at']): ?>
                                    <?php if ($sess['outcome'] === 'victory'): ?>
                                        <span class="badge badge-success">Victoire</span>
                                    <?php elseif ($sess['outcome'] === 'death'): ?>
                                        <span class="badge badge-danger">Mort</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Abandonné</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-info">En cours</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$sess['completed_at'] && $character['is_alive']): ?>
                                    <a href="<?= url('read/' . $sess['id'] . '/' . $sess['current_paragraph_number']) ?>"
                                       class="btn btn-primary btn-sm">Reprendre</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</div>
