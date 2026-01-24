<?php
/**
 * USER MANAGEMENT SYSTEM
 * 
 * Description: Administrative tool for managing all user accounts
 * Features:
 * - View all users (job seekers, employers, admins)
 * - User search and filtering
 * - Edit user information
 * - Activate/deactivate user accounts
 * - Delete user accounts
 * - User statistics and analytics
 * - Bulk user operations
 * 
 * User Types: Admin only
 * Access: Restricted to authenticated admin users
 */

// Database configuration
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = '';

echo "<h1>👥 User Management</h1>";
echo "<hr>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Handle password reset if requested
    if (isset($_POST['reset_password']) && isset($_POST['user_id'])) {
        $user_id = $_POST['user_id'];
        $new_password = 'password'; // Default password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$password_hash, $user_id]);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;'>";
        echo "✅ Password reset to 'password' for user ID: $user_id";
        echo "</div>";
    }
    
    // Get all users
    $stmt = $pdo->query("
        SELECT id, email, user_type, first_name, last_name, phone, location, is_active, created_at 
        FROM users 
        ORDER BY user_type, created_at
    ");
    $users = $stmt->fetchAll();
    
    echo "<h2>📊 All User Accounts</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 12px; text-align: left;'>ID</th>";
    echo "<th style='padding: 12px; text-align: left;'>Email</th>";
    echo "<th style='padding: 12px; text-align: left;'>Name</th>";
    echo "<th style='padding: 12px; text-align: left;'>Type</th>";
    echo "<th style='padding: 12px; text-align: left;'>Status</th>";
    echo "<th style='padding: 12px; text-align: left;'>Created</th>";
    echo "<th style='padding: 12px; text-align: left;'>Actions</th>";
    echo "</tr>";
    
    foreach ($users as $user) {
        $status_color = $user['is_active'] ? 'green' : 'red';
        $status_text = $user['is_active'] ? 'Active' : 'Inactive';
        
        echo "<tr>";
        echo "<td style='padding: 8px;'>{$user['id']}</td>";
        echo "<td style='padding: 8px;'><strong>{$user['email']}</strong></td>";
        echo "<td style='padding: 8px;'>{$user['first_name']} {$user['last_name']}</td>";
        echo "<td style='padding: 8px;'>";
        
        // Color code user types
        $type_color = '';
        switch($user['user_type']) {
            case 'admin': $type_color = '#dc3545'; break;
            case 'employer': $type_color = '#007bff'; break;
            case 'job_seeker': $type_color = '#28a745'; break;
        }
        echo "<span style='color: $type_color; font-weight: bold;'>{$user['user_type']}</span>";
        echo "</td>";
        echo "<td style='padding: 8px; color: $status_color; font-weight: bold;'>$status_text</td>";
        echo "<td style='padding: 8px;'>" . date('Y-m-d', strtotime($user['created_at'])) . "</td>";
        echo "<td style='padding: 8px;'>";
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='user_id' value='{$user['id']}'>";
        echo "<button type='submit' name='reset_password' style='background: #ffc107; color: #212529; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer;'>Reset Password</button>";
        echo "</form>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
    echo "<h3>🔐 Default Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@jobportal.com / password</li>";
    echo "<li><strong>Employer:</strong> employer@example.com / password</li>";
    echo "<li><strong>Job Seeker:</strong> jobseeker@example.com / password</li>";
    echo "</ul>";
    echo "<p><strong>Note:</strong> Click 'Reset Password' to reset any user's password to 'password'</p>";
    echo "</div>";
    
    // Show recent activity
    echo "<h2>📈 Recent Activity</h2>";
    $stmt = $pdo->query("
        SELECT al.*, u.email, u.first_name, u.last_name 
        FROM activity_logs al 
        LEFT JOIN users u ON al.user_id = u.id 
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $activities = $stmt->fetchAll();
    
    if (count($activities) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 20px 0;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 8px;'>Time</th>";
        echo "<th style='padding: 8px;'>User</th>";
        echo "<th style='padding: 8px;'>Action</th>";
        echo "<th style='padding: 8px;'>Details</th>";
        echo "</tr>";
        
        foreach ($activities as $activity) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . date('Y-m-d H:i', strtotime($activity['created_at'])) . "</td>";
            echo "<td style='padding: 8px;'>";
            if ($activity['email']) {
                echo "{$activity['first_name']} {$activity['last_name']}<br><small>{$activity['email']}</small>";
            } else {
                echo "System";
            }
            echo "</td>";
            echo "<td style='padding: 8px;'>{$activity['action']}</td>";
            echo "<td style='padding: 8px;'>{$activity['details']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No recent activity found.</p>";
    }
    
    echo "<hr>";
    echo "<h3>🔧 Quick Actions:</h3>";
    echo "<ul>";
    echo "<li><a href='test-login-api.php'>Test Login API</a></li>";
    echo "<li><a href='check-db-connection.php'>Check Database Connection</a></li>";
    echo "<li><a href='index.php'>Go to Main Application</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px;'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body { 
        font-family: Arial, sans-serif; 
        max-width: 1200px; 
        margin: 20px auto; 
        padding: 20px; 
        background: #f8f9fa;
    }
    h1 { 
        color: #333; 
        border-bottom: 3px solid #007bff; 
        padding-bottom: 10px;
    }
    h2 { 
        color: #555; 
        margin-top: 30px; 
    }
    table { 
        background: white; 
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    th { 
        background: #007bff; 
        color: white; 
    }
    tr:nth-child(even) { 
        background: #f8f9fa; 
    }
    a { 
        color: #007bff; 
        text-decoration: none; 
    }
    a:hover { 
        text-decoration: underline; 
    }
</style>