<?php

namespace App;

/**
 * Simple URL router.
 *
 * Routes are matched in declaration order. The first matching route wins.
 * Patterns use named captures: e.g. /books/(?P<slug>[^/]+)
 */
class Router
{
    /** @var array<array{method:string, pattern:string, handler:callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    private function addRoute(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'pattern' => '#^' . $pattern . '$#u',
            'handler' => $handler,
        ];
    }

    /**
     * Match the current request and invoke the appropriate handler.
     * Returns false if no route matched.
     */
    public function dispatch(string $method, string $uri): bool
    {
        // Strip query string
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            if (preg_match($route['pattern'], $path, $matches)) {
                // Keep only named captures
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func($route['handler'], $params);
                return true;
            }
        }
        return false;
    }
}
