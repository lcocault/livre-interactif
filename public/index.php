<?php
/**
 * Front controller — single entry point for all requests.
 */

// Ensure request goes through public/ folder
chdir(__DIR__);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Router;
use App\Controller\HomeController;
use App\Controller\AuthController;
use App\Controller\BookController;
use App\Controller\CharacterController;
use App\Controller\GameController;
use App\Controller\AdminController;

$router = new Router();

// ── Public routes ────────────────────────────────────────────────────────────
$router->get('/', fn() => (new HomeController())->index());

// Auth
$router->get('/login',    fn() => (new AuthController())->loginForm());
$router->post('/login',   fn() => (new AuthController())->login());
$router->get('/register', fn() => (new AuthController())->registerForm());
$router->post('/register',fn() => (new AuthController())->register());
$router->post('/logout',  fn() => (new AuthController())->logout());

// Books
$router->get('/books',                                     fn()      => (new BookController())->index());
$router->get('/books/(?P<slug>[^/]+)',                     fn($p)    => (new BookController())->show($p));
$router->post('/books/(?P<slug>[^/]+)/start',              fn($p)    => (new BookController())->startReading($p));

// Reading
$router->get('/read/(?P<session>\d+)/(?P<paragraph>\d+)',  fn($p)    => (new BookController())->readParagraph($p));
$router->post('/read/(?P<session>\d+)/navigate',           fn($p)    => (new BookController())->navigate($p));

// Combat
$router->post('/combat/(?P<session>\d+)/start',            fn($p)    => (new GameController())->startCombat($p));
$router->post('/combat/(?P<session>\d+)/round',            fn($p)    => (new GameController())->combatRound($p));
$router->post('/combat/(?P<session>\d+)/flee',             fn($p)    => (new GameController())->flee($p));

// Characters
$router->get('/characters',                                fn()      => (new CharacterController())->index());
$router->get('/characters/create',                         fn()      => (new CharacterController())->createForm());
$router->get('/characters/roll-stats',                     fn()      => (new CharacterController())->rollStats());
$router->post('/characters',                               fn()      => (new CharacterController())->store());
$router->get('/characters/(?P<id>\d+)',                    fn($p)    => (new CharacterController())->show($p));
$router->post('/characters/(?P<id>\d+)/inventory',         fn($p)    => (new CharacterController())->addItem($p));
$router->post('/characters/(?P<id>\d+)/inventory/(?P<item_id>\d+)/remove', fn($p) => (new CharacterController())->removeItem($p));

// Admin
$router->get('/admin',                     fn() => (new AdminController())->index());
$router->get('/admin/import',              fn() => (new AdminController())->importForm());
$router->post('/admin/import',             fn() => (new AdminController())->importSql());
$router->post('/admin/import/file',        fn() => (new AdminController())->importSqlFile());
$router->post('/admin/books/(?P<id>\d+)/toggle-publish', fn($p) => (new AdminController())->togglePublish($p));

// ── Dispatch ─────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

if (!$router->dispatch($method, $uri)) {
    http_response_code(404);
    $content = '<div class="alert alert-danger" style="margin:2rem auto;max-width:600px">Page introuvable (404).</div>';
    require TPL_DIR . '/layout.php';
}
