<?php
/**
 * Application Configuration for XAMPP
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('UTC');

// Cookie settings removed - using localStorage for authentication
session_start();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Constants for XAMPP
define('APP_NAME', 'JobPortal');
define('BASE_URL', 'http://localhost/create');
define('UPLOAD_PATH', '../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_TIMEOUT', 3600);

// Create upload directories
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}

$upload_dirs = ['resumes', 'logos', 'avatars'];
foreach ($upload_dirs as $dir) {
    $full_path = UPLOAD_PATH . $dir;
    if (!file_exists($full_path)) {
        mkdir($full_path, 0755, true);
    }
}

function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

function validateRequired($data, $required_fields) {
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing_fields[] = $field;
        }
    }
    return $missing_fields;
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        sendResponse(['success' => false, 'message' => 'Authentication required'], 401);
    }
}

function logActivity($user_id, $action, $details = '') {
    try {
        $query = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
        executeQuery($query, [$user_id, $action, $details]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
?>