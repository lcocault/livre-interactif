<?php

namespace App\Controller;

/**
 * Base controller with view rendering and helpers.
 */
abstract class Controller
{
    /** Render a template file, passing variables into its scope. */
    protected function render(string $template, array $vars = [], ?string $layout = 'layout'): void
    {
        extract($vars, EXTR_SKIP);

        // Capture the inner template
        ob_start();
        require TPL_DIR . '/' . $template . '.php';
        $content = ob_get_clean();

        if ($layout !== null) {
            require TPL_DIR . '/' . $layout . '.php';
        } else {
            echo $content;
        }
    }

    /** Redirect to a URL and stop execution. */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /** Send a 404 response and stop execution. */
    protected function notFound(string $message = 'Page introuvable.'): void
    {
        http_response_code(404);
        $content = '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>';
        require TPL_DIR . '/layout.php';
        exit;
    }

    /** Return the currently logged-in user id (or null). */
    protected function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /** Check if the current user is logged in (redirect to /login if not). */
    protected function requireAuth(): void
    {
        if ($this->userId() === null) {
            $this->redirect('/login');
        }
    }

    /** Check if the current user is admin (redirect to / if not). */
    protected function requireAdmin(): void
    {
        $this->requireAuth();
        if (empty($_SESSION['is_admin'])) {
            $this->redirect('/');
        }
    }

    /** Generate a CSRF token, store it in the session and return it. */
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /** Validate the posted CSRF token.  Die with 403 on mismatch. */
    protected function validateCsrf(): void
    {
        $posted = $_POST['_csrf'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        if (!$stored || !hash_equals($stored, $posted)) {
            http_response_code(403);
            exit('Requête invalide (CSRF).');
        }
    }

    /** Return a POST value, trimmed. */
    protected function input(string $key, string $default = ''): string
    {
        return trim($_POST[$key] ?? $default);
    }

    /** Flash a message to the session for the next request. */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
    }

    /** Retrieve and clear flash messages. */
    protected function getFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }

    /** Retrieve an integer from GET params. */
    protected function getInt(string $key, int $default = 0): int
    {
        return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
    }
}
