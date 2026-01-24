<?php
/**
 * JOB SEEKER HOME PAGE
 * 
 * Description: Main dashboard for job seekers to search and apply for jobs
 * Features:
 * - Job search with filters (location, category, salary)
 * - Saved jobs management
 * - Application tracking and status
 * - Profile management and resume upload
 * - Job recommendations based on profile
 * - Application history and analytics
 * 
 * User Types: Job Seeker only
 * Access: Restricted to authenticated job seeker users
 */

session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'job_seeker') {
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
    
    // Get job seeker profile
    $stmt = $pdo->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    // Get application statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_applications,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_count,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count
        FROM applications WHERE job_seeker_id = ?
    ");
    $stmt->execute([$user_id]);
    $app_stats = $stmt->fetch();
    
    // Get recent applications
    $stmt = $pdo->prepare("
        SELECT a.*, jp.title as job_title, jp.location, ep.company_name 
        FROM applications a 
        JOIN job_postings jp ON a.job_id = jp.id 
        LEFT JOIN users u ON jp.employer_id = u.id 
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
        WHERE a.job_seeker_id = ? 
        ORDER BY a.applied_date DESC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $recent_applications = $stmt->fetchAll();
    
    // Get featured jobs (latest jobs that user hasn't applied to)
    $stmt = $pdo->prepare("
        SELECT jp.*, jc.name as category_name, ep.company_name, ep.logo_filename 
        FROM job_postings jp 
        LEFT JOIN job_categories jc ON jp.category_id = jc.id 
        LEFT JOIN users u ON jp.employer_id = u.id 
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
        WHERE jp.is_active = TRUE 
        AND jp.id NOT IN (
            SELECT job_id FROM applications WHERE job_seeker_id = ?
        )
        ORDER BY jp.posted_date DESC 
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $featured_jobs = $stmt->fetchAll();
    
    // Get job categories for quick search
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
    <title>Welcome <?= htmlspecialchars($_SESSION['first_name']) ?> - JobPortal</title>
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
            color: #4facfe;
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
            color: #4facfe;
        }

        .nav-link.active {
            color: #4facfe;
        }

        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            right: 0;
            height: 2px;
            background: #4facfe;
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
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.6);
        }

        .btn-outline {
            background: transparent;
            color: #4facfe;
            border: 2px solid #4facfe;
        }

        .btn-outline:hover {
            background: #4facfe;
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

        .hero-search {
            background: white;
            border-radius: 20px;
            padding: 8px;
            display: flex;
            max-width: 600px;
            margin: 0 auto 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .hero-search input {
            flex: 1;
            border: none;
            padding: 16px 20px;
            font-size: 1.1rem;
            border-radius: 16px;
            outline: none;
        }

        .hero-search button {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .hero-search button:hover {
            transform: scale(1.05);
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

        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .category-card {
            background: linear-gradient(135deg, #f7fafc, #edf2f7);
            border: 2px solid transparent;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .category-card:hover {
            border-color: #4facfe;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.15);
        }

        .category-card i {
            font-size: 2rem;
            color: #4facfe;
            margin-bottom: 15px;
        }

        .category-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
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
            border-color: #4facfe;
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

        .job-company {
            color: #4facfe;
            font-weight: 500;
            margin-bottom: 12px;
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

        .job-description {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .job-salary {
            font-weight: 600;
            color: #38a169;
            font-size: 1.1rem;
        }

        .job-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        /* Recent Applications */
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

        .application-info h4 {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .application-info p {
            color: #718096;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef5e7; color: #d69e2e; }
        .status-accepted { background: #f0fff4; color: #38a169; }
        .status-rejected { background: #fed7d7; color: #e53e3e; }

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
            
            .hero-search {
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .job-footer {
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
    <!-- top of jobseeker Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><i class="fas fa-briefcase"></i> JobPortal</h2>
            </div>
            <div class="nav-links">
                <a href="home.php" class="nav-link active">Home</a>
                <a href="jobs.php" class="nav-link">All Jobs</a>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="profile.php" class="nav-link">Profile</a>
                <a href="#" onclick="handleLogout()" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- job seeker all home page Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="fade-in-up">Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</h1>
            <p class="fade-in-up stagger-1">Discover amazing career opportunities and take the next step in your professional journey</p>
            
            <form class="hero-search fade-in-up stagger-2" action="jobs.php" method="GET">
                <input type="text" name="keyword" placeholder="Search for jobs, companies, or skills..." 
                       value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                <button type="submit">
                    <i class="fas fa-search"></i> Search Jobs
                </button>
            </form>
        </div>
    </section>

    <!--total  Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card fade-in-up stagger-1">
                    <i class="fas fa-paper-plane"></i>
                    <h3><?= $app_stats['total_applications'] ?? 0 ?></h3>
                    <p>Applications Sent</p>
                </div>
                <div class="stat-card fade-in-up stagger-2">
                    <i class="fas fa-clock"></i>
                    <h3><?= $app_stats['pending_count'] ?? 0 ?></h3>
                    <p>Pending Reviews</p>
                </div>
                <div class="stat-card fade-in-up stagger-3">
                    <i class="fas fa-check-circle"></i>
                    <h3><?= $app_stats['accepted_count'] ?? 0 ?></h3>
                    <p>Accepted</p>
                </div>
                <div class="stat-card fade-in-up stagger-4">
                    <i class="fas fa-briefcase"></i>
                    <h3><?= count($featured_jobs) ?></h3>
                    <p>New Opportunities</p>
                </div>
            </div>
        </div>
    </section>

    <!--job seeker Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Job Categories -->
            <section class="section">
                <div class="section-header">
                    <h2>Explore by Category</h2>
                    <p>Find opportunities in your field of expertise</p>
                </div>
                
                <div class="categories-grid">
                    <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                        <a href="jobs.php?category=<?= $category['id'] ?>" class="category-card">
                            <i class="fas fa-<?= 
                                $category['name'] === 'Technology' ? 'laptop-code' : 
                                ($category['name'] === 'Healthcare' ? 'heartbeat' : 
                                ($category['name'] === 'Finance' ? 'chart-line' : 
                                ($category['name'] === 'Education' ? 'graduation-cap' : 
                                ($category['name'] === 'Marketing' ? 'bullhorn' : 
                                ($category['name'] === 'Sales' ? 'handshake' : 'briefcase'))))) ?>"></i>
                            <h4><?= htmlspecialchars($category['name']) ?></h4>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <a href="jobs.php" class="btn btn-outline">
                        <i class="fas fa-th-large"></i> View All Categories
                    </a>
                </div>
            </section>

            <!-- Featured Jobs -->
            <section class="section">
                <div class="section-header">
                    <h2>Recommended for You</h2>
                    <p>Jobs that match your profile and interests</p>
                </div>
                
                <?php if (empty($featured_jobs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>All Caught Up!</h3>
                        <p>You've applied to all available jobs. Check back later for new opportunities!</p>
                        <a href="jobs.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-refresh"></i> Browse All Jobs
                        </a>
                    </div>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($featured_jobs as $job): ?>
                            <div class="job-card">
                                <div class="job-header">
                                    <div class="job-title">
                                        <h3><?= htmlspecialchars($job['title']) ?></h3>
                                        <div class="job-company">
                                            <i class="fas fa-building"></i>
                                            <?= htmlspecialchars($job['company_name'] ?? 'Company') ?>
                                        </div>
                                        <div class="job-meta">
                                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                            <span><i class="fas fa-briefcase"></i> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></span>
                                            <span><i class="fas fa-clock"></i> <?= date('M j', strtotime($job['posted_date'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="job-description">
                                    <?= nl2br(htmlspecialchars(substr($job['description'], 0, 120))) ?>...
                                </div>
                                
                                <div class="job-footer">
                                    <div class="job-salary">
                                        <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                            $<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?>
                                        <?php else: ?>
                                            Competitive Salary
                                        <?php endif; ?>
                                    </div>
                                    <div class="job-actions">
                                        <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-eye"></i> Details
                                        </a>
                                        <button onclick="applyToJob(<?= $job['id'] ?>)" class="btn btn-primary btn-sm">
                                            <i class="fas fa-paper-plane"></i> Apply Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 40px;">
                        <a href="jobs.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> View All Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recent Applications -->
            <?php if (!empty($recent_applications)): ?>
                <section class="section">
                    <div class="section-header">
                        <h2>Recent Applications</h2>
                        <p>Track the status of your latest job applications</p>
                    </div>
                    
                    <div class="applications-list">
                        <?php foreach ($recent_applications as $app): ?>
                            <div class="application-item">
                                <div class="application-info">
                                    <h4><?= htmlspecialchars($app['job_title']) ?></h4>
                                    <p>
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($app['company_name'] ?? 'Company') ?> • 
                                        <i class="fas fa-calendar"></i> Applied <?= date('M j, Y', strtotime($app['applied_date'])) ?>
                                    </p>
                                    <?php if ($app['notes'] && $app['status'] !== 'pending'): ?>
                                        <p style="margin-top: 8px; font-style: italic; color: #667eea;">
                                            <i class="fas fa-comment"></i> "<?= htmlspecialchars($app['notes']) ?>"
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <span class="status-badge status-<?= $app['status'] ?>">
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="dashboard.php" class="btn btn-outline">
                            <i class="fas fa-list"></i> View All Applications
                        </a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>

    <script>
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
                        location.reload();
                    } else {
                        alert(result.message);
                    }
                })
                .catch(error => {
                    alert('Network error. Please try again.');
                });
            }
        }

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

            // Observe all job cards and category cards
            document.querySelectorAll('.job-card, .category-card, .application-item').forEach(el => {
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

    <!-- job seeker home page Footer -->
    <footer style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 60px 0 30px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <!-- Company Info -->
                <div>
                    <h3 style="color: #4facfe; margin-bottom: 20px; font-size: 1.5rem;">
                        <i class="fas fa-briefcase"></i> JobPortal
                    </h3>
                    <p style="color: #cbd5e0; line-height: 1.6; margin-bottom: 20px;">
                        Your gateway to amazing career opportunities. Connect with top employers and find your dream job today.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="color: #4facfe; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: #4facfe; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: #4facfe; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" style="color: #4facfe; font-size: 1.5rem; transition: color 0.3s ease;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="dashboard.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                               
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="profile.php" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">
         
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Job Categories -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Popular Categories</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php?category=1" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Technology</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php?category=2" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Marketing</a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php?category=3" style="color: #cbd5e0; text-decoration: none; transition: color 0.3s ease;">Sales</a>
                        </li>
                        <li style="margin-bottom: 10px;">

                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Contact Us</h4>
                    <div style="color: #cbd5e0; line-height: 1.8;">
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-envelope" style="color: #4facfe; margin-right: 10px;"></i>
                            contact my email
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-phone" style="color: #4facfe; margin-right: 10px;"></i>
                            +2519943123-4567
                        </p>
                        <p style="margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt" style="color: #4facfe; margin-right: 10px;"></i>
                            Ethiopia, addise abeba<br>
                            
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid #4a5568; padding-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="color: #cbd5e0;">
                    <p>&copy; 2025 JobPortal. All rights reserved.</p>
                </div>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Privacy Policy</a>
                    <a href="#" style="color: #cbd5e0; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        footer a:hover {
            color: #4facfe !important;
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