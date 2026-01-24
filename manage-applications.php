<?php
/**
 * APPLICATION MANAGEMENT SYSTEM
 * 
 * Description: Employer tool for managing job applications
 * Features:
 * - View all applications for employer's jobs
 * - Filter applications by job, status, date
 * - Review candidate profiles and resumes
 * - Update application status (pending, reviewed, accepted, rejected)
 * - Send messages to applicants
 * - Schedule interviews
 * - Application analytics and reporting
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
    
    $employer_id = $_SESSION['user_id'];
    $job_id = $_GET['job_id'] ?? null;
    $job = null; // Initialize job variable
    
    // Handle application status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'update_status') {
            $application_id = $_POST['application_id'];
            $new_status = $_POST['status'];
            $notes = $_POST['notes'] ?? '';
            
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = ?, notes = ?, status_updated_at = NOW() 
                WHERE id = ? AND job_id IN (SELECT id FROM job_postings WHERE employer_id = ?)
            ");
            $stmt->execute([$new_status, $notes, $application_id, $employer_id]);
            
            $success_message = "Application status updated successfully!";
        }
    }
    
    // Get job details
    if ($job_id) {
        $stmt = $pdo->prepare("
            SELECT jp.*, jc.name as category_name 
            FROM job_postings jp 
            LEFT JOIN job_categories jc ON jp.category_id = jc.id 
            WHERE jp.id = ? AND jp.employer_id = ?
        ");
        $stmt->execute([$job_id, $employer_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            header('Location: dashboard.php');
            exit();
        }
        
        // Get applications for this job
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.location,
                   jsp.skills, jsp.experience_years, jsp.education, jsp.bio, 
                   jsp.resume_filename, jsp.linkedin_url, jsp.portfolio_url,
                   jsp.desired_salary_min, jsp.desired_salary_max
            FROM applications a
            JOIN users u ON a.job_seeker_id = u.id
            LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
            WHERE a.job_id = ?
            ORDER BY a.applied_date DESC
        ");
        $stmt->execute([$job_id]);
        $applications = $stmt->fetchAll();
    } else {
        // Get all applications for employer's jobs
        $stmt = $pdo->prepare("
            SELECT a.*, u.first_name, u.last_name, u.email, u.phone, u.location, jp.title as job_title,
                   jsp.skills, jsp.experience_years, jsp.education, jsp.bio, 
                   jsp.resume_filename, jsp.linkedin_url, jsp.portfolio_url,
                   jsp.desired_salary_min, jsp.desired_salary_max
            FROM applications a
            JOIN users u ON a.job_seeker_id = u.id
            JOIN job_postings jp ON a.job_id = jp.id
            LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
            WHERE jp.employer_id = ?
            ORDER BY a.applied_date DESC
        ");
        $stmt->execute([$employer_id]);
        $applications = $stmt->fetchAll();
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
    <title><?= isset($job) ? 'Manage Applications - ' . htmlspecialchars($job['title']) : 'All Applications' ?> - JobPortal</title>
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
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
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
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: #666;
            margin-bottom: 20px;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .stat-card.pending i { color: #f39c12; }
        .stat-card.reviewed i { color: #3498db; }
        .stat-card.accepted i { color: #27ae60; }
        .stat-card.rejected i { color: #e74c3c; }

        .applications-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: #2c3e50;
            margin: 0;
        }

        .application-card {
            border-bottom: 1px solid #e9ecef;
            padding: 25px 30px;
            transition: background-color 0.3s ease;
        }

        .application-card:hover {
            background-color: #f8f9fa;
        }

        .application-card:last-child {
            border-bottom: none;
        }

        .applicant-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .applicant-info h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .applicant-contact {
            color: #666;
            font-size: 0.9rem;
        }

        .applicant-contact i {
            margin-right: 5px;
            width: 15px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #cce5ff; color: #004085; }
        .status-shortlisted { background: #d4edda; color: #155724; }
        .status-interviewed { background: #e2e3e5; color: #383d41; }
        .status-accepted { background: #d1ecf1; color: #0c5460; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .applicant-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }

        .detail-group h4 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .detail-group p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }

        .skill-tag {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .application-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .action-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .action-form select {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .notes-input {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            width: 200px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
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

        @media (max-width: 768px) {
            .applicant-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .applicant-details {
                grid-template-columns: 1fr;
            }
            
            .application-actions {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .action-form {
                width: 100%;
                flex-wrap: wrap;
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="post-job.php" class="nav-link">Post Job</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="dashboard.php">Dashboard</a> > 
                <?= isset($job) ? 'Job Applications' : 'All Applications' ?>
            </div>
            <h1>
                <i class="fas fa-users"></i> 
                <?= isset($job) ? 'Applications for: ' . htmlspecialchars($job['title']) : 'All Job Applications' ?>
            </h1>
            <?php if (isset($job)): ?>
                <p style="color: #666; margin-top: 10px;">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?> | 
                    <i class="fas fa-calendar"></i> Posted <?= date('M j, Y', strtotime($job['posted_date'])) ?>
                </p>
            <?php endif; ?>
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

        <!-- Simple Summary -->
        <?php if (!empty($applications)): ?>
            <?php
            $pending_count = count(array_filter($applications, fn($app) => $app['status'] === 'pending'));
            $processed_count = count($applications) - $pending_count;
            ?>
            <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
                <div style="display: flex; gap: 30px; align-items: center;">
                    <div>
                        <span style="font-size: 1.5rem; font-weight: bold; color: #f39c12;"><?= $pending_count ?></span>
                        <span style="color: #666; margin-left: 5px;">Need Review</span>
                    </div>
                    <div>
                        <span style="font-size: 1.5rem; font-weight: bold; color: #27ae60;"><?= $processed_count ?></span>
                        <span style="color: #666; margin-left: 5px;">Processed</span>
                    </div>
                    <div>
                        <span style="font-size: 1.5rem; font-weight: bold; color: #3498db;"><?= count($applications) ?></span>
                        <span style="color: #666; margin-left: 5px;">Total Applications</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Applications -->
        <div class="applications-section">
            <div class="section-header">
                <h2>Applications (<?= count($applications) ?>)</h2>
                <?php if (isset($job)): ?>
                    <a href="job-details.php?id=<?= $job['id'] ?>" class="btn btn-outline">
                        <i class="fas fa-eye"></i> View Job Details
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($applications)): ?>
                <div style="text-align: center; padding: 60px 20px; color: #666;">
                    <i class="fas fa-inbox" style="font-size: 4rem; margin-bottom: 20px; color: #bdc3c7;"></i>
                    <h3>No Applications Yet</h3>
                    <p>Applications will appear here when job seekers apply to your jobs.</p>
                </div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <div class="application-card">
                        <div class="applicant-header">
                            <div class="applicant-info">
                                <h3><?= htmlspecialchars($application['first_name'] . ' ' . $application['last_name']) ?></h3>
                                <div class="applicant-contact">
                                    <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($application['email']) ?></div>
                                    <?php if (!empty($application['phone'])): ?>
                                        <div><i class="fas fa-phone"></i> <?= htmlspecialchars($application['phone']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($application['location'])): ?>
                                        <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($application['location']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?= $application['status'] ?>">
                                    <?= ucfirst($application['status']) ?>
                                </span>
                                <div style="font-size: 0.8rem; color: #999; margin-top: 5px;">
                                    Applied: <?= date('M j, Y', strtotime($application['applied_date'])) ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!isset($job)): ?>
                            <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                <strong>Job:</strong> <?= htmlspecialchars($application['job_title']) ?>
                            </div>
                        <?php endif; ?>

                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <strong>Experience:</strong> <?= $application['experience_years'] ?? 0 ?> years<br>
                                    <?php if (!empty($application['education'])): ?>
                                        <strong>Education:</strong> <?= htmlspecialchars($application['education']) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong>Salary:</strong> 
                                    <?php if (!empty($application['desired_salary_min']) || !empty($application['desired_salary_max'])): ?>
                                        $<?= number_format($application['desired_salary_min'] ?? 0) ?> - $<?= number_format($application['desired_salary_max'] ?? 0) ?>
                                    <?php else: ?>
                                        Not specified
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($application['skills'])): ?>
                                <div style="margin-top: 10px;">
                                    <strong>Skills:</strong> <?= htmlspecialchars($application['skills']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="application-actions">
                            <?php if ($application['status'] === 'pending'): ?>
                                <form method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                                    
                                    <input type="text" name="notes" placeholder="Add comment for applicant..." 
                                           style="flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;" 
                                           value="<?= htmlspecialchars($application['notes'] ?? '') ?>">
                                    
                                    <button type="submit" name="status" value="accepted" class="btn btn-success">
                                        <i class="fas fa-check"></i> Accept
                                    </button>
                                    
                                    <button type="submit" name="status" value="rejected" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <span class="status-badge status-<?= $application['status'] ?>" style="padding: 8px 16px;">
                                        <?= $application['status'] === 'accepted' ? 'ACCEPTED' : 'REJECTED' ?>
                                    </span>
                                    <?php if ($application['notes']): ?>
                                        <span style="color: #666; font-style: italic;">
                                            Comment: "<?= htmlspecialchars($application['notes']) ?>"
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($application['resume_filename'])): ?>
                                <a href="uploads/resumes/<?= htmlspecialchars($application['resume_filename']) ?>" 
                                   target="_blank" class="btn btn-outline" style="margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> View Resume
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>