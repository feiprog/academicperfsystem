<?php
require_once __DIR__ . '/../config.php';

class SessionManager {
    public static function init() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerateSession();
        } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            self::regenerateSession();
        }
    }
    
    public static function regenerateSession() {
        // Regenerate session ID and update timestamp
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    public static function destroy() {
        // Clear all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function getUser() {
        return isset($_SESSION['user_id']) ? [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'full_name' => $_SESSION['full_name']
        ] : null;
    }
    
    public static function setUser($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
    }
    
    public static function checkInactivity() {
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            self::destroy();
            return false;
        }
        $_SESSION['last_activity'] = time();
        return true;
    }
} 