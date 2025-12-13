<?php

namespace Minimarcket\Core\Session;

class SessionManager
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Start the session if not already started.
     */
    public function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Regenerate session ID to prevent session fixation.
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }

    /**
     * Destroy the current session.
     */
    public function destroy()
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Set a session variable.
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable.
     */
    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a session variable exists.
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable.
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    // --- Authentication Helpers ---

    public function login($user)
    {
        $this->regenerate();
        $this->set('user_id', $user['id']);
        $this->set('user_role', $user['role']);
        $this->set('user_name', $user['name']);
        $this->set('logged_in', true);
    }

    public function logout()
    {
        $this->destroy();
    }

    public function isAuthenticated()
    {
        return $this->get('logged_in') === true;
    }

    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }
        return [
            'id' => $this->get('user_id'),
            'role' => $this->get('user_role'),
            'name' => $this->get('user_name')
        ];
    }

    // --- Flash Messages ---

    public function setFlash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }

    public function getFlash($key)
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
}
