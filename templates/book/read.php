<?php
/** @var array $book */
/** @var array $paragraph */
/** @var array $choices */
/** @var array $encounters */
/** @var array $session */
/** @var array $character */
/** @var array $stats */
/** @var array $statsMap */
/** @var array $inventory */
/** @var array|null $activeCombat */
/** @var array $gameSystemConfig */
/** @var array $flash */
/** @var string $csrf */

$lastCombatResult = $_SESSION['last_combat_result'] ?? null;
unset($_SESSION['last_combat_result']);

$sessionCompleted = !empty($session['completed_at']);
$characterDead    = !$character['is_alive'];
?>

<div class="reading-layout">
    <!-- Side panel: character sheet -->
    <aside class="character-panel">
        <h3><?= htmlspecialchars($character['name']) ?></h3>
        <p class="char-system"><?= htmlspecialchars($character['game_system_name']) ?></p>

        <div class="stats-list">
            <?php foreach ($stats as $stat): ?>
                <?php
                $pct = $stat['initial_value'] > 0
                    ? round(($stat['current_value'] / $stat['initial_value']) * 100)
                    : 0;
                $barClass = $pct > 60 ? 'bar-good' : ($pct > 30 ? 'bar-warn' : 'bar-danger');
                ?>
                <div class="stat-block">
                    <div class="stat-header">
                        <span class="stat-name"><?= htmlspecialchars($stat['stat_name']) ?></span>
                        <span class="stat-value">
                            <?= $stat['current_value'] ?> / <?= $stat['initial_value'] ?>
                        </span>
                    </div>
                    <div class="stat-bar">
                        <div class="stat-bar-fill <?= $barClass ?>"
                             style="width: <?= min(100, $pct) ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <details class="inventory-details">
            <summary>Inventaire (<?= count($inventory) ?> objets)</summary>
            <?php if (empty($inventory)): ?>
                <p class="empty-state">Inventaire vide.</p>
            <?php else: ?>
                <ul class="inventory-list">
                    <?php foreach ($inventory as $item): ?>
                        <li>
                            <?= htmlspecialchars($item['item_name']) ?>
                            <?php if ($item['quantity'] > 1): ?>
                                <span class="badge">&times;<?= $item['quantity'] ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </details>

        <p class="session-info">
            Paragraphe actuel : <strong><?= $session['current_paragraph_number'] ?></strong>
        </p>
    </aside>

    <!-- Main reading area -->
    <article class="reading-main">
        <div class="paragraph-header">
            <span class="paragraph-number">§ <?= $paragraph['number'] ?></span>
            <span class="book-title"><?= htmlspecialchars($book['title']) ?></span>
        </div>

        <?php foreach ($flash as $msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
                <?= htmlspecialchars($msg['message']) ?>
            </div>
        <?php endforeach; ?>

        <!-- Last combat result -->
        <?php if ($lastCombatResult): ?>
            <div class="combat-result">
                <h4>Résultat du dernier assaut</h4>
                <?php if (isset($lastCombatResult['roll'])): ?>
                    <p>🎲 Dés : [<?= implode(', ', $lastCombatResult['dice']) ?>] = <strong><?= $lastCombatResult['roll'] ?></strong></p>
                    <p>Dégâts infligés à la créature : <strong><?= $lastCombatResult['creature_damage'] ?></strong> pts</p>
                    <p>Dégâts reçus : <strong><?= $lastCombatResult['player_damage'] ?></strong> pts</p>
                    <?php if (isset($lastCombatResult['creature_endurance_after'])): ?>
                        <p>Endurance de la créature : <?= $lastCombatResult['creature_endurance_before'] ?> → <strong><?= $lastCombatResult['creature_endurance_after'] ?></strong></p>
                    <?php endif; ?>
                    <?php if (isset($lastCombatResult['player_endurance_after'])): ?>
                        <p>Votre Endurance : <strong><?= $lastCombatResult['player_endurance_after'] ?></strong></p>
                    <?php endif; ?>
                <?php elseif (isset($lastCombatResult['fled'])): ?>
                    <p>🏃 Tentative de fuite — Dés : [<?= implode(', ', $lastCombatResult['dice']) ?>] = <?= $lastCombatResult['roll'] ?></p>
                    <?php if ($lastCombatResult['damage'] > 0): ?>
                        <p class="text-danger">Coup dans le dos ! Vous perdez <?= $lastCombatResult['damage'] ?> points d'Endurance.</p>
                    <?php endif; ?>
                    <?php if ($lastCombatResult['fled']): ?>
                        <p class="text-success">Vous avez réussi à fuir !</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Paragraph text -->
        <div class="paragraph-content">
            <?= nl2br(htmlspecialchars($paragraph['content'])) ?>
        </div>

        <!-- Final paragraph -->
        <?php if ($paragraph['is_final'] || $sessionCompleted): ?>
            <div class="final-paragraph <?= ($paragraph['final_type'] === 'death' || $characterDead) ? 'final-death' : 'final-victory' ?>">
                <?php if ($paragraph['final_type'] === 'death' || $characterDead): ?>
                    <h2>💀 Vous êtes mort</h2>
                    <p>Votre aventure s'arrête ici. Votre personnage a péri au cours de cette quête.</p>
                <?php else: ?>
                    <h2>🏆 Félicitations !</h2>
                    <p>Vous avez accompli votre quête avec succès !</p>
                <?php endif; ?>
                <a href="<?= url('books/' . $book['slug']) ?>" class="btn btn-primary">
                    Retour au livre
                </a>
                <a href="<?= url('') ?>" class="btn btn-secondary">Accueil</a>
            </div>

        <?php elseif ($activeCombat && !$activeCombat['is_completed']): ?>
            <!-- Active combat -->
            <div class="combat-zone" id="combat-zone">
                <h3>⚔ Combat : <?= htmlspecialchars($activeCombat['creature_name']) ?></h3>

                <div class="combat-stats">
                    <div class="combatant">
                        <strong><?= htmlspecialchars($character['name']) ?></strong>
                        <span class="hp">
                            ENDURANCE : <?= $statsMap['ENDURANCE']['current_value'] ?? '?' ?>
                        </span>
                    </div>
                    <div class="vs">VS</div>
                    <div class="combatant">
                        <strong><?= htmlspecialchars($activeCombat['creature_name']) ?></strong>
                        <span class="hp">
                            ENDURANCE : <?= $activeCombat['creature_current_endurance'] ?>
                            / <?= $activeCombat['creature_max_endurance'] ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($activeCombat['combat_table'])): ?>
                    <details class="combat-table-details">
                        <summary>Table de combat</summary>
                        <table class="combat-table">
                            <thead>
                                <tr>
                                    <th>Résultat des dés</th>
                                    <th>Dégâts (vous)</th>
                                    <th>Dégâts (créature)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeCombat['combat_table'] as $row): ?>
                                    <tr>
                                        <td>
                                            <?= $row['min_roll'] === $row['max_roll']
                                                ? $row['min_roll']
                                                : $row['min_roll'] . ' – ' . $row['max_roll'] ?>
                                        </td>
                                        <td class="<?= $row['player_damage'] > 0 ? 'text-danger' : '' ?>">
                                            <?= $row['player_damage'] > 0 ? '-' . $row['player_damage'] : '—' ?>
                                        </td>
                                        <td class="<?= $row['creature_damage'] > 0 ? 'text-success' : '' ?>">
                                            <?= $row['creature_damage'] > 0 ? '-' . $row['creature_damage'] : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </details>
                <?php endif; ?>

                <div class="combat-actions">
                    <form method="POST" action="<?= url('combat/' . $session['id'] . '/round') ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="combat_state_id" value="<?= $activeCombat['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-lg">
                            🎲 Lancer les dés (assaut)
                        </button>
                    </form>

                    <?php if ($activeCombat['flee_allowed']): ?>
                        <form method="POST" action="<?= url('combat/' . $session['id'] . '/flee') ?>">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="combat_state_id" value="<?= $activeCombat['id'] ?>">
                            <button type="submit" class="btn btn-warning"
                                    onclick="return confirm('Voulez-vous fuir le combat ?')">
                                🏃 Fuir
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif (!empty($encounters) && !$activeCombat): ?>
            <!-- Combat encounters available -->
            <?php foreach ($encounters as $enc): ?>
                <div class="encounter-box">
                    <h3>⚔ Rencontre : <?= htmlspecialchars($enc['creature_name']) ?></h3>
                    <p>Endurance : <strong><?= $enc['endurance'] ?></strong></p>
                    <form method="POST" action="<?= url('combat/' . $session['id'] . '/start') ?>">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="encounter_id" value="<?= $enc['id'] ?>">
                        <button type="submit" class="btn btn-danger">⚔ Engager le combat</button>
                    </form>
                </div>
            <?php endforeach; ?>

        <?php elseif (!empty($choices)): ?>
            <!-- Navigation choices -->
            <div class="choices-section">
                <h3>Que décidez-vous ?</h3>
                <div class="choices-list">
                    <?php foreach ($choices as $choice): ?>
                        <form method="POST" action="<?= url('read/' . $session['id'] . '/navigate') ?>">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="target_paragraph"
                                   value="<?= (int)$choice['target_paragraph_number'] ?>">
                            <button type="submit" class="choice-btn">
                                <?= htmlspecialchars($choice['text']) ?>
                                <span class="choice-arrow">→ §<?= (int)$choice['target_paragraph_number'] ?></span>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php else: ?>
            <p class="no-choices">Fin du paragraphe. (Aucun choix disponible — vérifiez les données.)</p>
        <?php endif; ?>
    </article>
</div>
