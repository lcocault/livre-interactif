<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_CONFIG['app']['name']) ?></title>
    <link rel="stylesheet" href="<?= url('css/style.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="<?= url('') ?>" class="site-logo">📖 Livre Interactif</a>
            <nav class="main-nav">
                <a href="<?= url('books') ?>">Bibliothèque</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= url('characters') ?>">Mes Personnages</a>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="<?= url('admin') ?>" class="nav-admin">⚙ Admin</a>
                    <?php endif; ?>
                    <span class="nav-user">
                        <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                    </span>
                    <form method="POST" action="<?= url('logout') ?>" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn-link">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="<?= url('login') ?>">Connexion</a>
                    <a href="<?= url('register') ?>" class="btn-primary-sm">Inscription</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?= $content ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p>Livre Interactif &mdash; Informatisation des <em>Livres Dont Vous Êtes Le Héros</em></p>
        </div>
    </footer>
    <script src="<?= url('js/app.js') ?>"></script>
</body>
</html>
