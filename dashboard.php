<?php
/**
 * GENERAL DASHBOARD
 * 
 * Description: Universal dashboard that redirects users to their specific dashboards
 * Features:
 * - User type detection and routing
 * - Quick access navigation
 * - Recent activity overview
 * - System notifications
 * - User-specific dashboard redirection
 * - Fallback dashboard for undefined user types
 * 
 * User Types: All authenticated users
 * Access: Restricted to logged-in users, redirects based on user type
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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
    $user_type = $_SESSION['user_type'];
    
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user_type === 'job_seeker') {
        // Get job seeker profile
        $stmt = $pdo->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        // Get applications
        $stmt = $pdo->prepare("
            SELECT a.*, jp.title as job_title, jp.location, ep.company_name 
            FROM applications a 
            JOIN job_postings jp ON a.job_id = jp.id 
            LEFT JOIN users u ON jp.employer_id = u.id 
            LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
            WHERE a.job_seeker_id = ? 
            ORDER BY a.applied_date DESC
        ");
        $stmt->execute([$user_id]);
        $applications = $stmt->fetchAll();
        
    } elseif ($user_type === 'employer') {
        // Get employer profile
        $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        // Get job postings with detailed application stats
        $stmt = $pdo->prepare("
            SELECT jp.*, 
                   COUNT(a.id) as application_count,
                   COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count,
                   COUNT(CASE WHEN a.status = 'accepted' THEN 1 END) as accepted_count,
                   COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as rejected_count
            FROM job_postings jp 
            LEFT JOIN applications a ON jp.id = a.job_id 
            WHERE jp.employer_id = ? 
            GROUP BY jp.id 
            ORDER BY jp.posted_date DESC
        ");
        $stmt->execute([$user_id]);
        $job_postings = $stmt->fetchAll();
        
        // Get recent applications for this employer
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, jp.title as job_title
            FROM applications a
            JOIN users u ON a.job_seeker_id = u.id
            JOIN job_postings jp ON a.job_id = jp.id
            WHERE jp.employer_id = ? AND a.status = 'pending'
            ORDER BY a.applied_date DESC
            LIMIT 5
        ");
        $stmt->execute([$user_id]);
        $recent_applications = $stmt->fetchAll();
        
    } elseif ($user_type === 'admin') {
        // Get admin statistics
        $admin_stats = [];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $admin_stats['total_users'] = $stmt->fetch()['count'];
        
        // Active jobs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_postings WHERE is_active = TRUE");
        $admin_stats['active_jobs'] = $stmt->fetch()['count'];
        
        // Total applications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM applications");
        $admin_stats['total_applications'] = $stmt->fetch()['count'];
        
        // Recent registrations (last 30 days)
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $admin_stats['recent_registrations'] = $stmt->fetch()['count'];
        
        // Get recent users for activity feed
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
            LIMIT 10
        ");
        $recent_users = $stmt->fetchAll();
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JobPortal</title>
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
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
            color: #2c3e50;
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
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #3498db;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
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

        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-outline:hover {
            background: #3498db;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .user-info {
            color: #666;
            font-size: 1.1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .stat-card p {
            color: #666;
        }

        .stat-card.applications i { color: #3498db; }
        .stat-card.pending i { color: #f39c12; }
        .stat-card.jobs i { color: #27ae60; }
        .stat-card.views i { color: #9b59b6; }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
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

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status.pending { background: #fff3cd; color: #856404; }
        .status.reviewed { background: #d4edda; color: #155724; }
        .status.accepted { background: #d1ecf1; color: #0c5460; }
        .status.rejected { background: #f8d7da; color: #721c24; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-briefcase"></i> JobPortal</h2>
            </div>
            <div class="nav-links">
                <?php if ($user_type === 'job_seeker'): ?>
                    <a href="home.php" class="nav-link">Home</a>
                <?php elseif ($user_type === 'employer'): ?>
                    <a href="employer-home.php" class="nav-link">Home</a>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Home</a>
                <?php endif; ?>
                <a href="jobs.php" class="nav-link">Jobs</a>
                <a href="dashboard.php" class="nav-link" style="color: #3498db;">Dashboard</a>
                <?php if ($user_type === 'employer'): ?>
                    <a href="post-job.php" class="btn btn-primary">Post Job</a>
                <?php elseif ($user_type === 'admin'): ?>
                    <a href="admin.php" class="btn btn-primary">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
            <div class="user-info">
                <?= ucfirst(str_replace('_', ' ', $user_type)) ?> Dashboard
            </div>
        </div>

        <?php if ($user_type === 'job_seeker'): ?>
            <!-- Job Seeker Dashboard -->
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h2 style="margin-bottom: 0;">Job Seeker Dashboard</h2>
                    <a href="jobs.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem;">
                        <i class="fas fa-search"></i> Browse Jobs
                    </a>
                </div>

                <!-- Application Status Summary -->
                <?php if (!empty($applications)): ?>
                    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
                        <h3 style="margin-bottom: 15px; color: #2c3e50;">My Application Status</h3>
                        <div style="display: flex; gap: 30px; align-items: center;">
                            <div>
                                <span style="font-size: 1.5rem; font-weight: bold; color: #3498db;"><?= count($applications) ?></span>
                                <span style="color: #666; margin-left: 5px;">Total Applied</span>
                            </div>
                            <div>
                                <span style="font-size: 1.5rem; font-weight: bold; color: #f39c12;"><?= count(array_filter($applications, fn($app) => $app['status'] === 'pending')) ?></span>
                                <span style="color: #666; margin-left: 5px;">Pending</span>
                            </div>
                            <div>
                                <span style="font-size: 1.5rem; font-weight: bold; color: #27ae60;"><?= count(array_filter($applications, fn($app) => $app['status'] === 'accepted')) ?></span>
                                <span style="color: #666; margin-left: 5px;">Accepted</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recent Applications -->
                <?php if (!empty($applications)): ?>
                    <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 30px;">
                        <div style="padding: 20px; border-bottom: 1px solid #e9ecef;">
                            <h3 style="margin: 0; color: #2c3e50;">Recent Applications</h3>
                        </div>
                        <div style="display: grid; gap: 0;">
                            <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                                <div style="padding: 20px; border-bottom: 1px solid #f8f9fa; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="margin: 0 0 5px 0; color: #2c3e50;"><?= htmlspecialchars($app['job_title']) ?></h4>
                                        <div style="color: #666; font-size: 0.9rem;">
                                            <i class="fas fa-building"></i> <?= htmlspecialchars($app['company_name'] ?? 'Company') ?> | 
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($app['location']) ?> | 
                                            <i class="fas fa-calendar"></i> Applied <?= date('M j, Y', strtotime($app['applied_date'])) ?>
                                        </div>
                                        <?php if ($app['notes'] && $app['status'] !== 'pending'): ?>
                                            <div style="margin-top: 8px; padding: 8px 12px; background: #f8f9fa; border-radius: 5px; font-size: 0.9rem;">
                                                <strong>Employer Comment:</strong> <?= htmlspecialchars($app['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="status <?= $app['status'] ?>" style="padding: 8px 16px; font-weight: 500;">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Available Jobs Section -->
                <?php
                // Get latest available jobs for job seekers
                $stmt = $pdo->prepare("
                    SELECT jp.*, jc.name as category_name, ep.company_name 
                    FROM job_postings jp 
                    LEFT JOIN job_categories jc ON jp.category_id = jc.id 
                    LEFT JOIN users u ON jp.employer_id = u.id 
                    LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
                    WHERE jp.is_active = TRUE 
                    AND jp.id NOT IN (
                        SELECT job_id FROM applications WHERE job_seeker_id = ?
                    )
                    ORDER BY jp.posted_date DESC 
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
                $available_jobs = $stmt->fetchAll();
                ?>

                <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">
                    <div style="padding: 20px; border-bottom: 1px solid #e9ecef; display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0; color: #2c3e50;">Latest Job Opportunities</h3>
                        <a href="jobs.php" class="btn btn-outline">View All Jobs</a>
                    </div>

                    <?php if (empty($available_jobs)): ?>
                        <div style="text-align: center; padding: 60px 20px; color: #666;">
                            <i class="fas fa-search" style="font-size: 4rem; margin-bottom: 20px; color: #bdc3c7;"></i>
                            <h3>No New Jobs Available</h3>
                            <p>You've applied to all current job postings. Check back later for new opportunities!</p>
                            <a href="jobs.php" class="btn btn-primary" style="margin-top: 15px;">Browse All Jobs</a>
                        </div>
                    <?php else: ?>
                        <div style="display: grid; gap: 0;">
                            <?php foreach ($available_jobs as $job): ?>
                                <div style="padding: 25px; border-bottom: 1px solid #f8f9fa;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                        <div>
                                            <h4 style="margin: 0 0 8px 0; color: #2c3e50;"><?= htmlspecialchars($job['title']) ?></h4>
                                            <div style="color: #666; margin-bottom: 10px;">
                                                <i class="fas fa-building"></i> <?= htmlspecialchars($job['company_name'] ?? 'Company') ?> | 
                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?> | 
                                                <i class="fas fa-briefcase"></i> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?>
                                            </div>
                                            <div style="color: #666; font-size: 0.9rem;">
                                                <?= nl2br(htmlspecialchars(substr($job['description'], 0, 150))) ?>...
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                                <div style="color: #27ae60; font-weight: 600; margin-bottom: 10px;">
                                                    $<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div style="color: #999; font-size: 0.8rem;">
                                                Posted <?= date('M j', strtotime($job['posted_date'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; gap: 10px; margin-top: 15px;">
                                        <button onclick="applyToJob(<?= $job['id'] ?>)" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                        <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($applications)): ?>
                    <div style="text-align: center; padding: 60px 20px; color: #666; margin-top: 30px;">
                        <i class="fas fa-rocket" style="font-size: 5rem; margin-bottom: 20px; color: #bdc3c7;"></i>
                        <h3>Start Your Job Search!</h3>
                        <p>Browse available positions and apply to jobs that match your skills and interests.</p>
                        <a href="jobs.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem; margin-top: 20px;">
                            <i class="fas fa-search"></i> Find Jobs Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($user_type === 'employer'): ?>
            <!-- Simple Employer Dashboard -->
            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h2 style="margin-bottom: 0;">Employer Dashboard</h2>
                    <a href="post-job.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem;">
                        <i class="fas fa-plus"></i> Post New Job
                    </a>
                </div>
                
                <?php if (empty($job_postings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase" style="font-size: 5rem; color: #bdc3c7; margin-bottom: 20px;"></i>
                        <h3>Welcome to JobPortal!</h3>
                        <p>Start by posting your first job to attract qualified candidates.</p>
                        <a href="post-job.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1rem; margin-top: 20px;">
                            <i class="fas fa-plus"></i> Post Your First Job
                        </a>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 20px;">
                        <?php foreach ($job_postings as $job): ?>
                            <div style="background: white; border: 1px solid #e9ecef; border-radius: 10px; padding: 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <h3 style="color: #2c3e50; margin-bottom: 8px;"><?= htmlspecialchars($job['title']) ?></h3>
                                        <div style="color: #666; margin-bottom: 10px;">
                                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?> | 
                                            <i class="fas fa-calendar"></i> Posted <?= date('M j, Y', strtotime($job['posted_date'])) ?>
                                        </div>
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <span style="color: #666;">
                                                <i class="fas fa-users"></i> <?= $job['application_count'] ?> Applications
                                            </span>
                                            <?php if ($job['pending_count'] > 0): ?>
                                                <span style="background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: 500;">
                                                    <?= $job['pending_count'] ?> Need Review
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <span class="status <?= $job['is_active'] ? 'reviewed' : 'pending' ?>" style="padding: 6px 12px;">
                                        <?= $job['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                
                                <div style="display: flex; gap: 10px; margin-top: 20px;">
                                    <?php if ($job['application_count'] > 0): ?>
                                        <a href="manage-applications.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-users"></i> Review Applications (<?= $job['pending_count'] ?>)
                                        </a>
                                    <?php endif; ?>
                                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i> View Job
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($user_type === 'admin'): ?>
            <!-- Admin Dashboard -->
            <div class="stats-grid">
                <div class="stat-card applications">
                    <i class="fas fa-users"></i>
                    <h3><?= $admin_stats['total_users'] ?? 0 ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card jobs">
                    <i class="fas fa-briefcase"></i>
                    <h3><?= $admin_stats['active_jobs'] ?? 0 ?></h3>
                    <p>Active Jobs</p>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-paper-plane"></i>
                    <h3><?= $admin_stats['total_applications'] ?? 0 ?></h3>
                    <p>Applications</p>
                </div>
                <div class="stat-card views">
                    <i class="fas fa-user-plus"></i>
                    <h3><?= $admin_stats['recent_registrations'] ?? 0 ?></h3>
                    <p>New Users (30 days)</p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="section">
                <h2>Quick Actions</h2>
                <div class="admin-actions" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                    <a href="admin.php" class="btn btn-primary" style="padding: 20px; text-align: center; display: block;">
                        <i class="fas fa-shield-alt" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        Full Admin Panel
                    </a>
                    <a href="jobs.php" class="btn btn-outline" style="padding: 20px; text-align: center; display: block;">
                        <i class="fas fa-briefcase" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        View All Jobs
                    </a>
                    <a href="#" onclick="exportData()" class="btn btn-outline" style="padding: 20px; text-align: center; display: block;">
                        <i class="fas fa-download" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        Export Data
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="section">
                <h2>Recent Activity</h2>
                <?php if (!empty($recent_users)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recent_users, 0, 5) as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                    <td>
                                        <span class="status <?= $user['user_type'] === 'job_seeker' ? 'reviewed' : 'pending' ?>">
                                            <?= ucfirst(str_replace('_', ' ', $user['user_type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?= $user['is_active'] ? 'reviewed' : 'rejected' ?>">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <a href="admin.php" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">Manage</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-users"></i>
                        <h3>No Recent Activity</h3>
                        <p>User activity will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function exportData() {
            if (confirm('Export system data to CSV? This will include user statistics and job data.')) {
                // Create a simple CSV export
                const data = [
                    ['Metric', 'Value'],
                    ['Total Users', '<?= $admin_stats['total_users'] ?? 0 ?>'],
                    ['Active Jobs', '<?= $admin_stats['active_jobs'] ?? 0 ?>'],
                    ['Total Applications', '<?= $admin_stats['total_applications'] ?? 0 ?>'],
                    ['Recent Registrations (30 days)', '<?= $admin_stats['recent_registrations'] ?? 0 ?>'],
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
                link.setAttribute("download", "jobportal_stats_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        }

        function applyToJob(jobId) {
            if (confirm('Are you sure you want to apply for this job?')) {
                fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'apply_job',
                        job_id: jobId
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Application submitted successfully!');
                        location.reload(); // Refresh to update the job list
                    } else {
                        alert(result.message);
                    }
                })
                .catch(error => {
                    alert('Network error. Please try again.');
                });
            }
        }
    </script>
</body>
</html>