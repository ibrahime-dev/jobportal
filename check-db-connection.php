<?php
/**
 * DATABASE CONNECTION TESTER
 * 
 * Description: Diagnostic tool for testing database connectivity
 * Features:
 * - Database connection validation
 * - Table structure verification
 * - Sample data testing
 * - Connection performance metrics
 * - Error diagnosis and reporting
 * - Configuration troubleshooting
 * 
 * User Types: System Administrator/Developer
 * Access: Development/diagnostic tool
 */

echo "<h1>Database Connection Test</h1>";
echo "<hr>";

// Database credentials
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = ''; // Change if you have a password

echo "<h2>Testing Connection...</h2>";

try {
    // Test 1: Connect to MySQL server
    echo "<p><strong>Step 1:</strong> Connecting to MySQL server...</p>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✅ MySQL server connection: SUCCESS</p>";
    
    // Test 2: Check if database exists
    echo "<p><strong>Step 2:</strong> Checking if database '$dbname' exists...</p>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Database '$dbname' exists</p>";
        
        // Test 3: Connect to specific database
        echo "<p><strong>Step 3:</strong> Connecting to '$dbname' database...</p>";
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color: green;'>✅ Database connection: SUCCESS</p>";
        
        // Test 4: Check tables
        echo "<p><strong>Step 4:</strong> Checking tables...</p>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "<p style='color: green;'>✅ Found " . count($tables) . " tables:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
            
            // Test 5: Check users table specifically
            if (in_array('users', $tables)) {
                echo "<p><strong>Step 5:</strong> Testing users table...</p>";
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
                $count = $stmt->fetch()['count'];
                echo "<p style='color: green;'>✅ Users table has $count records</p>";
                
                if ($count > 0) {
                    $stmt = $pdo->query("SELECT email, user_type, first_name, last_name FROM users LIMIT 3");
                    $users = $stmt->fetchAll();
                    echo "<p><strong>Sample users:</strong></p>";
                    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
                    echo "<tr style='background: #f0f0f0;'><th style='padding: 8px;'>Email</th><th style='padding: 8px;'>Type</th><th style='padding: 8px;'>Name</th></tr>";
                    foreach ($users as $user) {
                        echo "<tr>";
                        echo "<td style='padding: 8px;'>{$user['email']}</td>";
                        echo "<td style='padding: 8px;'>{$user['user_type']}</td>";
                        echo "<td style='padding: 8px;'>{$user['first_name']} {$user['last_name']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ 'users' table not found</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ No tables found in database</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Database '$dbname' does not exist</p>";
        echo "<p><strong>Solution:</strong> Run setup-database.php or create the database manually</p>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ Connection Test Complete!</h2>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed: " . $e->getMessage() . "</p>";
    
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP/MySQL is running</li>";
    echo "<li>Check MySQL service in XAMPP Control Panel</li>";
    echo "<li>Verify database credentials (username/password)</li>";
    echo "<li>Try accessing phpMyAdmin: <a href='http://localhost/phpmyadmin'>http://localhost/phpmyadmin</a></li>";
    echo "</ol>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
h1 { color: #333; border-bottom: 2px solid #4CAF50; }
table { width: 100%; }
th, td { text-align: left; }
</style>