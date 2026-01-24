<?php
/**
 * DATABASE SETUP UTILITY
 * 
 * Description: Automated database installation and configuration tool
 * Features:
 * - Database creation and initialization
 * - Schema.sql execution
 * - Sample data insertion
 * - Configuration validation
 * - Error handling and reporting
 * - Setup progress tracking
 * - Database connection testing
 * 
 * User Types: System Administrator
 * Access: Installation/setup tool (should be removed after setup)
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change this if you have a password
define('DB_NAME', 'job_portal');
define('SCHEMA_FILE', 'database/schema.sql');

echo "<h1>Job Portal - Automatic Database Setup</h1>";
echo "<hr>";

try {
    // Step 1: Connect to MySQL server
    echo "<h2>Step 1: Connecting to MySQL Server...</h2>";
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    echo "<p style='color: green;'>✓ Connected successfully!</p>";
    
    // Step 2: Create database
    echo "<h2>Step 2: Creating Database...</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ Database '" . DB_NAME . "' created/verified!</p>";
    
    // Step 3: Use the database
    echo "<h2>Step 3: Selecting Database...</h2>";
    $pdo->exec("USE " . DB_NAME);
    echo "<p style='color: green;'>✓ Using database '" . DB_NAME . "'</p>";
    
    // Step 4: Read and execute schema file
    echo "<h2>Step 4: Running Schema File...</h2>";
    
    if (!file_exists(SCHEMA_FILE)) {
        throw new Exception("Schema file not found: " . SCHEMA_FILE);
    }
    
    $sql = file_get_contents(SCHEMA_FILE);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    echo "<p>Found " . count($statements) . " SQL statements to execute...</p>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (PDOException $e) {
            $errorCount++;
            // Only show critical errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<p style='color: orange;'>⚠ Warning: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p style='color: green;'>✓ Executed $successCount statements successfully!</p>";
    if ($errorCount > 0) {
        echo "<p style='color: orange;'>⚠ $errorCount statements had warnings (likely already existed)</p>";
    }
    
    // Step 5: Verify tables
    echo "<h2>Step 5: Verifying Tables...</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p style='color: green;'>✓ Created " . count($tables) . " tables:</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $countStmt->fetch()['count'];
        echo "<li><strong>$table</strong> - $count records</li>";
    }
    echo "</ul>";
    
    // Step 6: Success message
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Database Setup Complete!</h2>";
    echo "<p><strong>Your job_portal database is ready to use!</strong></p>";
    
    echo "<h3>Default Login Credentials:</h3>";
    echo "<div style='background: #e8f5e9; padding: 15px; border-left: 4px solid #4CAF50;'>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin@jobportal.com / password</li>";
    echo "<li><strong>Employer:</strong> employer@example.com / password</li>";
    echo "<li><strong>Job Seeker:</strong> jobseeker@example.com / password</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test the connection: <a href='test-schema-connection.php'>test-schema-connection.php</a></li>";
    echo "<li>Update your config files with database credentials</li>";
    echo "<li>Start using your job portal application!</li>";
    echo "</ol>";
    
    echo "<p><a href='index.php' style='display: inline-block; background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Home Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Setup Failed!</h2>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP/MySQL is running</li>";
    echo "<li>Check database credentials in this file (lines 8-10)</li>";
    echo "<li>Verify the schema file exists at: " . SCHEMA_FILE . "</li>";
    echo "<li>Try manual setup via phpMyAdmin</li>";
    echo "</ol>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 900px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #333;
        border-bottom: 3px solid #4CAF50;
        padding-bottom: 10px;
    }
    h2 {
        color: #555;
        margin-top: 20px;
        border-left: 4px solid #4CAF50;
        padding-left: 10px;
    }
    ul, ol {
        line-height: 1.8;
    }
    a {
        color: #4CAF50;
    }
</style>
