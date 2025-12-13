<?php

namespace Minimarcket\Modules\User\Services;

use Minimarcket\Modules\User\Repositories\UserRepository;
use Minimarcket\Core\Session\SessionManager;
use Minimarcket\Core\Database;
use Exception;

class UserService
{
    private $userRepository;
    private $sessionManager;

    public function __construct(UserRepository $userRepository, SessionManager $sessionManager)
    {
        $this->userRepository = $userRepository;
        $this->sessionManager = $sessionManager;
    }

    public function authenticate($email, $password)
    {
        $user = $this->userRepository->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            // Remove sensitive password from object before storing in session
            unset($user['password']);
            $this->sessionManager->login($user);
            return [
                'success' => true,
                'user' => $user
            ];
        }

        return [
            'success' => false,
            'message' => 'Credenciales inválidas.'
        ];
    }

    public function login($email, $password)
    {
        return $this->authenticate($email, $password);
    }

    public function getUserById($id)
    {
        return $this->userRepository->find($id);
    }

    public function getUserByEmail($email)
    {
        return $this->userRepository->findByEmail($email);
    }

    public function createUser($name, $email, $password, $phone, $documentId, $address, $role = 'user')
    {
        // Check if email exists
        if ($this->userRepository->findByEmail($email)) {
            return "Error: El email ya está registrado.";
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->userRepository->create([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'phone' => $phone,
            'document_id' => $documentId,
            'address' => $address,
            'role' => $role
        ]);

        return $userId;
    }

    public function updateUser($id, $name, $email, $role)
    {
        return $this->userRepository->update($id, [
            'name' => $name,
            'email' => $email, // Should we validate email uniqueness here? Ideally yes.
            'role' => $role
        ]);
    }

    public function updateUserProfile($id, $name, $email, $phone, $documentId, $address)
    {
        return $this->userRepository->update($id, [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'document_id' => $documentId,
            'address' => $address
        ]);
    }

    public function changePassword($id, $oldPassword, $newPassword)
    {
        $user = $this->getUserById($id);
        if (!$user)
            return "Usuario no encontrado.";

        if (!password_verify($oldPassword, $user['password'])) {
            return "La contraseña actual es incorrecta.";
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->update($id, ['password' => $newHash]);

        return true;
    }

    public function setPasswordResetToken($email, $token)
    {
        $user = $this->userRepository->findByEmail($email);
        if (!$user)
            return false;

        // Note: reset_token and token_expiry fields must exist in DB.
        // Assuming they do based on legacy usage.
        return $this->userRepository->update($user['id'], [
            'reset_token' => $token,
            'token_expiry' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
    }

    public function deleteUser($id)
    {
        return $this->userRepository->delete($id);
    }

    public function getAllUsers()
    {
        return $this->userRepository->all();
    }

    public function searchUsers($query)
    {
        return $this->userRepository->search($query);
    }

    public function getTotalUsuarios()
    {
        return $this->userRepository->count();
    }

    public function saveProfilePicture($userId, $imagePath)
    {
        return $this->userRepository->update($userId, ['profile_picture' => $imagePath]);
    }

    // Role checks
    public function hasPosAccess($userSession)
    {
        if (!$userSession)
            return false;
        // Logic from legacy: role IN admin, cajero
        return in_array($userSession['role'], ['admin', 'cajero']);
    }

    public function hasKitchenAccess($userSession)
    {
        if (!$userSession)
            return false;
        return in_array($userSession['role'], ['admin', 'cocina']);
    }

    public function validateAnyAdminPassword($password)
    {
        $admins = $this->userRepository->getAllAdmins();
        foreach ($admins as $admin) {
            if (password_verify($password, $admin['password'])) {
                return true;
            }
        }
        return false;
    }
}
