<?php
/** @var array $flash */
/** @var string $csrf */
?>
<div class="auth-form-container">
    <h1>Inscription</h1>

    <?php foreach ($flash as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
            <?= htmlspecialchars($msg['message']) ?>
        </div>
    <?php endforeach; ?>

    <form method="POST" action="/register" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required minlength="3" maxlength="100"
                   autocomplete="username" class="form-control">
        </div>

        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required
                   autocomplete="email" class="form-control">
        </div>

        <div class="form-group">
            <label for="password">Mot de passe <small>(8 caractères minimum)</small></label>
            <input type="password" id="password" name="password" required minlength="8"
                   autocomplete="new-password" class="form-control">
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" required
                   autocomplete="new-password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
    </form>

    <p class="auth-link">Déjà un compte ? <a href="/login">Se connecter</a></p>
</div>
