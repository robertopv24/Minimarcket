<?php

namespace Minimarcket\Core;

use Exception;
use ReflectionClass;

class Container
{
    private static $instance;
    private $services = [];
    private $instances = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register(string $key, callable $resolver): void
    {
        $this->services[$key] = $resolver;
    }

    public function get(string $key)
    {
        // 1. Return existing singleton instance if available
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        // 2. Resolve via registered closure
        if (isset($this->services[$key])) {
            $object = $this->services[$key]($this);
            $this->instances[$key] = $object; // Singleton by default for services
            return $object;
        }

        // 3. Auto-wiring (Simple implementation)
        if (class_exists($key)) {
            $reflector = new ReflectionClass($key);
            if (!$reflector->isInstantiable()) {
                throw new Exception("Class $key is not instantiable.");
            }

            $constructor = $reflector->getConstructor();
            if (!$constructor) {
                $object = new $key;
                $this->instances[$key] = $object;
                return $object;
            }

            $parameters = $constructor->getParameters();
            $dependencies = [];

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if ($type && !$type->isBuiltin()) {
                    $dependencies[] = $this->get($type->getName());
                } else {
                    // Primitive types cannot be auto-wired securely without config
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                    } else {
                        throw new Exception("Cannot resolve parameter '{$parameter->getName()}' of class '$key'");
                    }
                }
            }

            $object = $reflector->newInstanceArgs($dependencies);
            $this->instances[$key] = $object;
            return $object;
        }

        throw new Exception("Service '$key' not found in container.");
    }
}
