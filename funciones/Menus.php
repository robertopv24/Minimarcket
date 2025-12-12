<?php
require_once __DIR__ . '/conexion.php';
// Important: Legacy Menus.php was often instantiated as 'new Menus()', no params.
// So proxy should support that.

use Minimarcket\Core\Container;
use Minimarcket\Core\View\MenuService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Core\View\MenuService instead.
 */
class Menus
{
    private $service;

    public function __construct()
    {
        $container = Container::getInstance();
        try {
            $this->service = $container->get(MenuService::class);
        } catch (Exception $e) {
            $this->service = new MenuService();
        }
    }

    public function getAllMenus()
    {
        return $this->service->getAllMenus();
    }

    public function getMenuById($id)
    {
        return $this->service->getMenuById($id);
    }

    public function createMenu($title, $url, $type)
    {
        return $this->service->createMenu($title, $url, $type);
    }

    public function updateMenu($id, $title, $url, $type)
    {
        return $this->service->updateMenu($id, $title, $url, $type);
    }

    public function deleteMenu($id)
    {
        return $this->service->deleteMenu($id);
    }
}