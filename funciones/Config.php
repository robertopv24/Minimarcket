<?php
// config.php - Configuración global del sistema. PROXY VERSION.

use Minimarcket\Core\Container;
use Minimarcket\Core\Config\ConfigService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\Config\ConfigService instead.
 */
class GlobalConfig
{
    private $service;

    public function __construct()
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(ConfigService::class);
        } catch (Exception $e) {
            $this->service = new ConfigService();
        }
    }

    public function load()
    {
        $this->service->load();
    }

    public function get($key, $value = null)
    {
        return $this->service->get($key, $value);
    }

    public function update($key, $value)
    {
        return $this->service->update($key, $value);
    }

    public function getAll()
    {
        return $this->service->getAll();
    }

    public function setGlobals()
    {
        $this->service->setGlobals();
    }
}

// Inicialización para compatibilidad
$config = new GlobalConfig();
$config->setGlobals();
?>