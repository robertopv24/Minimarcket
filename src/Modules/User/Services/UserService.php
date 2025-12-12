<?php

namespace Minimarcket\Modules\User\Services;

use Minimarcket\Core\Database;
use PDO;
use Exception;

class UserService
{
    private $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function createUser($name, $email, $password, $phone, $documentId, $address, $role = 'user')
    {
        if ($this->getUserByEmail($email)) {
            return "Error: El correo ya está registrado.";
        }
        $hashPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, phone, document_id, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$name, $email, $hashPassword, $phone, $documentId, $address, $role]);
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, document_id, address, role, profile_pic, balance, salary_amount, salary_frequency, job_role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function searchUsers($query = '')
    {
        $sql = "SELECT id, name, email, phone, role, created_at FROM users";
        $params = [];

        if (!empty($query)) {
            $sql .= " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
            $term = "%$query%";
            $params = [$term, $term, $term];
        }

        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsers()
    {
        return $this->searchUsers();
    }

    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, document_id, address, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function authenticate($email, $password)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, profile_pic, balance FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function validateAnyAdminPassword($password)
    {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($admins as $hash) {
            if (password_verify($password, $hash)) {
                return true;
            }
        }
        return false;
    }

    public function hasPosAccess($userSession)
    {
        if (!isset($userSession['user_id']))
            return false;

        $u = $this->getUserById($userSession['user_id']);
        if (!$u)
            return false;

        if ($u['role'] === 'admin')
            return true;
        if (in_array($u['job_role'] ?? '', ['manager', 'cashier']))
            return true;

        return false;
    }

    public function login($email, $password)
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $user = $this->authenticate($email, $password);
        if ($user) {
            // Note: Ideally Session manipulation should be in a Controller or AuthManager, not generic UserService.
            // But for parity with UserManager, we do it here or let the caller do it.
            // UserManager did it. I will duplicate side-effects here for 1:1 migration.
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_profile_pic'] = $user['profile_pic'];
            $_SESSION['user_balance'] = $user['balance'];
            return $user;
        }

        return false;
    }

    public function updateUser($id, $name, $email, $role)
    {
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        return $stmt->execute([$name, $email, $role, $id]);
    }

    public function updateUserProfile($id, $name, $email, $phone, $documentId, $address)
    {
        try {
            if (empty($id) || empty($name) || empty($email)) {
                throw new Exception("Error: Los campos ID, nombre y correo electrónico son obligatorios.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Error: El correo electrónico no tiene un formato válido.");
            }

            $existingUser = $this->getUserByEmail($email);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception("Error: El correo electrónico ya está en uso por otro usuario.");
            }

            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, document_id = ?, address = ?, updated_at = NOW() WHERE id = ?");

            if (!$stmt->execute([$name, $email, $phone, $documentId, $address, $id])) {
                throw new Exception("Error: No se pudo actualizar el perfil del usuario.");
            }

            return true;

        } catch (Exception $e) {
            error_log("Error en updateUserProfile: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function updatePayrollData($userId, $jobRole, $salaryFrequency, $salaryAmount)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET job_role = ?, salary_frequency = ?, salary_amount = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt->execute([$jobRole, $salaryFrequency, $salaryAmount, $userId])) {
                throw new Exception("Error al actualizar datos de nómina.");
            }
            return true;
        } catch (Exception $e) {
            error_log("Error en updatePayrollData: " . $e->getMessage());
            return $e->getMessage();
        }
    }

    public function saveProfilePicture($userId, $imagePath)
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_pic = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$imagePath, $userId]);
    }

    public function changePassword($id, $oldPassword, $newPassword)
    {
        $user = $this->getUserById($id);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return "Error: La contraseña actual es incorrecta.";
        }
        $hashPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hashPassword, $id]);
    }

    public function setPasswordResetToken($email, $token)
    {
        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        return $stmt->execute([$token, $email]);
    }

    public function deleteUser($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getTotalUsuarios()
    {
        $query = "SELECT COUNT(*) AS total FROM users";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    public function hasKitchenAccess($userSession)
    {
        if (!isset($userSession['user_id']))
            return false;

        $u = $this->getUserById($userSession['user_id']);
        if (!$u)
            return false;

        if ($u['role'] === 'admin')
            return true;
        if (in_array($u['job_role'] ?? '', ['manager', 'cashier', 'kitchen', 'delivery']))
            return true;

        return false;
    }
}
