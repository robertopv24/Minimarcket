# Registro de Decisiones de Arquitectura (ADR)

## ADR-001: Adopción de PSR-4 y Composer
**Estado:** Propuesto
**Contexto:** El proyecto utiliza `require_once` manuales y clases en el namespace global. Esto dificulta el testing y la modularización.
**Decisión:** Implementar Composer para autoloading PSR-4. Las nuevas clases residirán en `src/` bajo el namespace `Minimarcket\`.
**Consecuencias:**
- Requiere instalar Composer en el entorno de desarrollo/producción.
- Se debe refactorizar `autoload.php` para incluir `vendor/autoload.php`.

## ADR-002: Estrategia de Coexistencia (Proxy Pattern)
**Estado:** Propuesto
**Contexto:** No podemos detener el desarrollo ni romper la funcionalidad actual mientras migramos.
**Decisión:** Mantener las clases "Legacy" en `funciones/` pero vaciar su lógica, delegando las llamadas a los nuevos servicios en `src/`.
**Ejemplo:**
```php
// funciones/ProductManager.php
class ProductManager {
    private $service;
    public function __construct() {
        $this->service = Container::get(ProductService::class);
    }
    public function getProduct($id) {
        return $this->service->getProductById($id);
    }
}
```
**Consecuencias:**
- Permite refactorización gradual.
- Introduce una pequeña capa de indirección temporal.

## ADR-003: Inyección de Dependencias
**Estado:** Propuesto
**Contexto:** Las clases actuales instancian sus dependencias internamente o dependen de globales.
**Decisión:** Utilizar un contenedor de dependencias simple (o PHP-DI) para inyectar dependencias en los constructores.
**Consecuencias:**
- Mejora testabilidad.
- Requiere un archivo de "bootstrap" que configure el contenedor.

## ADR-004: Separación por Dominios (Modules)
**Estado:** Propuesto
**Contexto:** La lógica está mezclada.
**Decisión:** Definir límites estrictos: Inventory, Sales, Finance, User, SupplyChain.
**Consecuencias:**
- Código más limpio y mantenible.
- Posible duplicación inicial de DTOs o modelos compartidos hasta definir objetos de dominio comunes.
