<?php
/**
 * User Login API
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
    }
    
    $required_fields = ['email', 'password'];
    $missing_fields = validateRequired($input, $required_fields);
    
    if (!empty($missing_fields)) {
        sendResponse([
            'success' => false, 
            'message' => 'Missing fields: ' . implode(', ', $missing_fields)
        ], 400);
    }
    
    $data = sanitizeInput($input);
    
    if (!validateEmail($data['email'])) {
        sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }
    
    $user = fetchOne(
        "SELECT id, email, password_hash, user_type, first_name, last_name, phone, location, is_active 
         FROM users WHERE email = ?",
        [$data['email']]
    );
    
    if (!$user) {
        sendResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    if (!$user['is_active']) {
        sendResponse(['success' => false, 'message' => 'Account is deactivated'], 401);
    }
    
    if (!verifyPassword($data['password'], $user['password_hash'])) {
        logActivity($user['id'], 'login_failed', 'Invalid password');
        sendResponse(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
    
    // Create session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['login_time'] = time();
    
    $user_data = [
        'id' => $user['id'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'phone' => $user['phone'],
        'location' => $user['location']
    ];
    
    logActivity($user['id'], 'login_success', 'User logged in');
    
    sendResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user_data
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Login failed'], 500);
}
?>