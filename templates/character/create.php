<?php
/** @var array $systems */
/** @var array $flash */
/** @var string $csrf */
?>
<div class="page-header">
    <h1>Créer un personnage</h1>
</div>

<?php foreach ($flash as $msg): ?>
    <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
        <?= htmlspecialchars($msg['message']) ?>
    </div>
<?php endforeach; ?>

<?php if (empty($systems)): ?>
    <div class="alert alert-warning">
        Aucun système de jeu n'est disponible. Contactez l'administrateur.
    </div>
<?php else: ?>
    <div class="character-create-layout">
        <div class="character-create-form">
            <form method="POST" action="/characters" id="character-form">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="form-group">
                    <label for="name">Nom du personnage</label>
                    <input type="text" id="name" name="name" required
                           minlength="1" maxlength="255"
                           class="form-control" placeholder="Entrez le nom de votre héros">
                </div>

                <div class="form-group">
                    <label for="game_system_id">Système de jeu</label>
                    <select name="game_system_id" id="game_system_id" class="form-control" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($systems as $sys): ?>
                            <option value="<?= $sys['id'] ?>">
                                <?= htmlspecialchars($sys['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Stat rolling section -->
                <div id="stats-section" class="stats-section" style="display:none;">
                    <h3>Caractéristiques</h3>
                    <p class="help-text">
                        Cliquez sur "Lancer les dés" pour générer aléatoirement vos caractéristiques.
                        Les dés peuvent être relancés avant de confirmer la création.
                    </p>

                    <button type="button" id="roll-stats-btn" class="btn btn-secondary">
                        🎲 Lancer les dés
                    </button>

                    <div id="stats-results" class="stats-results" style="display:none;">
                        <!-- Filled by JS -->
                    </div>

                    <div id="stats-hidden-inputs"></div>
                </div>

                <div id="submit-section" style="display:none;">
                    <hr>
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✓ Créer ce personnage
                    </button>
                    <a href="/characters" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>

        <div class="system-info-panel">
            <h3>Description du système de jeu</h3>
            <div id="system-description">
                <p class="text-muted">Sélectionnez un système de jeu pour voir sa description.</p>
            </div>

            <?php foreach ($systems as $sys):
                $cfg = json_decode($sys['config'], true);
            ?>
                <div class="system-info" id="system-info-<?= $sys['id'] ?>" style="display:none;">
                    <p><?= nl2br(htmlspecialchars($sys['description'] ?? '')) ?></p>

                    <h4>Caractéristiques</h4>
                    <ul>
                        <?php foreach ($cfg['stats'] ?? [] as $stat): ?>
                            <li>
                                <strong><?= htmlspecialchars($stat['label']) ?></strong>
                                (<?= htmlspecialchars($stat['roll_formula']) ?>) :
                                <?= htmlspecialchars($stat['description'] ?? '') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($cfg['initial_equipment'])): ?>
                        <h4>Équipement de départ</h4>
                        <ul>
                            <?php foreach ($cfg['initial_equipment'] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const systemSelect = document.getElementById('game_system_id');
    const statsSection = document.getElementById('stats-section');
    const submitSection = document.getElementById('submit-section');
    const rollBtn = document.getElementById('roll-stats-btn');
    const statsResults = document.getElementById('stats-results');
    const hiddenInputs = document.getElementById('stats-hidden-inputs');
    const systemDesc = document.getElementById('system-description');

    systemSelect.addEventListener('change', function () {
        const systemId = this.value;
        // Show/hide system description
        document.querySelectorAll('.system-info').forEach(el => el.style.display = 'none');
        if (systemId) {
            const info = document.getElementById('system-info-' + systemId);
            if (info) {
                info.style.display = 'block';
                systemDesc.style.display = 'none';
            }
            statsSection.style.display = 'block';
        } else {
            statsSection.style.display = 'none';
            submitSection.style.display = 'none';
        }
        statsResults.style.display = 'none';
        hiddenInputs.innerHTML = '';
        submitSection.style.display = 'none';
    });

    rollBtn.addEventListener('click', function () {
        const systemId = systemSelect.value;
        if (!systemId) return;

        rollBtn.disabled = true;
        rollBtn.textContent = '⏳ Lancement...';

        fetch('/characters/roll-stats?system_id=' + systemId)
            .then(r => r.json())
            .then(data => {
                displayStats(data);
                rollBtn.disabled = false;
                rollBtn.textContent = '🎲 Relancer les dés';
                statsResults.style.display = 'block';
                submitSection.style.display = 'block';
            })
            .catch(() => {
                rollBtn.disabled = false;
                rollBtn.textContent = '🎲 Lancer les dés';
                alert('Erreur lors du lancer de dés.');
            });
    });

    function displayStats(data) {
        statsResults.innerHTML = '';
        hiddenInputs.innerHTML = '';

        Object.entries(data).forEach(([statName, stat]) => {
            // Display
            const div = document.createElement('div');
            div.className = 'rolled-stat';
            div.innerHTML =
                '<span class="stat-label">' + escapeHtml(stat.label) + '</span>' +
                '<span class="stat-dice">[' + stat.rolls.join(', ') + ']' +
                (stat.bonus ? ' +' + stat.bonus : '') + '</span>' +
                '<span class="stat-total">' + stat.total + '</span>';
            statsResults.appendChild(div);

            // Hidden input
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'stat_' + statName;
            input.value = stat.total;
            hiddenInputs.appendChild(input);
        });
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }
});
</script>
