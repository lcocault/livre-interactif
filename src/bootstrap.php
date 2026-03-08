<?php
/**
 * Application bootstrap: autoloading, configuration, database initialisation.
 */

define('ROOT_DIR', dirname(__DIR__));
define('SRC_DIR',  ROOT_DIR . '/src');
define('TPL_DIR',  ROOT_DIR . '/templates');

// ── PSR-4-style autoloader ───────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    // All classes live under the App\ namespace → src/
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = SRC_DIR . '/' . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// ── Load configuration ───────────────────────────────────────────────────────
$configFile = ROOT_DIR . '/config/config.php';
if (!file_exists($configFile)) {
    // Fallback: try environment variables (useful for CI/CD)
    $config = [
        'db' => [
            'host'     => getenv('DB_HOST')     ?: 'localhost',
            'port'     => (int)(getenv('DB_PORT') ?: 5432),
            'name'     => getenv('DB_NAME')     ?: 'livre_interactif',
            'user'     => getenv('DB_USER')     ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: '',
        ],
        'app' => [
            'name'         => getenv('APP_NAME')       ?: 'Livre Interactif',
            'url'          => getenv('APP_URL')        ?: 'http://localhost',
            'debug'        => (bool)(getenv('APP_DEBUG') ?: false),
            'session_name' => 'livre_interactif_session',
            'secret_key'   => getenv('APP_SECRET')     ?: 'change_me',
        ],
    ];
} else {
    $config = require $configFile;
}

define('APP_CONFIG', $config);

// ── URL helper ───────────────────────────────────────────────────────────────
// Prepends the configured app sub-path so links work under any URL prefix.
// url('login') → '/livres-interactifs/login'  (or '/login' when hosted at root)
function url(string $path = ''): string {
    $base = rtrim(parse_url(APP_CONFIG['app']['url'] ?? '', PHP_URL_PATH) ?: '', '/');
    return $base . '/' . ltrim($path, '/');
}

// ── Start session ────────────────────────────────────────────────────────────
$sessionName = $config['app']['session_name'] ?? 'livre_interactif_session';
session_name($sessionName);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Error reporting ──────────────────────────────────────────────────────────
if ($config['app']['debug'] ?? false) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
