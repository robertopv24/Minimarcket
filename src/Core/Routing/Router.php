<?php

namespace Minimarcket\Core\Routing;

use Exception;
use Minimarcket\Core\Container;

class Router
{
    protected array $routes = [];
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    protected function addRoute(string $method, string $uri, $action): Route
    {
        $route = new Route($method, $uri, $action);
        $this->routes[$method][$uri] = $route;
        return $route;
    }

    public function dispatch(string $method, string $uri)
    {
        // Limpiar URI (query strings, trailing slashes)
        $uri = parse_url($uri, PHP_URL_PATH);
        // Si estamos en subcarpeta (ej /minimarcket/public/...), ajustar aquí si fuera necesario
        // Por ahora asumimos que la URI viene limpia o relativa al root.

        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
            return $this->handle($route);
        }

        throw new Exception("Ruta no encontrada: [$method] $uri", 404);
    }

    protected function handle(Route $route)
    {
        // TODO: Middleware processing here

        $action = $route->action;

        // 1. Array [Controller::class, 'method']
        if (is_array($action)) {
            $controllerClass = $action[0];
            $method = $action[1];

            if (!class_exists($controllerClass)) {
                throw new Exception("Controlador no encontrado: $controllerClass");
            }

            // Resolver controlador desde el Container (DI)
            $controller = $this->container->get($controllerClass);

            if (!method_exists($controller, $method)) {
                throw new Exception("Método $method no encontrado en $controllerClass");
            }

            return $controller->$method();
        }

        // 2. Closure function() { ... }
        if (is_callable($action)) {
            return call_user_func($action);
        }

        throw new Exception("Acción de ruta inválida");
    }
}
