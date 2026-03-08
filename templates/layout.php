<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? APP_CONFIG['app']['name']) ?></title>
    <?php
    // Compute base path from configured app URL (allows hosting under a subpath)
    $basePath = parse_url(APP_CONFIG['app']['url'] ?? '', PHP_URL_PATH) ?: '';
    $basePath = rtrim($basePath, '/'); // e.g. "" or "/livres-interactifs"
    function asset_path(string $path): string {
        global $basePath;
        return ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');
    }
    function path_for(string $path): string {
        global $basePath;
        $p = trim($path, '/');
        if ($p === '') {
            return $basePath === '' ? '/' : $basePath;
        }
        return ($basePath === '' ? '' : $basePath) . '/' . $p;
    }
    ?>
    <link rel="stylesheet" href="<?= asset_path('css/style.css') ?>">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <a href="<?= path_for('') ?>" class="site-logo">📖 Livre Interactif</a>
            <nav class="main-nav">
                <a href="<?= path_for('books') ?>">Bibliothèque</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?= path_for('characters') ?>">Mes Personnages</a>
                    <?php if (!empty($_SESSION['is_admin'])): ?>
                        <a href="<?= path_for('admin') ?>" class="nav-admin">⚙ Admin</a>
                    <?php endif; ?>
                    <span class="nav-user">
                        <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
                    </span>
                    <form method="POST" action="<?= path_for('logout') ?>" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn-link">Déconnexion</button>
                    </form>
                <?php else: ?>
                    <a href="<?= path_for('login') ?>">Connexion</a>
                    <a href="<?= path_for('register') ?>" class="btn-primary-sm">Inscription</a>
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
    <script src="<?= asset_path('js/app.js') ?>"></script>
</body>
</html>
