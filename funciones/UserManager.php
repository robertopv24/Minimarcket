<?php

use Minimarcket\Modules\User\Services\UserService;

/**
 * @deprecated This class is a legacy proxy. Use Minimarcket\Modules\User\Services\UserService instead.
 */
class UserManager
{
    private $service;

    public function __construct($db = null)
    {
        // Obtener el servicio desde el contenedor global de la aplicación
        global $app;
        if (isset($app)) {
            $this->service = $app->getContainer()->get(UserService::class);
        } else {
            // Fallback temporal si no hay app inicializada
            throw new \Exception("Application not bootstrapped. Cannot instantiate UserManager.");
        }
    }

    public function createUser($name, $email, $password, $phone, $documentId, $address, $role = 'user')
    {
        return $this->service->createUser($name, $email, $password, $phone, $documentId, $address, $role);
    }

    public function getUserById($id)
    {
        return $this->service->getUserById($id);
    }

    public function searchUsers($query = '')
    {
        return $this->service->searchUsers($query);
    }

    public function getAllUsers()
    {
        return $this->service->getAllUsers();
    }

    public function getUserByEmail($email)
    {
        return $this->service->getUserByEmail($email);
    }

    public function authenticate($email, $password)
    {
        return $this->service->authenticate($email, $password);
    }

    public function validateAnyAdminPassword($password)
    {
        return $this->service->validateAnyAdminPassword($password);
    }

    public function hasPosAccess($userSession)
    {
        return $this->service->hasPosAccess($userSession);
    }

    public function login($email, $password)
    {
        return $this->service->login($email, $password);
    }

    public function updateUser($id, $name, $email, $role)
    {
        return $this->service->updateUser($id, $name, $email, $role);
    }

    public function updateUserProfile($id, $name, $email, $phone, $documentId, $address)
    {
        return $this->service->updateUserProfile($id, $name, $email, $phone, $documentId, $address);
    }

    public function saveProfilePicture($userId, $imagePath)
    {
        return $this->service->saveProfilePicture($userId, $imagePath);
    }

    public function changePassword($id, $oldPassword, $newPassword)
    {
        return $this->service->changePassword($id, $oldPassword, $newPassword);
    }

    public function setPasswordResetToken($email, $token)
    {
        return $this->service->setPasswordResetToken($email, $token);
    }

    public function deleteUser($id)
    {
        return $this->service->deleteUser($id);
    }

    public function getTotalUsuarios()
    {
        return $this->service->getTotalUsuarios();
    }

    public function hasKitchenAccess($userSession)
    {
        return $this->service->hasKitchenAccess($userSession);
    }
}
