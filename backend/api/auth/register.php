<?php
/**
 * User Registration API
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
    
    $required_fields = ['first_name', 'last_name', 'email', 'password', 'user_type'];
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
    
    if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
        sendResponse([
            'success' => false, 
            'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters'
        ], 400);
    }
    
    // Check if email exists
    $existing_user = fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
    
    if ($existing_user) {
        sendResponse(['success' => false, 'message' => 'Email already registered'], 409);
    }
    
    $password_hash = hashPassword($data['password']);
    
    $user_id = insertRecord(
        "INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, location, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
        [
            $data['email'],
            $password_hash,
            $data['user_type'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $data['location'] ?? null
        ]
    );
    
    if (!$user_id) {
        sendResponse(['success' => false, 'message' => 'Failed to create account'], 500);
    }
    
    // Create profile
    if ($data['user_type'] === 'job_seeker') {
        insertRecord("INSERT INTO job_seeker_profiles (user_id, created_at) VALUES (?, NOW())", [$user_id]);
    } elseif ($data['user_type'] === 'employer') {
        insertRecord("INSERT INTO employer_profiles (user_id, created_at) VALUES (?, NOW())", [$user_id]);
    }
    
    logActivity($user_id, 'user_registered', 'User type: ' . $data['user_type']);
    
    sendResponse([
        'success' => true,
        'message' => 'Account created successfully',
        'user_id' => $user_id
    ], 201);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Registration failed'], 500);
}
?>