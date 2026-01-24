<?php
/**
 * ADMIN INTERFACE
 * 
 * Description: Administrative interface for system management
 * Features:
 * - System configuration management
 * - Advanced user management tools
 * - Database administration
 * - System monitoring and logs
 * - Security settings
 * - Backup and restore functionality
 * - System maintenance tools
 * 
 * User Types: Admin only
 * Access: Restricted to authenticated admin users with elevated privileges
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
    
    // Get system statistics
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Users by type
    $stmt = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
    $user_types = $stmt->fetchAll();
    foreach ($user_types as $type) {
        $stats[$type['user_type'] . '_users'] = $type['count'];
    }
    
    // Total jobs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_postings WHERE is_active = TRUE");
    $stats['active_jobs'] = $stmt->fetch()['count'];
    
    // Total applications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
    $stats['total_applications'] = $stmt->fetch()['count'];
    
    // Recent registrations (last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['recent_registrations'] = $stmt->fetch()['count'];
    
    // Get recent users
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
        ORDER BY u.created_at DESC 
        LIMIT 10
    ");
    $recent_users = $stmt->fetchAll();
    
    // Get recent job postings
    $stmt = $pdo->query("
        SELECT jp.*, ep.company_name, u.first_name, u.last_name
        FROM job_postings jp
        LEFT JOIN users u ON jp.employer_id = u.id
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id
        ORDER BY jp.posted_date DESC
        LIMIT 10
    ");
    $recent_jobs = $stmt->fetchAll();
    
    // Handle admin actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'toggle_user_status':
                    $user_id = $_POST['user_id'];
                    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $success_message = "User status updated successfully!";
                    break;
                    
                case 'delete_job':
                    $job_id = $_POST['job_id'];
                    $stmt = $pdo->prepare("UPDATE job_postings SET is_active = FALSE WHERE id = ?");
                    $stmt->execute([$job_id]);
                    $success_message = "Job posting deactivated successfully!";
                    break;
            }
            
            // Refresh page to show updated data
            header('Location: admin.php');
            exit();
        }
    }
    
} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - JobPortal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .navbar {
            background: #2c3e50;
            color: white;
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
            color: white;
            font-size: 1.8rem;
        }

        .nav-logo i {
            color: #3498db;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #ecf0f1;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #3498db;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-outline {
            background: transparent;
            color: #ecf0f1;
            border: 2px solid #ecf0f1;
        }

        .btn-outline:hover {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .admin-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .stat-card p {
            color: #666;
            font-weight: 500;
        }

        .stat-card.users i { color: #3498db; }
        .stat-card.jobs i { color: #27ae60; }
        .stat-card.applications i { color: #f39c12; }
        .stat-card.recent i { color: #9b59b6; }

        .admin-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .admin-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .user-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-job_seeker {
            background: #cce5ff;
            color: #004085;
        }

        .type-employer {
            background: #fff3cd;
            color: #856404;
        }

        .type-admin {
            background: #f8d7da;
            color: #721c24;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
            </div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
                <a href="jobs.php" class="nav-link">Jobs</a>
                <a href="admin.php" class="nav-link" style="color: #3498db;">Admin</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Admin pannale Header -->
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt"></i> System Dashboard</h1>
            <p>Manage users, jobs, and monitor system performance</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success_message ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card users">
                <i class="fas fa-users"></i>
                <h3><?= $stats['total_users'] ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card jobs">
                <i class="fas fa-briefcase"></i>
                <h3><?= $stats['active_jobs'] ?></h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-card applications">
                <i class="fas fa-paper-plane"></i>
                <h3><?= $stats['total_applications'] ?></h3>
                <p>Total Applications</p>
            </div>
            <div class="stat-card recent">
                <i class="fas fa-user-plus"></i>
                <h3><?= $stats['recent_registrations'] ?></h3>
                <p>New Users (30 days)</p>
            </div>
        </div>

        <!-- User Type Breakdown -->
        <div class="admin-section">
            <h2><i class="fas fa-chart-pie"></i> User Distribution</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-user-tie"></i>
                    <h3><?= $stats['job_seeker_users'] ?? 0 ?></h3>
                    <p>Job Seekers</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-building"></i>
                    <h3><?= $stats['employer_users'] ?? 0 ?></h3>
                    <p>Employers</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3><?= $stats['admin_users'] ?? 0 ?></h3>
                    <p>Administrators</p>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="admin-section">
            <h2><i class="fas fa-users"></i> Recent Users</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Additional Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="user-type-badge type-<?= $user['user_type'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $user['user_type'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                            <td><?= htmlspecialchars(substr($user['additional_info'] ?? 'N/A', 0, 30)) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($user['user_type'] !== 'admin'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_user_status">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn <?= $user['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                                                    onclick="return confirm('Are you sure?')">
                                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Job Postings -->
        <div class="admin-section">
            <h2><i class="fas fa-briefcase"></i> Recent Job Postings</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Posted By</th>
                        <th>Posted Date</th>
                        <th>Applications</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_jobs as $job): ?>
                        <tr>
                            <td><?= htmlspecialchars($job['title']) ?></td>
                            <td><?= htmlspecialchars($job['company_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($job['location']) ?></td>
                            <td><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($job['posted_date'])) ?></td>
                            <td><?= $job['applications_count'] ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-primary">View</a>
                                    <?php if ($job['is_active']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_job">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Are you sure you want to deactivate this job?')">
                                                Deactivate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>