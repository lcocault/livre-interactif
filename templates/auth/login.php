<?php
/** @var array $flash */
/** @var string $csrf */
?>
<div class="auth-form-container">
    <h1>Connexion</h1>

    <?php foreach ($flash as $msg): ?>
        <div class="alert alert-<?= htmlspecialchars($msg['type']) ?>">
            <?= htmlspecialchars($msg['message']) ?>
        </div>
    <?php endforeach; ?>

    <form method="POST" action="/login" class="auth-form">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-group">
            <label for="username">Nom d'utilisateur</label>
            <input type="text" id="username" name="username" required
                   autocomplete="username" class="form-control">
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
    </form>

    <p class="auth-link">Pas encore de compte ? <a href="/register">S'inscrire</a></p>
</div>
