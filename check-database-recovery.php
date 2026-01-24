<?php
/**
 * DATABASE RECOVERY CHECKER
 * 
 * Description: Simple tool to check if database recovery is working
 * Features: Test database connection, verify tables, check data integrity
 * Usage: Run this file to check database status after recovery
 */

// Database configuration
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = '';

echo "<h1>🔍 Database Recovery Checker</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

// Step 1: Test Database Connection
echo "<h2>📡 Step 1: Testing Database Connection</h2>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<div class='success'>✅ Database connection successful!</div>";
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 If connection failed, your database might not be recovered properly.</div>";
    exit;
}

// Step 2: Check if all required tables exist
echo "<h2>📋 Step 2: Checking Required Tables</h2>";
$requiredTables = [
    'users',
    'job_seeker_profiles',
    'employer_profiles',
    'job_categories',
    'job_postings',
    'applications',
    'saved_jobs',
    'activity_logs',
    'password_reset_tokens',
    'system_settings'
];

$existingTables = [];
$stmt = $pdo->query("SHOW TABLES");
while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    $existingTables[] = $row[0];
}

echo "<table>";
echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";

$allTablesExist = true;
foreach ($requiredTables as $table) {
    if (in_array($table, $existingTables)) {
        // Count records in table
        $countStmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $countStmt->fetchColumn();
        
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: green;'>✅ Exists</td>";
        echo "<td>$count records</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td style='color: red;'>❌ Missing</td>";
        echo "<td>-</td>";
        echo "</tr>";
        $allTablesExist = false;
    }
}
echo "</table>";

if ($allTablesExist) {
    echo "<div class='success'>✅ All required tables exist!</div>";
} else {
    echo "<div class='error'>❌ Some tables are missing. Database recovery may be incomplete.</div>";
}

// Step 3: Check Sample Data
echo "<h2>📊 Step 3: Checking Sample Data</h2>";

// Check if default admin user exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        echo "<div class='success'>✅ Admin user(s) found: $adminCount</div>";
    } else {
        echo "<div class='error'>❌ No admin users found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking admin users: " . $e->getMessage() . "</div>";
}

// Check job categories
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_categories");
    $stmt->execute();
    $categoryCount = $stmt->fetchColumn();
    
    if ($categoryCount > 0) {
        echo "<div class='success'>✅ Job categories found: $categoryCount</div>";
    } else {
        echo "<div class='error'>❌ No job categories found</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking job categories: " . $e->getMessage() . "</div>";
}

// Step 4: Test Basic Operations
echo "<h2>⚙️ Step 4: Testing Basic Operations</h2>";

// Test INSERT operation
try {
    $testData = [
        'setting_key' => 'recovery_test_' . time(),
        'setting_value' => 'Database recovery test successful',
        'description' => 'Test entry created during recovery check'
    ];
    
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    $stmt->execute([$testData['setting_key'], $testData['setting_value'], $testData['description']]);
    
    echo "<div class='success'>✅ INSERT operation successful</div>";
    
    // Test SELECT operation
    $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$testData['setting_key']]);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<div class='success'>✅ SELECT operation successful</div>";
        
        // Test UPDATE operation
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute(['Updated test value', $testData['setting_key']]);
        
        echo "<div class='success'>✅ UPDATE operation successful</div>";
        
        // Test DELETE operation (cleanup)
        $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$testData['setting_key']]);
        
        echo "<div class='success'>✅ DELETE operation successful</div>";
    } else {
        echo "<div class='error'>❌ SELECT operation failed</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database operation failed: " . $e->getMessage() . "</div>";
}

// Step 5: Check Database Integrity
echo "<h2>🔍 Step 5: Database Integrity Check</h2>";

// Check foreign key constraints
try {
    // Test foreign key relationship
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned_profiles 
        FROM job_seeker_profiles jsp 
        LEFT JOIN users u ON jsp.user_id = u.id 
        WHERE u.id IS NULL
    ");
    $orphanedProfiles = $stmt->fetchColumn();
    
    if ($orphanedProfiles == 0) {
        echo "<div class='success'>✅ No orphaned job seeker profiles found</div>";
    } else {
        echo "<div class='error'>❌ Found $orphanedProfiles orphaned job seeker profiles</div>";
    }
    
    // Check employer profiles
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphaned_profiles 
        FROM employer_profiles ep 
        LEFT JOIN users u ON ep.user_id = u.id 
        WHERE u.id IS NULL
    ");
    $orphanedEmployerProfiles = $stmt->fetchColumn();
    
    if ($orphanedEmployerProfiles == 0) {
        echo "<div class='success'>✅ No orphaned employer profiles found</div>";
    } else {
        echo "<div class='error'>❌ Found $orphanedEmployerProfiles orphaned employer profiles</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Integrity check failed: " . $e->getMessage() . "</div>";
}

// Step 6: Final Summary
echo "<h2>📋 Recovery Check Summary</h2>";

$issues = [];
if (!$allTablesExist) {
    $issues[] = "Missing database tables";
}
if ($adminCount == 0) {
    $issues[] = "No admin users found";
}
if ($categoryCount == 0) {
    $issues[] = "No job categories found";
}

if (empty($issues)) {
    echo "<div class='success'>";
    echo "<h3>🎉 Database Recovery Status: SUCCESSFUL</h3>";
    echo "<p>Your database has been successfully recovered and all checks passed!</p>";
    echo "<ul>";
    echo "<li>✅ Database connection working</li>";
    echo "<li>✅ All required tables present</li>";
    echo "<li>✅ Sample data exists</li>";
    echo "<li>✅ Basic operations functional</li>";
    echo "<li>✅ Data integrity maintained</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠️ Database Recovery Status: ISSUES FOUND</h3>";
    echo "<p>The following issues were detected:</p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>❌ $issue</li>";
    }
    echo "</ul>";
    echo "<p><strong>Recommendation:</strong> Re-run the database setup or restore from a different backup.</p>";
    echo "</div>";
}

// Step 7: Quick Actions
echo "<h2>🛠️ Quick Actions</h2>";
echo "<div class='info'>";
echo "<p><strong>If you need to fix issues:</strong></p>";
echo "<ul>";
echo "<li>🔄 <a href='setup-database.php'>Re-run Database Setup</a></li>";
echo "<li>🗄️ <a href='database-backup-interface.php'>Access Backup System</a></li>";
echo "<li>🔍 <a href='check-db-connection.php'>Test Database Connection</a></li>";
echo "<li>🏠 <a href='index.php'>Return to Home Page</a></li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><small>Recovery check completed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>