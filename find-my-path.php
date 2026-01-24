<?php
/**
 * JOB SEEKER NAVIGATION PAGE
 * 
 * Description: Navigation and guidance page for job seekers
 * Features:
 * - Career path recommendations
 * - Skill assessment tools
 * - Job market insights
 * - Career guidance resources
 * - Personalized job suggestions
 * - Industry trend analysis
 * - Professional development tips
 * 
 * User Types: Job Seeker focused, public access
 * Access: Public page with enhanced features for logged-in job seekers
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find My Project Path</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; font-weight: bold; font-size: 1.2em; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .url { background: #f5f5f5; padding: 10px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1> Success! You Found Your Project Path!</h1>
    
    <div class="success">
        ✅ Your project is accessible at this URL!
    </div>
    
    <div class="info">
        <h3>Your Project Information:</h3>
        <p><strong>Current URL:</strong> <span class="url"><?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></span></p>
        <p><strong>Document Root:</strong> <span class="url"><?php echo $_SERVER['DOCUMENT_ROOT']; ?></span></p>
        <p><strong>Script Path:</strong> <span class="url"><?php echo __FILE__; ?></span></p>
        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    </div>
    
    <h3>Now Try These Database Test Files:</h3>
    
    <?php
    $currentUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
    $testFiles = [
        'check-db-connection.php' => 'Basic Database Connection Test',
        'test-backend-config.php' => 'Backend Configuration Test',
        'setup-database.php' => 'Automatic Database Setup',
        'test-schema-connection.php' => 'Complete Database Test'
    ];
    
    echo "<ul>";
    foreach ($testFiles as $file => $description) {
        $url = $currentUrl . '/' . $file;
        echo "<li><a href='$url' target='_blank'>$description</a><br>";
        echo "<small class='url'>$url</small></li><br>";
    }
    echo "</ul>";
    ?>
    
    <div class="info">
        <h3>Quick Database Setup:</h3>
        <ol>
            <li>Make sure XAMPP is running (Apache + MySQL)</li>
            <li>Click on "Automatic Database Setup" above</li>
            <li>Then try "Complete Database Test"</li>
            <li>Finally, test your login at: <a href="<?php echo $currentUrl; ?>/index.php">index.php</a></li>
        </ol>
    </div>
    
    <h3>Default Login Credentials:</h3>
    <ul>
        <li><strong>Admin:</strong> admin@jobportal.com / password</li>
        <li><strong>Employer:</strong> employer@example.com / password</li>
        <li><strong>Job Seeker:</strong> jobseeker@example.com / password</li>
    </ul>
</body>
</html>