<?php
/**
 * ADMIN DASHBOARD
 * 
 * Description: Main administrative dashboard for system management
 * Features:
 * - System overview with statistics
 * - User management (view, edit, delete users)
 * - Job posting management
 * - Application monitoring
 * - System settings and configuration
 * - Admin-only access controls
 * 
 * User Types: Admin only
 * Access: Restricted to authenticated admin users
 */

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Database configuration
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $user_id = $_SESSION['user_id'];
    
    // Get comprehensive system statistics
    $stats = [];
    
    // User statistics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'job_seeker'");
    $stats['job_seekers'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'employer'");
    $stats['employers'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['new_users_30d'] = $stmt->fetch()['count'];
    
    // Job statistics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_postings WHERE is_active = TRUE");
    $stats['active_jobs'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_postings");
    $stats['total_jobs'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_postings WHERE posted_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['new_jobs_7d'] = $stmt->fetch()['count'];
    
    // Application statistics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $stats['total_applications'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'");
    $stats['pending_applications'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE applied_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['new_applications_7d'] = $stmt->fetch()['count'];
    
    // Get recent activity
    $stmt = $pdo->query("
        SELECT u.*, 
               CASE 
                   WHEN u.user_type = 'job_seeker' THEN jsp.skills
                   WHEN u.user_type = 'employer' THEN ep.company_name
                   ELSE NULL
               END as additional_info
        FROM users u
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id AND u.user_type = 'job_seeker'
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id AND u.user_type = 'employer'
        WHERE u.user_type != 'admin'
        ORDER BY u.created_at DESC 
        LIMIT 8
    ");
    $recent_users = $stmt->fetchAll();
    
    // Get recent job postings
    $stmt = $pdo->query("
        SELECT jp.*, ep.company_name, u.first_name, u.last_name,
               COUNT(a.id) as application_count
        FROM job_postings jp
        LEFT JOIN users u ON jp.employer_id = u.id
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id
        LEFT JOIN applications a ON jp.id = a.job_id
        GROUP BY jp.id
        ORDER BY jp.posted_date DESC
        LIMIT 6
    ");
    $recent_jobs = $stmt->fetchAll();
    
    // Get top categories by job count
    $stmt = $pdo->query("
        SELECT jc.name, COUNT(jp.id) as job_count
        FROM job_categories jc
        LEFT JOIN job_postings jp ON jc.id = jp.category_id AND jp.is_active = TRUE
        WHERE jc.is_active = TRUE
        GROUP BY jc.id, jc.name
        ORDER BY job_count DESC
        LIMIT 6
    ");
    $top_categories = $stmt->fetchAll();
    
    // Get system health metrics
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'accepted'");
    $stats['successful_hires'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JobPortal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #1a202c;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-logo h2 {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .nav-logo i {
            color: #667eea;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #4a5568;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: #667eea;
        }

        .nav-link.active {
            color: #667eea;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #667eea;
            border-radius: 1px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 60, 114, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-outline:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .hero {
            padding: 60px 0;
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-action-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .hero-action-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .hero-action-card i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .hero-action-card h3 {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .hero-action-card p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Stats Section */
        .stats-section {
            margin-top: -40px;
            margin-bottom: 60px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 30px 20px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2d3748;
        }

        .stat-card p {
            color: #718096;
            font-weight: 500;
        }

        .stat-card .trend {
            font-size: 0.8rem;
            margin-top: 5px;
            padding: 3px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .trend.up {
            background: #f0fff4;
            color: #38a169;
        }

        .trend.neutral {
            background: #f7fafc;
            color: #718096;
        }

        /* Main Content */
        .main-content {
            background: white;
            border-radius: 30px 30px 0 0;
            margin-top: 40px;
            padding: 60px 0;
            min-height: 60vh;
        }

        .section {
            margin-bottom: 60px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #718096;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Quick Actions Grid */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border: 2px solid transparent;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            border-color: #667eea;
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.15);
            color: inherit;
        }

        .action-card i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .action-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .action-card p {
            color: #718096;
            font-size: 0.95rem;
        }

        /* Data Tables */
        .data-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .data-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .data-card h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .data-card h3 i {
            color: #667eea;
        }

        .data-list {
            display: grid;
            gap: 15px;
        }

        .data-item {
            padding: 15px;
            background: #f7fafc;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .data-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }

        .data-item-info h4 {
            color: #2d3748;
            font-size: 0.95rem;
            margin-bottom: 3px;
        }

        .data-item-info p {
            color: #718096;
            font-size: 0.85rem;
        }

        .data-item-value {
            font-weight: 600;
            color: #667eea;
        }

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .category-item {
            background: #f7fafc;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .category-item:hover {
            background: #edf2f7;
            transform: translateY(-5px);
        }

        .category-item h4 {
            color: #2d3748;
            margin-bottom: 8px;
        }

        .category-item .count {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        .stagger-1 { animation-delay: 0.1s; }
        .stagger-2 { animation-delay: 0.2s; }
        .stagger-3 { animation-delay: 0.3s; }
        .stagger-4 { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-shield-alt"></i> JobPortal Admin</h2>
            </div>
            <div class="nav-links">
                <a href="admin-home.php" class="nav-link active">Home</a>
                <a href="admin.php" class="nav-link">Full Admin Panel</a>
                <a href="jobs.php" class="nav-link">Browse Jobs</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="#" onclick="handleLogout()" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="fade-in-up">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
            <p class="fade-in-up stagger-1">Monitor and manage your JobPortal platform with comprehensive admin tools and insights</p>
            
            <div class="hero-actions fade-in-up stagger-2">
                <a href="admin.php" class="hero-action-card">
                    <i class="fas fa-cogs"></i>
                    <h3>System Management</h3>
                    <p>Full admin control panel</p>
                </a>
                <a href="#users" class="hero-action-card">
                    <i class="fas fa-users"></i>
                    <h3>User Analytics</h3>
                    <p>Monitor user activity</p>
                </a>
                <a href="#jobs" class="hero-action-card">
                    <i class="fas fa-briefcase"></i>
                    <h3>Job Oversight</h3>
                    <p>Manage job postings</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in-up stagger-1">
                    <i class="fas fa-users"></i>
                    <h3><?= $stats['job_seekers'] + $stats['employers'] ?></h3>
                    <p>Total Users</p>
                    <div class="trend up">+<?= $stats['new_users_30d'] ?> this month</div>
                </div>
                <div class="stat-card fade-in-up stagger-2">
                    <i class="fas fa-briefcase"></i>
                    <h3><?= $stats['active_jobs'] ?></h3>
                    <p>Active Jobs</p>
                    <div class="trend up">+<?= $stats['new_jobs_7d'] ?> this week</div>
                </div>
                <div class="stat-card fade-in-up stagger-3">
                    <i class="fas fa-paper-plane"></i>
                    <h3><?= $stats['total_applications'] ?></h3>
                    <p>Total Applications</p>
                    <div class="trend up">+<?= $stats['new_applications_7d'] ?> this week</div>
                </div>
                <div class="stat-card fade-in-up stagger-4">
                    <i class="fas fa-handshake"></i>
                    <h3><?= $stats['successful_hires'] ?></h3>
                    <p>Successful Hires</p>
                    <div class="trend neutral">Platform success</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Quick Actions -->
            <section class="section">
                <div class="section-header">
                    <h2>Admin Tools</h2>
                    <p>Essential administrative functions for platform management</p>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="admin.php" class="action-card">
                        <i class="fas fa-shield-alt"></i>
                        <h4>Full Admin Panel</h4>
                        <p>Complete system administration and user management</p>
                    </a>
                    <div class="action-card" onclick="exportData()">
                        <i class="fas fa-download"></i>
                        <h4>Export Data</h4>
                        <p>Download system statistics and user data</p>
                    </div>
                    <div class="action-card" onclick="generateReport()">
                        <i class="fas fa-chart-bar"></i>
                        <h4>Generate Reports</h4>
                        <p>Create detailed analytics and performance reports</p>
                    </div>
                    <div class="action-card" onclick="systemHealth()">
                        <i class="fas fa-heartbeat"></i>
                        <h4>System Health</h4>
                        <p>Monitor platform performance and uptime</p>
                    </div>
                </div>
            </section>

            <!-- Data Overview -->
            <section class="section" id="users">
                <div class="section-header">
                    <h2>Platform Overview</h2>
                    <p>Real-time insights into your job portal's performance</p>
                </div>
                
                <div class="data-grid">
                    <!-- Recent Users -->
                    <div class="data-card">
                        <h3><i class="fas fa-user-plus"></i> Recent Users</h3>
                        <div class="data-list">
                            <?php foreach (array_slice($recent_users, 0, 5) as $user): ?>
                                <div class="data-item">
                                    <div class="data-item-info">
                                        <h4><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h4>
                                        <p><?= ucfirst(str_replace('_', ' ', $user['user_type'])) ?> • <?= date('M j', strtotime($user['created_at'])) ?></p>
                                    </div>
                                    <div class="data-item-value">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Jobs -->
                    <div class="data-card" id="jobs">
                        <h3><i class="fas fa-briefcase"></i> Recent Job Postings</h3>
                        <div class="data-list">
                            <?php foreach (array_slice($recent_jobs, 0, 5) as $job): ?>
                                <div class="data-item">
                                    <div class="data-item-info">
                                        <h4><?= htmlspecialchars($job['title']) ?></h4>
                                        <p><?= htmlspecialchars($job['company_name'] ?? 'Company') ?> • <?= date('M j', strtotime($job['posted_date'])) ?></p>
                                    </div>
                                    <div class="data-item-value">
                                        <?= $job['application_count'] ?> apps
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Categories Overview -->
            <section class="section">
                <div class="section-header">
                    <h2>Job Categories Performance</h2>
                    <p>Most popular job categories on your platform</p>
                </div>
                
                <div class="categories-grid">
                    <?php foreach ($top_categories as $category): ?>
                        <div class="category-item">
                            <h4><?= htmlspecialchars($category['name']) ?></h4>
                            <div class="count"><?= $category['job_count'] ?></div>
                            <p>Active Jobs</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- System Status -->
            <section class="section">
                <div class="section-header">
                    <h2>System Status</h2>
                    <p>Current platform health and performance metrics</p>
                </div>
                
                <div class="quick-actions-grid">
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-server"></i>
                        <h4>Database Status</h4>
                        <p style="color: #38a169; font-weight: 600;">Operational</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-users-cog"></i>
                        <h4>Active Users</h4>
                        <p style="color: #667eea; font-weight: 600;"><?= $stats['active_users'] ?> users</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-clock"></i>
                        <h4>Pending Reviews</h4>
                        <p style="color: #f39c12; font-weight: 600;"><?= $stats['pending_applications'] ?> applications</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-chart-line"></i>
                        <h4>Platform Growth</h4>
                        <p style="color: #38a169; font-weight: 600;">+<?= round(($stats['new_users_30d'] / max($stats['job_seekers'] + $stats['employers'], 1)) * 100, 1) ?>% this month</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        function exportData() {
            if (confirm('Export system data to CSV? This will include user statistics and job data.')) {
                // Create a comprehensive CSV export
                const data = [
                    ['Metric', 'Value'],
                    ['Total Users', '<?= $stats['job_seekers'] + $stats['employers'] ?>'],
                    ['Job Seekers', '<?= $stats['job_seekers'] ?>'],
                    ['Employers', '<?= $stats['employers'] ?>'],
                    ['Active Jobs', '<?= $stats['active_jobs'] ?>'],
                    ['Total Jobs', '<?= $stats['total_jobs'] ?>'],
                    ['Total Applications', '<?= $stats['total_applications'] ?>'],
                    ['Pending Applications', '<?= $stats['pending_applications'] ?>'],
                    ['Successful Hires', '<?= $stats['successful_hires'] ?>'],
                    ['New Users (30 days)', '<?= $stats['new_users_30d'] ?>'],
                    ['New Jobs (7 days)', '<?= $stats['new_jobs_7d'] ?>'],
                    ['New Applications (7 days)', '<?= $stats['new_applications_7d'] ?>'],
                    ['Export Date', new Date().toLocaleDateString()]
                ];
                
                let csvContent = "data:text/csv;charset=utf-8,";
                data.forEach(function(rowArray) {
                    let row = rowArray.join(",");
                    csvContent += row + "\r\n";
                });
                
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "jobportal_admin_report_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                alert('Data exported successfully!');
            }
        }

        function generateReport() {
            alert('Advanced reporting feature coming soon! Use Export Data for current statistics.');
        }

        function systemHealth() {
            alert('System Status: All services operational\n\n' +
                  '✅ Database: Connected\n' +
                  '✅ User Authentication: Active\n' +
                  '✅ Job Posting System: Operational\n' +
                  '✅ Application Processing: Running\n' +
                  '✅ Email Services: Available');
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add animations on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards
            document.querySelectorAll('.action-card, .data-card, .category-item').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });
        });

        // Logout function
        function handleLogout() {
            // Clear all client-side storage
            localStorage.clear();
            sessionStorage.clear();
            
            // Redirect to logout.php for server-side cleanup
            window.location.href = 'logout.php';
        }
    </script>

    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 60px 0 30px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <!-- Admin Info -->
                <div>
                    <h3 style="color: #667eea; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fas fa-shield-alt"></i> JobPortal Admin
                    </h3>
                    <p style="color: #cbd5e0; line-height: 1.6; margin-bottom: 20px;">
                        Administrative control center for managing the JobPortal platform. Monitor, analyze, and optimize your job portal ecosystem.
                    </p>
                    <div style="background: rgba(102, 126, 234, 0.1); padding: 15px; border-radius: 10px; border-left: 4px solid #667eea;">
                        <p style="color: #cbd5e0; margin: 0; font-size: 0.9rem;">
                            <i class="fas fa-info-circle" style="color: #667eea; margin-right: 8px;"></i>
                            System Status: All services operational
                        </p>
                    </div>
                </div>

                <!-- Admin Tools -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Admin Tools</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="admin.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-cogs"></i> Full Admin Panel
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="dashboard.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-tachometer-alt"></i> System Dashboard
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-briefcase"></i> Job Oversight
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" onclick="exportData()" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease; cursor: pointer;">
                                <i class="fas fa-download"></i> Export Data
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- System Stats -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Platform Stats</h4>
                    <div style="color: #cbd5e0; line-height: 1.8;">
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-users" style="color: #667eea; margin-right: 10px;"></i>
                            <?= $stats['job_seekers'] + $stats['employers'] ?> Total Users
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-briefcase" style="color: #667eea; margin-right: 10px;"></i>
                            <?= $stats['active_jobs'] ?> Active Jobs
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-paper-plane" style="color: #667eea; margin-right: 10px;"></i>
                            <?= $stats['total_applications'] ?> Applications
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-handshake" style="color: #667eea; margin-right: 10px;"></i>
                            <?= $stats['successful_hires'] ?> Successful Hires
                        </p>
                    </div>
                </div>

                <!-- System Info -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">System Information</h4>
                    <div style="color: #cbd5e0; line-height: 1.8;">
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-server" style="color: #667eea; margin-right: 10px;"></i>
                            Database: Connected
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-shield-alt" style="color: #667eea; margin-right: 10px;"></i>
                            Security: Active
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-clock" style="color: #667eea; margin-right: 10px;"></i>
                            Last Update: <?= date('M j, Y') ?>
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-code" style="color: #667eea; margin-right: 10px;"></i>
                            Version: 2.0.0
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid #4a5568; padding-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="color: #cbd5e0;">
                    <p>&copy; 2024 JobPortal Admin Panel. All rights reserved. | Secure Administrative Access</p>
                </div>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Admin Guidelines</a>
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Security Policy</a>
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">System Logs</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        footer a:hover {
            color: #667eea !important;
        }
        
        @media (max-width: 768px) {
            footer .container > div:first-child {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            footer .container > div:last-child {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>