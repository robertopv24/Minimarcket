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
        global $app;
        if (isset($app)) {
            try {
                $this->service = $app->getContainer()->get(ConfigService::class);
            } catch (Exception $e) {
                // $this->service = new ConfigService(); // Might fail without dependencies
                // die("ConfigService dependency error: " . $e->getMessage());
                error_log("GlobalConfig Proxy Error: " . $e->getMessage());
                // Rethrow so we see the trace if needed, or suppress if non-critical?
                // autoload.php continues...
                // But $config object will be broken.
                throw $e;
            }
        } else {
            // If accessed before app bootstrap, this is critical error now
            die("GlobalConfig instantiated before App Bootstrap.");
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

// Inicialización eliminada. La instanciación debe ocurrir en autoload.php o vía Contenedor.
// $config = new GlobalConfig(); 
// $config->setGlobals();
?>