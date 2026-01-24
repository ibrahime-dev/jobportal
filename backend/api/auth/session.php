<?php
/**
 * Session Check API
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    if (!isAuthenticated()) {
        sendResponse(['success' => false, 'message' => 'Not authenticated'], 401);
    }
    
    $user_id = $_SESSION['user_id'];
    
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        session_destroy();
        sendResponse(['success' => false, 'message' => 'Session expired'], 401);
    }
    
    $user = fetchOne(
        "SELECT id, email, user_type, first_name, last_name, phone, location, is_active 
         FROM users WHERE id = ?",
        [$user_id]
    );
    
    if (!$user) {
        session_destroy();
        sendResponse(['success' => false, 'message' => 'User not found'], 401);
    }
    
    if (!$user['is_active']) {
        session_destroy();
        sendResponse(['success' => false, 'message' => 'Account deactivated'], 401);
    }
    
    $user_data = [
        'id' => $user['id'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'phone' => $user['phone'],
        'location' => $user['location']
    ];
    
    $_SESSION['login_time'] = time();
    
    sendResponse([
        'success' => true,
        'user' => $user_data
    ]);
    
} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Session validation failed'], 500);
}
?>