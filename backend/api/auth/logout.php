<?php
/**
 * User Logout API
 */

require_once '../../config/config.php';
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

try {
    if (isAuthenticated()) {
        $user_id = $_SESSION['user_id'];
        logActivity($user_id, 'logout', 'User logged out');
    }
    
    session_destroy();
    
    // Cookie clearing removed - using localStorage only
    
    sendResponse([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    sendResponse(['success' => false, 'message' => 'Logout failed'], 500);
}
?>