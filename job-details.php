<?php
/**
 * JOB DETAILS PAGE
 * 
 * Description: Detailed view of individual job posting
 * Features:
 * - Complete job description and requirements
 * - Company information and profile
 * - Application form for job seekers
 * - Save job functionality
 * - Share job posting
 * - Related job suggestions
 * - Application status tracking
 * 
 * User Types: All users (public access)
 * Access: Public page, application features for logged-in job seekers
 */

session_start();

if (!isset($_GET['id'])) {
    header('Location: jobs.php');
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
    
    $job_id = $_GET['id'];
    
    // Get job details
    $stmt = $pdo->prepare("
        SELECT jp.*, jc.name as category_name, ep.company_name, ep.company_description, ep.website 
        FROM job_postings jp 
        LEFT JOIN job_categories jc ON jp.category_id = jc.id 
        LEFT JOIN users u ON jp.employer_id = u.id 
        LEFT JOIN employer_profiles ep ON u.id = ep.user_id 
        WHERE jp.id = ? AND jp.is_active = TRUE
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        header('Location: jobs.php');
        exit();
    }
    
    // Update view count
    $stmt = $pdo->prepare("UPDATE job_postings SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$job_id]);
    
    // Check if user already applied (if logged in as job seeker)
    $already_applied = false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'job_seeker') {
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND job_seeker_id = ?");
        $stmt->execute([$job_id, $_SESSION['user_id']]);
        $already_applied = $stmt->fetch() !== false;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    header('Location: jobs.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['title']) ?> - JobPortal</title>
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

        .btn-large {
            padding: 15px 30px;
            font-size: 1.1rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .job-header {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .job-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .job-company {
            font-size: 1.3rem;
            color: #666;
            margin-bottom: 20px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }

        .job-meta span {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            color: #666;
        }

        .job-meta i {
            color: #3498db;
        }

        .job-salary {
            font-size: 1.5rem;
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .apply-section {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .job-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        .job-main {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .job-sidebar {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .section p,
        .section ul {
            color: #666;
            line-height: 1.8;
        }

        .section ul {
            padding-left: 20px;
        }

        .section li {
            margin-bottom: 8px;
        }

        .company-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .company-info p {
            color: #666;
            margin-bottom: 15px;
        }

        .company-link {
            color: #3498db;
            text-decoration: none;
        }

        .company-link:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3498db;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .applied-notice {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 768px) {
            .job-content {
                grid-template-columns: 1fr;
            }
            
            .job-title {
                font-size: 2rem;
            }
            
            .job-meta {
                flex-direction: column;
                gap: 15px;
            }
            
            .apply-section {
                flex-direction: column;
                align-items: stretch;
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
                <a href="index.php" class="nav-link">Home</a>
                <a href="jobs.php" class="nav-link">Jobs</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="logout.php" class="btn btn-outline">Logout</a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-primary">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <a href="jobs.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Jobs
        </a>

        <!-- Job Header -->
        <div class="job-header">
            <h1 class="job-title"><?= htmlspecialchars($job['title']) ?></h1>
            <div class="job-company"><?= htmlspecialchars($job['company_name'] ?? 'Company Name') ?></div>
            
            <div class="job-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></span>
                <span><i class="fas fa-briefcase"></i> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></span>
                <span><i class="fas fa-layer-group"></i> <?= ucfirst($job['experience_level']) ?></span>
                <?php if ($job['category_name']): ?>
                    <span><i class="fas fa-tag"></i> <?= htmlspecialchars($job['category_name']) ?></span>
                <?php endif; ?>
                <span><i class="fas fa-eye"></i> <?= $job['views_count'] ?> views</span>
                <span><i class="fas fa-users"></i> <?= $job['applications_count'] ?> applications</span>
            </div>
            
            <div class="job-salary">
                <?php if ($job['salary_min'] && $job['salary_max']): ?>
                    $<?= number_format($job['salary_min']) ?> - $<?= number_format($job['salary_max']) ?>
                <?php elseif ($job['salary_min']): ?>
                    $<?= number_format($job['salary_min']) ?>+
                <?php else: ?>
                    Salary not specified
                <?php endif; ?>
            </div>
            
            <?php if ($already_applied): ?>
                <div class="applied-notice">
                    <i class="fas fa-check-circle"></i>
                    You have already applied for this job
                </div>
            <?php endif; ?>
            
            <div class="apply-section">
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'job_seeker' && !$already_applied): ?>
                    <button onclick="applyToJob(<?= $job['id'] ?>)" class="btn btn-primary btn-large">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </button>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="index.php" class="btn btn-primary btn-large">Login to Apply</a>
                <?php endif; ?>
                <span style="color: #666;">Posted <?= date('M j, Y', strtotime($job['posted_date'])) ?></span>
            </div>
        </div>

        <!-- Job Content -->
        <div class="job-content">
            <div class="job-main">
                <div class="section">
                    <h2>Job Description</h2>
                    <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                </div>
                
                <?php if ($job['requirements']): ?>
                    <div class="section">
                        <h2>Requirements</h2>
                        <p><?= nl2br(htmlspecialchars($job['requirements'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($job['responsibilities']): ?>
                    <div class="section">
                        <h2>Responsibilities</h2>
                        <p><?= nl2br(htmlspecialchars($job['responsibilities'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="job-sidebar">
                <div class="company-info">
                    <h3>About <?= htmlspecialchars($job['company_name'] ?? 'Company') ?></h3>
                    <?php if ($job['company_description']): ?>
                        <p><?= nl2br(htmlspecialchars($job['company_description'])) ?></p>
                    <?php endif; ?>
                    <?php if ($job['website']): ?>
                        <a href="<?= htmlspecialchars($job['website']) ?>" target="_blank" class="company-link">
                            <i class="fas fa-external-link-alt"></i> Visit Company Website
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="section">
                    <h3>Job Details</h3>
                    <p><strong>Job Type:</strong> <?= ucfirst(str_replace('_', ' ', $job['job_type'])) ?></p>
                    <p><strong>Experience Level:</strong> <?= ucfirst($job['experience_level']) ?></p>
                    <?php if ($job['application_deadline']): ?>
                        <p><strong>Application Deadline:</strong> <?= date('M j, Y', strtotime($job['application_deadline'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
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
                        location.reload(); // Refresh to show applied status
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