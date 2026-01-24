<?php
/**
 * JOB LISTINGS PAGE in job seeker homepage
 * 
 * Description: Public job listings page with search and filter functionality
 * Features:
 * - Display all available job postings
 * - Search jobs by title, company, location
 * - Filter by category, salary range, job type
 * - Pagination for large job lists
 * - Job preview with basic details
 * - Links to detailed job view
 * 
 * User Types: All users (public access)
 * Access: Public page, enhanced features for logged-in users
 */

session_start();

// Database configuration
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get job listings with company info
    $sql = "SELECT jp.*, jc.name as category_name, ep.company_name, ep.logo_filename 
            FROM job_postings jp 
            LEFT JOIN job_categories jc ON jp.category_id = jc.id 
            LEFT JOIN users u ON jp.employer_id = u.id 
            LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
            WHERE jp.is_active = TRUE 
            ORDER BY jp.posted_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $jobs = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
} catch (Exception $e) {
    $jobs = [];
    $categories = [];
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings - JobPortal</title>
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

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .search-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .jobs-grid {
            display: grid;
            gap: 20px;
        }

        .job-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .job-title {
            flex: 1;
        }

        .job-title h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 5px;
        }

        .job-company {
            color: #666;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .job-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .job-salary {
            font-weight: 600;
            color: #27ae60;
            font-size: 1.1rem;
        }

        .job-posted {
            font-size: 0.85rem;
            color: #999;
        }

        .job-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .no-jobs {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-jobs i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #bdc3c7;
        }

        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .job-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .job-footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_type'] === 'job_seeker'): ?>
                        <a href="home.php" class="nav-link">Home</a>
                    <?php elseif ($_SESSION['user_type'] === 'employer'): ?>
                        <a href="employer-home.php" class="nav-link">Home</a>
                    <?php else: ?>
                        <a href="index.php" class="nav-link">Home</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="index.php" class="nav-link">Home</a>
                <?php endif; ?>
                <a href="jobs.php" class="nav-link" style="color: #3498db;">Jobs</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="profile.php" class="nav-link">Profile</a>
                    <?php if ($_SESSION['user_type'] === 'employer'): ?>
                        <a href="post-job.php" class="btn btn-primary">Post Job</a>
                    <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin.php" class="btn btn-primary">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Find Your Dream Job</h1>
            <p>Discover amazing opportunities from top companies</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" method="GET">
                <div class="form-group">
                    <label for="keyword">Job Title or Keywords</label>
                    <input type="text" id="keyword" name="keyword" placeholder="e.g. Software Developer" 
                           value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" placeholder="e.g. Ethiopia" 
                           value="<?= htmlspecialchars($_GET['location'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($_GET['category'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <!-- Jobs Grid -->
        <div class="jobs-grid">
            <?php if (empty($jobs)): ?>
                <div class="no-jobs">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Found</h3>
                    <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div class="job-title">
                                <h3><?= htmlspecialchars($job['title']) ?></h3>
                                <div class="job-company">
                                    <?= htmlspecialchars($job['company_name'] ?? 'Company Name') ?>
                                </div>
                                <div class="job-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                                    <span><i class="fas fa-briefcase"></i> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></span>
                                    <span><i class="fas fa-layer-group"></i> <?= ucfirst($job['experience_level']) ?></span>
                                    <?php if ($job['category_name']): ?>
                                        <span><i class="fas fa-tag"></i> <?= htmlspecialchars($job['category_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-description">
                            <?= nl2br(htmlspecialchars(substr($job['description'], 0, 200))) ?>
                            <?= strlen($job['description']) > 200 ? '...' : '' ?>
                        </div>
                        
                        <div class="job-footer">
                            <div class="job-salary">
                                <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                    $<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?>
                                <?php elseif ($job['salary_min']): ?>
                                    $<?= number_format($job['salary_min']) ?>+
                                <?php else: ?>
                                    Salary not specified
                                <?php endif; ?>
                            </div>
                            <div class="job-posted">
                                Posted <?= date('M j, Y', strtotime($job['posted_date'])) ?>
                            </div>
                        </div>
                        
                        <div class="job-actions">
                            <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline btn-sm">
                                View Details
                            </a>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'job_seeker'): ?>
                                <button onclick="applyToJob(<?= $job['id'] ?>)" class="btn btn-primary btn-sm">
                                    Apply Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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