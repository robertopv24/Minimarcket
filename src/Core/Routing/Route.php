<?php

namespace Minimarcket\Core\Routing;

class Route
{
    public string $method;
    public string $uri;
    public $action;
    public array $middlewares = [];

    public function __construct(string $method, string $uri, $action)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
    }

    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
}
