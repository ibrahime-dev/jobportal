<?php
/**
 * LOGOUT HANDLER
 * 
 * Description: Handles user logout and session cleanup
 * Features:
 * - Session destruction and cleanup
 * - Local storage clearing
 * - Security token invalidation
 * - Redirect to login page
 * - Logout confirmation
 * - Cross-browser compatibility
 * 
 * User Types: All authenticated users
 * Access: Available to all logged-in users
 */

session_start();
session_destroy();

// Session cookie clearing removed - using localStorage only
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        // Clear all client-side storage
        localStorage.clear();
        sessionStorage.clear();
        
        // Redirect to index.php with logout success parameter
        window.location.href = 'index.php?logout=success';
    </script>
</body>
</html>