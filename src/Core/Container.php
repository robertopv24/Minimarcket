<?php

namespace Minimarcket\Core;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Class Container
 * 
 * Un contenedor de inyección de dependencias simple y nativo.
 * Soporta registro de servicios, fábricas (closures) y autowiring básico.
 */
class Container
{
    /**
     * @var array Almacena las definiciones (instancias, nombres de clase, o closures)
     */
    protected array $definitions = [];

    /**
     * @var array Almacena las instancias compartidas (Singleton)
     */
    protected array $shared = [];

    /**
     * @var Container|null Instancia global del contenedor (Singleton Pattern)
     */
    private static ?Container $instance = null;

    /**
     * Obtiene la instancia global del contenedor.
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establece la instancia global (usado por Application).
     */
    public static function setInstance(Container $container): void
    {
        self::$instance = $container;
    }

    /**
     * Registra un servicio o valor en el contenedor.
     * 
     * @param string $id Identificador del servicio (usualmente nombre de clase o interfaz)
     * @param mixed $concrete Implementación concreta (instancia, closure, o nombre de clase)
     */
    public function set(string $id, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }
        $this->definitions[$id] = $concrete;
    }

    /**
     * Obtiene una instancia del servicio solicitado.
     * 
     * @param string $id Identificador del servicio
     * @return mixed Instancia del servicio
     * @throws Exception Si no se puede resolver la dependencia
     */
    public function get(string $id): mixed
    {
        // 1. Si ya existe una instancia compartida, retornarla
        if (array_key_exists($id, $this->shared)) {
            return $this->shared[$id];
        }

        // 2. Si no tenemos definición, intentamos autowiring directo si es una clase existente
        if (!isset($this->definitions[$id])) {
            if (class_exists($id)) {
                $object = $this->resolve($id);
                $this->shared[$id] = $object; // Singleton por defecto para autowiring
                return $object;
            }
            throw new Exception("Servicio no encontrado o no definido: {$id}");
        }

        $concrete = $this->definitions[$id];

        // 3. Si la definición es un Closure (Factory), ejecutarlo
        if ($concrete instanceof \Closure) {
            $object = $concrete($this);
            $this->shared[$id] = $object; // Singleton por defecto para factories
            return $object;
        }

        // 4. Si la definición es un string (nombre de clase), resolver autowiring
        if (is_string($concrete)) {
            // Si el ID es igual al concreto, o si concreto es nombre de clase
            $object = $this->resolve($concrete);
            $this->shared[$id] = $object;
            return $object;
        }

        // 5. Si es un objeto/valor directo, retornarlo tal cual
        return $concrete;
    }

    /**
     * Verifica si un servicio está definido.
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * Resuelve una clase concreta inyectando sus dependencias automáticamente.
     */
    protected function resolve(string $className): object
    {
        try {
            $reflector = new ReflectionClass($className);
        } catch (ReflectionException $e) {
            throw new Exception("Error de reflexión: La clase {$className} no existe.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new Exception("La clase {$className} no es instanciable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $className;
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resuelve un array de dependencias.
     */
    protected function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Si no tiene tipo o es un tipo primitivo (no clase), intentamos valor por defecto
            if (!$type || $type instanceof ReflectionUnionType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("No se puede resolver la dependencia no tipada o primitiva: \${$parameter->getName()}");
            }

            // Si es un tipo de clase (NamedType), intentamos obtenerla del contenedor
            if ($type instanceof ReflectionNamedType) {
                // Recursividad: pedimos al contenedor la clase requerida
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            throw new Exception("Tipo de dependencia no soportado para: \${$parameter->getName()}");
        }

        return $dependencies;
    }
}
