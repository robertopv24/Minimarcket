<?php

namespace Minimarcket\Core\View;

use Minimarcket\Core\Database;
use PDO;

class MenuService
{
    private $conn;
    private $table_name = "menus";

    public function __construct(?PDO $conn = null)
    {
        $this->conn = $conn ?? Database::getConnection();
    }

    public function getAllMenus()
    {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMenuById($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createMenu($title, $url, $type)
    {
        $query = "INSERT INTO " . $this->table_name . " (title, url, type) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $url, $type]);
    }

    public function updateMenu($id, $title, $url, $type)
    {
        $query = "UPDATE " . $this->table_name . " SET title = ?, url = ?, type = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$title, $url, $type, $id]);
    }

    public function deleteMenu($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }
}
