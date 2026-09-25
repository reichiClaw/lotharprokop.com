<?php
declare(strict_types=1);

namespace App;

/**
 * Minimaler Router: Pfadmuster mit {parametern}, Methode GET/POST.
 */
final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:callable, names:string[]}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $names = [];
        $regex = preg_replace_callback('/\{([a-z_]+)(?::([^}]+))?\}/', static function ($m) use (&$names) {
            $names[] = $m[1];
            return '(' . ($m[2] ?? '[^/]+') . ')';
        }, $pattern) ?? $pattern;
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $regex . '$#u',
            'handler' => $handler,
            'names' => $names,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim($path, '/');
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }
            array_shift($m);
            $params = [];
            foreach ($route['names'] as $i => $name) {
                $params[$name] = urldecode($m[$i] ?? '');
            }
            ($route['handler'])($params);
            return;
        }
        if ($allowed !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowed)));
            View::render('error', ['title' => 'Methode nicht erlaubt', 'code' => 405, 'message' => 'Diese Anfrage ist hier nicht möglich.']);
            return;
        }
        View::notFound();
    }
}
