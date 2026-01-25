<?php

require_once __DIR__ . '/conexion.php';

class UserManager
{
    private $db;

    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Database::getConnection();
        }
    }

    // Crear usuario con validación
    public function createUser($name, $email, $password, $phone, $documentId, $address, $role = 'user')
    {
        if ($this->getUserByEmail($email)) {
            return "Error: El correo ya está registrado.";
        }
        $hashPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, phone, document_id, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([$name, $email, $hashPassword, $phone, $documentId, $address, $role]);
    }

    // Obtener usuario por ID
    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, document_id, address, role, profile_pic, balance, salary_amount, salary_frequency, job_role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Obtener lista de usuarios con búsqueda opcional
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

    // Obtener usuario por email
    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, phone, document_id, address, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Autenticación de usuario
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
        // Busca todos los administradores
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

    /**
     * Verifica si el usuario tiene permiso de acceder al Punto de Venta (POS)
     * Regla: Admin siempre SI. User solo si job_role es 'cashier' o 'manager'.
     */
    public function hasPosAccess($userSession)
    {
        if (!isset($userSession['user_id']))
            return false;

        // Si es Admin de sistema, acceso total
        // OJO: validar que 'role' esté en session. Generalmente guardamos 'role'.
        // Si no está en session, habría que consultarlo. Asumimos que login guarda datos basicos.
        // Si no, hacemos query. Para optimizar, asumimos que SESSION tiene lo necesario o consultamos DB.

        // Para seguridad robusta, consultamos DB usando user_id de sesion
        $u = $this->getUserById($userSession['user_id']);
        if (!$u)
            return false;

        if ($u['role'] === 'admin')
            return true;
        if (in_array($u['job_role'], ['manager', 'cashier']))
            return true;

        return false;
    }

    public function login($email, $password)
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false; // Error: Formato de email inválido
        }

        $user = $this->authenticate($email, $password);
        if ($user) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_profile_pic'] = $user['profile_pic'];
            $_SESSION['user_balance'] = $user['balance'];
            return $user; // Return user array on success
        }

        return false; // Return false on failure
    }


    // Actualizar usuario (Admin)
    public function updateUser($id, $name, $email, $role)
    {
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
        return $stmt->execute([$name, $email, $role, $id]);
    }

    // Actualizar perfil de usuario
    public function updateUserProfile($id, $name, $email, $phone, $documentId, $address)
    {
        try {
            // Validación de datos
            if (empty($id) || empty($name) || empty($email)) {
                throw new Exception("Error: Los campos ID, nombre y correo electrónico son obligatorios.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Error: El correo electrónico no tiene un formato válido.");
            }

            // Validación de correo electrónico único
            $existingUser = $this->getUserByEmail($email);
            if ($existingUser && $existingUser['id'] != $id) {
                throw new Exception("Error: El correo electrónico ya está en uso por otro usuario.");
            }

            // Preparar la consulta SQL
            $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, document_id = ?, address = ?, updated_at = NOW() WHERE id = ?");

            // Ejecutar la consulta
            if (!$stmt->execute([$name, $email, $phone, $documentId, $address, $id])) {
                throw new Exception("Error: No se pudo actualizar el perfil del usuario.");
            }

            return true; // Éxito

        } catch (Exception $e) {
            error_log("Error en updateUserProfile: " . $e->getMessage());
            return $e->getMessage(); // Devolver el mensaje de error
        }
    }

    // Guardar foto de perfil
    public function saveProfilePicture($userId, $imagePath)
    {
        $stmt = $this->db->prepare("UPDATE users SET profile_pic = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$imagePath, $userId]);
    }

    // Cambiar contraseña con validación
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

    // Restablecer contraseña con token
    public function setPasswordResetToken($email, $token)
    {
        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE email = ?");
        return $stmt->execute([$token, $email]);
    }

    // Eliminar usuario
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

    /**
     * Verifica acceso a pantallas de cocina/despacho (KDS)
     * Admin, Manager, Cashier, Kitchen, Delivery
     */
    public function hasKitchenAccess($userSession)
    {
        if (!isset($userSession['user_id']))
            return false;

        $u = $this->getUserById($userSession['user_id']);
        if (!$u)
            return false;

        if ($u['role'] === 'admin')
            return true;
        if (in_array($u['job_role'], ['manager', 'cashier', 'kitchen', 'delivery']))
            return true;

        return false;
    }

    /**
     * Enforce kitchen access. Redirects if validation fails.
     * Replaces templates/kitchen_check.php to avoid permission errors.
     */
    public function requireKitchenAccess($session)
    {
        if (!$this->hasKitchenAccess($session)) {
            if (isset($session['user_id'])) {
                header("Location: ../paginas/status.php");
            } else {
                header("Location: ../paginas/login.php");
            }
            exit;
        }
    }

    public function requireAdminAccess($session)
    {
        if (!isset($session['user_id'])) {
            header("Location: ../paginas/login.php");
            exit;
        }
        $user = $this->getUserById($session['user_id']);
        if (!$user || $user['role'] !== 'admin') {
            header("Location: ../paginas/index.php");
            exit;
        }
    }
}

// Uso de la clase
// $userManager = new UserManager();
// $userManager->createUser("testuser", "test@example.com", "password123", "123456789", "ID123", "Calle 123", "user");
