<?php

namespace HBM\Core;

use HBM\Helpers\Response;

class Router {
    private array $routes = [];

    public function get(string $path, callable|array $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler
        ];
    }

    private function normalizePath(string $path): string {
        $path = trim($path, '/');
        $path = "/{$path}/";
        return preg_replace('/[\/]{2,}/', '/', $path);
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            // Convert {param} to Regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route['path']);
            $pattern = "#^{$pattern}$#";

            if ($route['method'] === $method && preg_match($pattern, $path, $matches)) {
                $handler = $route['handler'];
                
                // Filter out numeric keys from regex matches to get named params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                if (is_array($handler) && count($handler) === 2) {
                    $className = $handler[0];
                    $methodName = $handler[1];
                    
                    if (class_exists($className)) {
                        $instance = new $className();
                        if (method_exists($instance, $methodName)) {
                            // Call with named params in order
                            call_user_func_array([$instance, $methodName], array_values($params));
                            return;
                        }
                    }
                } elseif (is_callable($handler)) {
                    call_user_func_array($handler, array_values($params));
                    return;
                }
            }
        }

        Response::error('Not Found', 404);
    }
}
