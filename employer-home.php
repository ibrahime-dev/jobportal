<?php
/**
 * EMPLOYER DASHBOARD
 * 
 * Description: Main dashboard for employers to manage job postings and applications
 * Features:
 * - Job posting management (create, edit, delete)
 * - Application tracking and management
 * - Company profile management
 * - Candidate search and filtering
 * - Application status updates
 * - Employer analytics and insights
 * 
 * User Types: Employer only
 * Access: Restricted to authenticated employer users
 */

session_start();

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
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
    
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get employer profile
    $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    // Get job posting statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_jobs,
            COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_jobs,
            COUNT(CASE WHEN is_active = FALSE THEN 1 END) as inactive_jobs,
            SUM(applications_count) as total_applications
        FROM job_postings WHERE employer_id = ?
    ");
    $stmt->execute([$user_id]);
    $job_stats = $stmt->fetch();
    
    // Get application statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN a.status = 'accepted' THEN 1 END) as accepted_count,
            COUNT(CASE WHEN a.status = 'rejected' THEN 1 END) as rejected_count
        FROM applications a
        JOIN job_postings jp ON a.job_id = jp.id
        WHERE jp.employer_id = ?
    ");
    $stmt->execute([$user_id]);
    $app_stats = $stmt->fetch();
    
    // Get recent job postings
    $stmt = $pdo->prepare("
        SELECT jp.*, 
               COUNT(a.id) as application_count,
               COUNT(CASE WHEN a.status = 'pending' THEN 1 END) as pending_count
        FROM job_postings jp 
        LEFT JOIN applications a ON jp.id = a.job_id 
        WHERE jp.employer_id = ? 
        GROUP BY jp.id 
        ORDER BY jp.posted_date DESC 
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $recent_jobs = $stmt->fetchAll();
    
    // Get recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, u.first_name, u.last_name, jp.title as job_title,
               jsp.skills, jsp.experience_years
        FROM applications a
        JOIN users u ON a.job_seeker_id = u.id
        JOIN job_postings jp ON a.job_id = jp.id
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE jp.employer_id = ? AND a.status = 'pending'
        ORDER BY a.applied_date DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_applications = $stmt->fetchAll();
    
    // Get job categories for quick posting
    $stmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name LIMIT 8");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome <?= htmlspecialchars($_SESSION['first_name']) ?> - Employer Portal</title>
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
            color: #fa709a;
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
            color: #fa709a;
        }

        .nav-link.active {
            color: #fa709a;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #fa709a;
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
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.6);
        }

        .btn-success {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(72, 187, 120, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: #fa709a;
            border: 2px solid #fa709a;
        }

        .btn-outline:hover {
            background: #fa709a;
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

        /* Stats Cards */
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
            border-color: #fa709a;
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(250, 112, 154, 0.15);
            color: inherit;
        }

        .action-card i {
            font-size: 3rem;
            color: #fa709a;
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

        /* Jobs Grid */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }

        .job-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: #fa709a;
        }

        .job-card:hover::before {
            transform: scaleX(1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .job-title h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #718096;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .job-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .job-stat {
            text-align: center;
        }

        .job-stat .number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fa709a;
        }

        .job-stat .label {
            font-size: 0.8rem;
            color: #718096;
            text-transform: uppercase;
        }

        .job-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        /* Applications List */
        .applications-list {
            display: grid;
            gap: 20px;
        }

        .application-item {
            background: #f7fafc;
            border-radius: 16px;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .application-item:hover {
            background: #edf2f7;
            transform: translateX(5px);
        }

        .applicant-info h4 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .applicant-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .applicant-skills {
            margin-top: 8px;
        }

        .skill-tag {
            display: inline-block;
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-right: 5px;
        }

        .application-actions {
            display: flex;
            gap: 10px;
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active { background: #f0fff4; color: #38a169; }
        .status-inactive { background: #fed7d7; color: #e53e3e; }
        .status-pending { background: #fef5e7; color: #d69e2e; }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #cbd5e0;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4a5568;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .hero-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .job-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .application-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
                <h2><i class="fas fa-briefcase"></i> JobPortal</h2>
            </div>
            <div class="nav-links">
                <a href="employer-home.php" class="nav-link active">Home</a>
                <a href="jobs.php" class="nav-link">Browse Jobs</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="post-job.php" class="nav-link">Post Job</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="#" onclick="handleLogout()" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!--employer home page  Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="fade-in-up">Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
            <p class="fade-in-up stagger-1">Manage your job postings, review applications, and find the perfect candidates for your team</p>
            
            <div class="hero-actions fade-in-up stagger-2">
                <a href="post-job.php" class="hero-action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Post New Job</h3>
                    <p>Create and publish job openings</p>
                </a>
                <a href="manage-applications.php" class="hero-action-card">
                    <i class="fas fa-users"></i>
                    <h3>Review Applications</h3>
                    <p>Manage candidate applications</p>
                </a>
                <a href="dashboard.php" class="hero-action-card">
                    <i class="fas fa-chart-line"></i>
                    <h3>View Analytics</h3>
                    <p>Track hiring performance</p>
                </a>
            </div>
        </div>
    </section>

    <!--employyer home contents Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in-up stagger-1">
                    <i class="fas fa-briefcase"></i>
                    <h3><?= $job_stats['total_jobs'] ?? 0 ?></h3>
                    <p>Total Job Posts</p>
                </div>
                <div class="stat-card fade-in-up stagger-2">
                    <i class="fas fa-eye"></i>
                    <h3><?= $job_stats['active_jobs'] ?? 0 ?></h3>
                    <p>Active Jobs</p>
                </div>
                <div class="stat-card fade-in-up stagger-3">
                    <i class="fas fa-paper-plane"></i>
                    <h3><?= $app_stats['total_applications'] ?? 0 ?></h3>
                    <p>Total Applications</p>
                </div>
                <div class="stat-card fade-in-up stagger-4">
                    <i class="fas fa-clock"></i>
                    <h3><?= $app_stats['pending_count'] ?? 0 ?></h3>
                    <p>Pending Reviews</p>
                </div>
            </div>
        </div>
    </section>

    <!--  employer Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Quick Actions -->
            <section class="section">
                <div class="section-header">
                    <h2>Quick Actions</h2>
                    <p>Streamline your hiring process with these essential tools</p>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="post-job.php" class="action-card">
                        <i class="fas fa-plus-circle"></i>
                        <h4>Post New Job</h4>
                        <p>Create and publish a new job opening to attract qualified candidates</p>
                    </a>
                    <a href="manage-applications.php" class="action-card">
                        <i class="fas fa-inbox"></i>
                        <h4>Review Applications</h4>
                        <p>View and manage applications from job seekers</p>
                    </a>
                    <a href="dashboard.php" class="action-card">
                        <i class="fas fa-chart-bar"></i>
                        <h4>View Reports</h4>
                        <p>Analyze hiring metrics and application trends</p>
                    </a>
                    <a href="profile.php" class="action-card">
                        <i class="fas fa-building"></i>
                        <h4>Company Profile</h4>
                        <p>Update your company information and branding</p>
                    </a>
                </div>
            </section>

            <!-- Recent Job Postings -->
            <section class="section">
                <div class="section-header">
                    <h2>Your Recent Job Postings</h2>
                    <p>Monitor the performance of your latest job openings</p>
                </div>
                
                <?php if (empty($recent_jobs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h3>No Job Postings Yet</h3>
                        <p>Start by creating your first job posting to attract talented candidates.</p>
                        <a href="post-job.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-plus"></i> Post Your First Job
                        </a>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <div class="job-title">
                                        <h3><?= htmlspecialchars($job['title']) ?></h3>
                                        <div class="job-meta">
                                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                            <span><i class="fas fa-briefcase"></i> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></span>
                                            <span><i class="fas fa-calendar"></i> <?= date('M j', strtotime($job['posted_date'])) ?></span>
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?= $job['is_active'] ? 'active' : 'inactive' ?>">
                                        <?= $job['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </div>
                                
                                <div class="job-stats">
                                    <div class="job-stat">
                                        <div class="number"><?= $job['application_count'] ?></div>
                                        <div class="label">Applications</div>
                                    </div>
                                    <div class="job-stat">
                                        <div class="number"><?= $job['pending_count'] ?></div>
                                        <div class="label">Pending</div>
                                    </div>
                                    <div class="job-stat">
                                        <div class="number"><?= $job['views'] ?? 0 ?></div>
                                        <div class="label">Views</div>
                                    </div>
                                </div>
                                
                                <div class="job-actions">
                                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($job['application_count'] > 0): ?>
                                        <a href="manage-applications.php?job_id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-users"></i> Applications (<?= $job['pending_count'] ?>)
                                        </a>
                                    <?php endif; ?>
                                    <a href="post-job.php?edit=<?= $job['id'] ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 40px;">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-list"></i> View All Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recent Applications -->
            <?php if (!empty($recent_applications)): ?>
                <section class="section">
                    <div class="section-header">
                        <h2>Recent Applications</h2>
                        <p>New candidates waiting for your review</p>
                    </div>
                    
                    <div class="applications-list">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="application-item">
                                <div class="applicant-info">
                                    <h4><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></h4>
                                    <p>
                                        <i class="fas fa-briefcase"></i> Applied for: <?= htmlspecialchars($app['job_title']) ?> • 
                                        <i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($app['applied_date'])) ?>
                                    </p>
                                    <?php if ($app['skills']): ?>
                                        <div class="applicant-skills">
                                            <?php foreach (array_slice(explode(',', $app['skills']), 0, 3) as $skill): ?>
                                                <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="application-actions">
                                    <a href="manage-applications.php?job_id=<?= $app['job_id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Review
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="manage-applications.php" class="btn btn-outline">
                            <i class="fas fa-list"></i> View All Applications
                        </a>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Hiring Tips -->
            <section class="section">
                <div class="section-header">
                    <h2>Hiring Tips & Best Practices</h2>
                    <p>Improve your recruitment process with these expert recommendations</p>
                </div>
                
                <div class="quick-actions-grid">
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-lightbulb"></i>
                        <h4>Write Clear Job Descriptions</h4>
                        <p>Include specific requirements, responsibilities, and company culture to attract the right candidates</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-clock"></i>
                        <h4>Respond Quickly</h4>
                        <p>Fast response times improve candidate experience and increase acceptance rates</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-star"></i>
                        <h4>Showcase Your Company</h4>
                        <p>Complete your company profile with photos, values, and benefits to stand out</p>
                    </div>
                    <div class="action-card" style="cursor: default;">
                        <i class="fas fa-handshake"></i>
                        <h4>Provide Feedback</h4>
                        <p>Give constructive feedback to candidates, even when declining applications</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Add smooth scrolling and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
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
            document.querySelectorAll('.job-card, .action-card, .application-item').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });

            // Add click effects to action cards
            document.querySelectorAll('.action-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('div');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(102, 126, 234, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s ease-out;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

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
                <!-- Company Info -->
                <div>
                    <h3 style="color: #fa709a; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fas fa-briefcase"></i> JobPortal
                    </h3>
                    <p style="color: #cbd5e0; line-height: 1.6; margin-bottom: 20px;">
                        The premier platform for employers to find exceptional talent. Post jobs, manage applications, and build your dream team.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="color: #fa709a; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: #fa709a; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: #fa709a; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" style="color: #fa709a; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- Employer Tools -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Employer Tools</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="post-job.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-plus-circle"></i> Post New Job
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="manage-applications.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-users"></i> Manage Applications
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">

                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="profile.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-building"></i> Company Profile
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Resources</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Hiring Best Practices</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Salary Benchmarks</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Interview Templates</a>
                        </li>
                        <li style="margin-bottom: 10px;">
      
                        </li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Support</h4>
                    <div style="color: #cbd5e0; line-height: 1.8;">
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-envelope" style="color: #fa709a; margin-right: 10px;"></i>
                             contact ownemployer email
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-phone" style="color: #fa709a; margin-right: 10px;"></i>
                            +25191234568
                        </p>
                        <p style="margin-bottom: 10px;">
                           
                           
                        </p>
                        <p style="margin-bottom: 10px;">
                            <a href="#" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-question-circle" style="color: #fa709a; margin-right: 10px;"></i>
                                Help Center
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid #4a5568; padding-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="color: #cbd5e0;">
                    <p>&copy; 2025 JobPortal. All rights reserved. | Empowering employers worldwide</p>
                </div>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Privacy Policy</a>
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Terms of Service</a>
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Employer Agreement</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        footer a:hover {
            color: #fa709a !important;
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