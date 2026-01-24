<?php
/**
 * USER PROFILE PAGE
 * 
 * Description: User profile management for all user types
 * Features:
 * - Personal information editing
 * - Profile picture upload
 * - Resume upload (job seekers)
 * - Company logo upload (employers)
 * - Skills and experience management
 * - Contact information updates
 * - Account settings and preferences
 * 
 * User Types: All authenticated users
 * Access: Restricted to logged-in users, content varies by user type
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
    
    // Get profile data based on user type
    if ($user_type === 'job_seeker') {
        $stmt = $pdo->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    } elseif ($user_type === 'employer') {
        $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update user basic info
        $stmt = $pdo->prepare("
            UPDATE users 
            SET first_name = ?, last_name = ?, phone = ?, location = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['phone'],
            $_POST['location'],
            $user_id
        ]);
        
        // Update profile based on user type
        if ($user_type === 'job_seeker') {
            if ($profile) {
                $stmt = $pdo->prepare("
                    UPDATE job_seeker_profiles 
                    SET skills = ?, experience_years = ?, education = ?, desired_salary_min = ?, 
                        desired_salary_max = ?, bio = ?, linkedin_url = ?, portfolio_url = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $_POST['skills'],
                    $_POST['experience_years'],
                    $_POST['education'],
                    $_POST['desired_salary_min'] ?: null,
                    $_POST['desired_salary_max'] ?: null,
                    $_POST['bio'],
                    $_POST['linkedin_url'],
                    $_POST['portfolio_url'],
                    $user_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO job_seeker_profiles 
                    (user_id, skills, experience_years, education, desired_salary_min, desired_salary_max, 
                     bio, linkedin_url, portfolio_url, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $_POST['skills'],
                    $_POST['experience_years'],
                    $_POST['education'],
                    $_POST['desired_salary_min'] ?: null,
                    $_POST['desired_salary_max'] ?: null,
                    $_POST['bio'],
                    $_POST['linkedin_url'],
                    $_POST['portfolio_url']
                ]);
            }
        } elseif ($user_type === 'employer') {
            if ($profile) {
                $stmt = $pdo->prepare("
                    UPDATE employer_profiles 
                    SET company_name = ?, company_description = ?, industry = ?, company_size = ?, 
                        website = ?, founded_year = ?, headquarters = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    $_POST['company_name'],
                    $_POST['company_description'],
                    $_POST['industry'],
                    $_POST['company_size'],
                    $_POST['website'],
                    $_POST['founded_year'] ?: null,
                    $_POST['headquarters'],
                    $user_id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO employer_profiles 
                    (user_id, company_name, company_description, industry, company_size, 
                     website, founded_year, headquarters, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $_POST['company_name'],
                    $_POST['company_description'],
                    $_POST['industry'],
                    $_POST['company_size'],
                    $_POST['website'],
                    $_POST['founded_year'] ?: null,
                    $_POST['headquarters']
                ]);
            }
        }
        
        $success_message = "Profile updated successfully!";
        
        // Refresh data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user_type === 'job_seeker') {
            $stmt = $pdo->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
        } elseif ($user_type === 'employer') {
            $stmt = $pdo->prepare("SELECT * FROM employer_profiles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
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
    <title>My Profile - JobPortal</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .page-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .profile-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 40px;
        }

        .form-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .profile-completion {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .completion-bar {
            background: #e0e0e0;
            height: 10px;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }

        .completion-progress {
            background: #4caf50;
            height: 100%;
            transition: width 0.3s ease;
        }

        .file-upload {
            border: 2px dashed #e9ecef;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            transition: border-color 0.3s ease;
        }

        .file-upload:hover {
            border-color: #3498db;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload-label {
            cursor: pointer;
            color: #3498db;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Form Validation Styles */
        .form-group input.error,
        .form-group select.error,
        .form-group textarea.error {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 2px rgba(231, 76, 60, 0.2) !important;
        }

        .field-error {
            color: #e74c3c;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
            font-weight: 500;
            animation: fadeInError 0.3s ease-in;
        }

        @keyframes fadeInError {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group input.valid,
        .form-group select.valid,
        .form-group textarea.valid {
            border-color: #27ae60 !important;
            box-shadow: 0 0 0 2px rgba(39, 174, 96, 0.2) !important;
        }

        .validation-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
        }

        .form-group {
            position: relative;
        }

        .form-group input.error + .validation-icon {
            color: #e74c3c;
        }

        .form-group input.valid + .validation-icon {
            color: #27ae60;
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
                <a href="profile.php" class="nav-link" style="color: #3498db;">Profile</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!--  Page Header job seeker -->
        <div class="page-header">
            <h1><i class="fas fa-user-edit"></i> My Profile</h1>
            <p>Keep your profile updated to <?= $user_type === 'job_seeker' ? 'attract employers' : 'attract top talent' ?></p>
        </div>

        <!-- Profile Container -->
        <div class="profile-container">
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

            <!-- Profile Completion -->
            <div class="profile-completion">
                <h3><i class="fas fa-chart-line"></i> Profile Completion</h3>
                <?php
                $completion = 0;
                $total_fields = $user_type === 'job_seeker' ? 10 : 8;
                
                // Count completed fields
                if ($user['first_name']) $completion++;
                if ($user['last_name']) $completion++;
                if ($user['phone']) $completion++;
                if ($user['location']) $completion++;
                
                if ($profile) {
                    if ($user_type === 'job_seeker') {
                        if ($profile['skills']) $completion++;
                        if ($profile['education']) $completion++;
                        if ($profile['bio']) $completion++;
                        if ($profile['experience_years']) $completion++;
                        if ($profile['desired_salary_min']) $completion++;
                        if ($profile['linkedin_url']) $completion++;
                    } else {
                        if ($profile['company_name']) $completion++;
                        if ($profile['company_description']) $completion++;
                        if ($profile['industry']) $completion++;
                        if ($profile['website']) $completion++;
                    }
                }
                
                $percentage = round(($completion / $total_fields) * 100);
                ?>
                <p><?= $percentage ?>% Complete - <?= $total_fields - $completion ?> fields remaining</p>
                <div class="completion-bar">
                    <div class="completion-progress" style="width: <?= $percentage ?>%"></div>
                </div>
            </div>

            <form method="POST" id="profileForm">
                <!-- Basic Information about job seeker -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> Basic Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required
                                   value="<?= htmlspecialchars($user['first_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required
                                   value="<?= htmlspecialchars($user['last_name']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" placeholder="09XXXXXXXX or +251XXXXXXXXX"
                                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location"
                                   value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php if ($user_type === 'job_seeker'): ?>
                    <!-- Job Seeker Profile -->
                    <div class="form-section">
                        <h2><i class="fas fa-briefcase"></i> Professional Information</h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="experience_years">Years of Experience</label>
                                <select id="experience_years" name="experience_years">
                                    <option value="0" <?= ($profile['experience_years'] ?? 0) == 0 ? 'selected' : '' ?>>Fresh Graduate</option>
                                    <option value="1" <?= ($profile['experience_years'] ?? 0) == 1 ? 'selected' : '' ?>>1 Year</option>
                                    <option value="2" <?= ($profile['experience_years'] ?? 0) == 2 ? 'selected' : '' ?>>2 Years</option>
                                    <option value="3" <?= ($profile['experience_years'] ?? 0) == 3 ? 'selected' : '' ?>>3 Years</option>
                                    <option value="4" <?= ($profile['experience_years'] ?? 0) == 4 ? 'selected' : '' ?>>4 Years</option>
                                    <option value="5" <?= ($profile['experience_years'] ?? 0) == 5 ? 'selected' : '' ?>>5 Years</option>
                                    <option value="10" <?= ($profile['experience_years'] ?? 0) >= 10 ? 'selected' : '' ?>>10+ Years</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="education">Education</label>
                                <input type="text" id="education" name="education" 
                                       placeholder="e.g., Bachelor of Computer Science"
                                       value="<?= htmlspecialchars($profile['education'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="skills">Skills (comma-separated)</label>
                            <textarea id="skills" name="skills" 
                                      placeholder="e.g., JavaScript, React, Node.js, Python, SQL"><?= htmlspecialchars($profile['skills'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="bio">Professional Bio</label>
                            <textarea id="bio" name="bio" 
                                      placeholder="Tell employers about your experience, achievements, and career goals..."><?= htmlspecialchars($profile['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="desired_salary_min">Desired Salary (Min)</label>
                                <input type="number" id="desired_salary_min" name="desired_salary_min" 
                                       placeholder="50000"
                                       value="<?= htmlspecialchars($profile['desired_salary_min'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="desired_salary_max">Desired Salary (Max)</label>
                                <input type="number" id="desired_salary_max" name="desired_salary_max" 
                                       placeholder="80000"
                                       value="<?= htmlspecialchars($profile['desired_salary_max'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="linkedin_url">LinkedIn Profile</label>
                                <input type="url" id="linkedin_url" name="linkedin_url" 
                                       placeholder="https://linkedin.com/in/yourprofile"
                                       value="<?= htmlspecialchars($profile['linkedin_url'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="portfolio_url">Portfolio Website</label>
                                <input type="url" id="portfolio_url" name="portfolio_url" 
                                       placeholder="https://yourportfolio.com"
                                       value="<?= htmlspecialchars($profile['portfolio_url'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Resume Upload -->
                    <div class="form-section">
                        <h2><i class="fas fa-file-upload"></i> Resume</h2>
                        <div class="file-upload">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #3498db; margin-bottom: 15px;"></i>
                            <p><label for="resume" class="file-upload-label">Click to upload your resume</label></p>
                            <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                            <p style="color: #666; font-size: 0.9rem; margin-top: 10px;">Supported formats: PDF, DOC, DOCX (Max 5MB)</p>
                            <?php if ($profile && $profile['resume_filename']): ?>
                                <p style="color: #27ae60; margin-top: 10px;">
                                    <i class="fas fa-check-circle"></i> Current resume: <?= htmlspecialchars($profile['resume_filename']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php elseif ($user_type === 'employer'): ?>
                    <!-- Employer Profile -->
                    <div class="form-section">
                        <h2><i class="fas fa-building"></i> Company Information</h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="company_name">Company Name</label>
                                <input type="text" id="company_name" name="company_name" required
                                       value="<?= htmlspecialchars($profile['company_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="industry">Industry</label>
                                <input type="text" id="industry" name="industry"
                                       placeholder="e.g., Technology, Healthcare, Finance"
                                       value="<?= htmlspecialchars($profile['industry'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="company_description">Company Description</label>
                            <textarea id="company_description" name="company_description" 
                                      placeholder="Describe your company, mission, and culture..."><?= htmlspecialchars($profile['company_description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="company_size">Company Size</label>
                                <select id="company_size" name="company_size">
                                    <option value="">Select Size</option>
                                    <option value="1-10" <?= ($profile['company_size'] ?? '') == '1-10' ? 'selected' : '' ?>>1-10 employees</option>
                                    <option value="11-50" <?= ($profile['company_size'] ?? '') == '11-50' ? 'selected' : '' ?>>11-50 employees</option>
                                    <option value="51-200" <?= ($profile['company_size'] ?? '') == '51-200' ? 'selected' : '' ?>>51-200 employees</option>
                                    <option value="201-500" <?= ($profile['company_size'] ?? '') == '201-500' ? 'selected' : '' ?>>201-500 employees</option>
                                    <option value="500+" <?= ($profile['company_size'] ?? '') == '500+' ? 'selected' : '' ?>>500+ employees</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="founded_year">Founded Year</label>
                                <input type="number" id="founded_year" name="founded_year" 
                                       min="1800" max="<?= date('Y') ?>"
                                       value="<?= htmlspecialchars($profile['founded_year'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="website">Company Website</label>
                                <input type="url" id="website" name="website" 
                                       placeholder="https://yourcompany.com"
                                       value="<?= htmlspecialchars($profile['website'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label for="headquarters">Headquarters</label>
                                <input type="text" id="headquarters" name="headquarters" 
                                       placeholder="e.g., San Francisco, CA"
                                       value="<?= htmlspecialchars($profile['headquarters'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        /**
         * PROFILE FORM VALIDATION
         * Comprehensive validation for profile edit forms (both job seeker and employer)
         */

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('profileForm');
            const userType = '<?= $user_type ?>';
            
            // Setup validation for all form fields
            setupProfileValidation(userType);
            
            // Handle form submission
            form.addEventListener('submit', function(event) {
                handleProfileFormSubmit(event, userType);
            });
        });

        function setupProfileValidation(userType) {
            // Basic information validation
            setupBasicInfoValidation();
            
            // User type specific validation
            if (userType === 'job_seeker') {
                setupJobSeekerValidation();
            } else if (userType === 'employer') {
                setupEmployerValidation();
            }
        }

        function setupBasicInfoValidation() {
            // Name fields validation
            const firstNameField = document.getElementById('first_name');
            const lastNameField = document.getElementById('last_name');
            
            if (firstNameField) {
                firstNameField.addEventListener('keydown', preventInvalidNameChars);
                firstNameField.addEventListener('input', function() {
                    validateNameInput(this);
                });
                firstNameField.addEventListener('blur', function() {
                    validateNameInput(this);
                });
            }
            
            if (lastNameField) {
                lastNameField.addEventListener('keydown', preventInvalidNameChars);
                lastNameField.addEventListener('input', function() {
                    validateNameInput(this);
                });
                lastNameField.addEventListener('blur', function() {
                    validateNameInput(this);
                });
            }

            // Phone number validation
            const phoneField = document.getElementById('phone');
            if (phoneField) {
                phoneField.addEventListener('keydown', preventInvalidPhoneChars);
                phoneField.addEventListener('input', function() {
                    validatePhoneInput(this);
                });
                phoneField.addEventListener('blur', function() {
                    validatePhoneInput(this);
                });
            }

            // Location validation
            const locationField = document.getElementById('location');
            if (locationField) {
                locationField.addEventListener('input', function() {
                    validateLocation(this);
                });
                locationField.addEventListener('blur', function() {
                    validateLocation(this);
                });
            }
        }

        function setupJobSeekerValidation() {
            // Skills validation
            const skillsField = document.getElementById('skills');
            if (skillsField) {
                skillsField.addEventListener('keydown', preventInvalidSkillsChars);
                skillsField.addEventListener('input', function() {
                    validateSkills(this);
                });
                skillsField.addEventListener('blur', function() {
                    validateSkills(this);
                });
            }

            // Bio validation
            const bioField = document.getElementById('bio');
            if (bioField) {
                bioField.addEventListener('keydown', preventInvalidBioChars);
                bioField.addEventListener('input', function() {
                    validateBio(this);
                });
                bioField.addEventListener('blur', function() {
                    validateBio(this);
                });
            }

            // Education validation
            const educationField = document.getElementById('education');
            if (educationField) {
                educationField.addEventListener('input', function() {
                    validateEducation(this);
                });
                educationField.addEventListener('blur', function() {
                    validateEducation(this);
                });
            }

            // Salary range validation
            const salaryMinField = document.getElementById('desired_salary_min');
            const salaryMaxField = document.getElementById('desired_salary_max');
            
            if (salaryMinField && salaryMaxField) {
                salaryMinField.addEventListener('input', function() {
                    validateSalaryRange('desired_salary_min', 'desired_salary_max');
                });
                salaryMaxField.addEventListener('input', function() {
                    validateSalaryRange('desired_salary_min', 'desired_salary_max');
                });
            }

            // URL validation
            const linkedinField = document.getElementById('linkedin_url');
            const portfolioField = document.getElementById('portfolio_url');
            
            if (linkedinField) {
                linkedinField.addEventListener('input', function() {
                    validateLinkedInURL(this);
                });
                linkedinField.addEventListener('blur', function() {
                    validateLinkedInURL(this);
                });
            }
            
            if (portfolioField) {
                portfolioField.addEventListener('input', function() {
                    validateURL(this, 'Portfolio URL');
                });
                portfolioField.addEventListener('blur', function() {
                    validateURL(this, 'Portfolio URL');
                });
            }
        }

        function setupEmployerValidation() {
            // Company name validation
            const companyNameField = document.getElementById('company_name');
            if (companyNameField) {
                companyNameField.addEventListener('keydown', preventInvalidCompanyNameChars);
                companyNameField.addEventListener('input', function() {
                    validateCompanyName(this);
                });
                companyNameField.addEventListener('blur', function() {
                    validateCompanyName(this);
                });
            }

            // Company description validation
            const companyDescField = document.getElementById('company_description');
            if (companyDescField) {
                companyDescField.addEventListener('keydown', preventInvalidCompanyDescChars);
                companyDescField.addEventListener('input', function() {
                    validateCompanyDescription(this);
                });
                companyDescField.addEventListener('blur', function() {
                    validateCompanyDescription(this);
                });
            }

            // Industry validation
            const industryField = document.getElementById('industry');
            if (industryField) {
                industryField.addEventListener('keydown', preventInvalidIndustryChars);
                industryField.addEventListener('input', function() {
                    validateIndustry(this);
                });
                industryField.addEventListener('blur', function() {
                    validateIndustry(this);
                });
            }

            // Website validation
            const websiteField = document.getElementById('website');
            if (websiteField) {
                websiteField.addEventListener('input', function() {
                    validateCompanyWebsite(this);
                });
                websiteField.addEventListener('blur', function() {
                    validateCompanyWebsite(this);
                });
            }

            // Founded year validation
            const foundedYearField = document.getElementById('founded_year');
            if (foundedYearField) {
                foundedYearField.addEventListener('input', function() {
                    validateFoundedYear(this);
                });
                foundedYearField.addEventListener('blur', function() {
                    validateFoundedYear(this);
                });
            }

            // Headquarters validation
            const headquartersField = document.getElementById('headquarters');
            if (headquartersField) {
                headquartersField.addEventListener('keydown', preventInvalidLocationChars);
                headquartersField.addEventListener('input', function() {
                    validateHeadquarters(this);
                });
                headquartersField.addEventListener('blur', function() {
                    validateHeadquarters(this);
                });
            }
        }

        // Validation functions
        function validateLocation(input) {
            const location = input.value.trim();
            
            if (!location) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            if (location.length < 2) {
                showFieldError(input, 'Location must be at least 2 characters');
                return false;
            }
            
            if (location.length > 100) {
                showFieldError(input, 'Location must be less than 100 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateSkills(input) {
            const skills = input.value.trim();
            
            if (!skills) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Check for numbers in skills
            if (/\d/.test(skills)) {
                showFieldError(input, 'Skills should contain only text, not numbers');
                return false;
            }
            
            // Check for inappropriate special characters (allow only letters, spaces, commas, hyphens, periods)
            if (!/^[a-zA-ZÀ-ÿ\s,\-\.]+$/.test(skills)) {
                showFieldError(input, 'Skills can only contain letters, spaces, commas, hyphens, and periods');
                return false;
            }
            
            if (skills.length < 5) {
                showFieldError(input, 'Please provide more detailed skills information');
                return false;
            }
            
            if (skills.length > 500) {
                showFieldError(input, 'Skills description must be less than 500 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateBio(input) {
            const bio = input.value.trim();
            
            if (!bio) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Check for excessive numbers in bio (allow some numbers but not primarily numeric)
            const numberCount = (bio.match(/\d/g) || []).length;
            const totalChars = bio.length;
            if (numberCount > totalChars * 0.2) { // More than 20% numbers
                showFieldError(input, 'Professional bio should be primarily text, not numbers');
                return false;
            }
            
            // Check for inappropriate special characters (allow letters, spaces, common punctuation)
            if (!/^[a-zA-ZÀ-ÿ\s\.,!?;:()\-'"]+$/.test(bio)) {
                showFieldError(input, 'Bio can only contain letters, spaces, and common punctuation');
                return false;
            }
            
            if (bio.length < 20) {
                showFieldError(input, 'Bio should be at least 20 characters for better profile visibility');
                return false;
            }
            
            if (bio.length > 1000) {
                showFieldError(input, 'Bio must be less than 1000 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateEducation(input) {
            const education = input.value.trim();
            
            if (!education) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            if (education.length < 3) {
                showFieldError(input, 'Education must be at least 3 characters');
                return false;
            }
            
            if (education.length > 200) {
                showFieldError(input, 'Education must be less than 200 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateSalaryRange(minFieldId, maxFieldId) {
            const salaryMin = document.getElementById(minFieldId);
            const salaryMax = document.getElementById(maxFieldId);
            const minValue = parseFloat(salaryMin.value) || 0;
            const maxValue = parseFloat(salaryMax.value) || 0;
            
            // Clear previous errors
            clearFieldError(salaryMin);
            clearFieldError(salaryMax);
            
            // If both are empty, that's okay (optional field)
            if (!salaryMin.value && !salaryMax.value) {
                return true;
            }
            
            // Individual field validation
            if (salaryMin.value && minValue < 0) {
                showFieldError(salaryMin, 'Salary cannot be negative');
                return false;
            }
            
            if (salaryMax.value && maxValue < 0) {
                showFieldError(salaryMax, 'Salary cannot be negative');
                return false;
            }
            
            // Range validation
            if (salaryMin.value && salaryMax.value && minValue >= maxValue) {
                showFieldError(salaryMax, 'Maximum salary must be greater than minimum salary');
                return false;
            }
            
            // Reasonable limits
            if (minValue > 1000000) {
                showFieldError(salaryMin, 'Please enter a reasonable salary amount');
                return false;
            }
            
            if (maxValue > 1000000) {
                showFieldError(salaryMax, 'Please enter a reasonable salary amount');
                return false;
            }
            
            return true;
        }

        function validateLinkedInURL(input) {
            const url = input.value.trim();
            
            if (!url) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Basic URL validation
            if (!validateURL(input, 'LinkedIn URL')) {
                return false;
            }
            
            // LinkedIn specific validation
            if (!url.includes('linkedin.com')) {
                showFieldError(input, 'Please enter a valid LinkedIn URL');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateURL(input, fieldName) {
            const url = input.value.trim();
            
            if (!url) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            try {
                new URL(url);
                clearFieldError(input);
                return true;
            } catch (e) {
                showFieldError(input, `Please enter a valid ${fieldName}`);
                return false;
            }
        }

        function validateCompanyName(input) {
            const companyName = input.value.trim();
            
            if (!companyName) {
                showFieldError(input, 'Company name is required');
                return false;
            }
            
            // Check for excessive numbers (allow some numbers but not primarily numeric)
            const numberCount = (companyName.match(/\d/g) || []).length;
            const totalChars = companyName.length;
            if (numberCount > totalChars * 0.3) { // More than 30% numbers
                showFieldError(input, 'Company name should be primarily text, not numbers');
                return false;
            }
            
            // Check for inappropriate special characters (allow letters, spaces, numbers, common business symbols)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,&\-'()]+$/.test(companyName)) {
                showFieldError(input, 'Company name can only contain letters, numbers, spaces, and common business symbols (.,&-\'())');
                return false;
            }
            
            if (companyName.length < 2) {
                showFieldError(input, 'Company name must be at least 2 characters');
                return false;
            }
            
            if (companyName.length > 100) {
                showFieldError(input, 'Company name must be less than 100 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateCompanyDescription(input) {
            const description = input.value.trim();
            
            if (!description) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Check for excessive numbers in description
            const numberCount = (description.match(/\d/g) || []).length;
            const totalChars = description.length;
            if (numberCount > totalChars * 0.15) { // More than 15% numbers
                showFieldError(input, 'Company description should be primarily text, not numbers');
                return false;
            }
            
            // Check for inappropriate special characters (allow letters, spaces, common punctuation)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"&%$]+$/.test(description)) {
                showFieldError(input, 'Company description can only contain letters, numbers, spaces, and professional punctuation');
                return false;
            }
            
            if (description.length < 20) {
                showFieldError(input, 'Company description should be at least 20 characters for better visibility');
                return false;
            }
            
            if (description.length > 2000) {
                showFieldError(input, 'Company description must be less than 2000 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateIndustry(input) {
            const industry = input.value.trim();
            
            if (!industry) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Check for numbers in industry (industries should be text-based)
            if (/\d/.test(industry)) {
                showFieldError(input, 'Industry should contain only text, not numbers');
                return false;
            }
            
            // Check for inappropriate special characters (allow letters, spaces, hyphens, ampersands)
            if (!/^[a-zA-ZÀ-ÿ\s\-&]+$/.test(industry)) {
                showFieldError(input, 'Industry can only contain letters, spaces, hyphens, and ampersands');
                return false;
            }
            
            if (industry.length < 2) {
                showFieldError(input, 'Industry must be at least 2 characters');
                return false;
            }
            
            if (industry.length > 100) {
                showFieldError(input, 'Industry must be less than 100 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateCompanyWebsite(input) {
            const url = input.value.trim();
            
            if (!url) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Basic URL validation
            try {
                const urlObj = new URL(url);
                
                // Check for valid protocols
                if (!['http:', 'https:'].includes(urlObj.protocol)) {
                    showFieldError(input, 'Website URL must start with http:// or https://');
                    return false;
                }
                
                // Check for valid domain format
                if (!urlObj.hostname || urlObj.hostname.length < 3) {
                    showFieldError(input, 'Please enter a valid website URL');
                    return false;
                }
                
                clearFieldError(input);
                return true;
            } catch (e) {
                showFieldError(input, 'Please enter a valid website URL (e.g., https://yourcompany.com)');
                return false;
            }
        }

        function validateHeadquarters(input) {
            const headquarters = input.value.trim();
            
            if (!headquarters) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            // Check for excessive numbers in location
            const numberCount = (headquarters.match(/\d/g) || []).length;
            const totalChars = headquarters.length;
            if (numberCount > totalChars * 0.3) { // More than 30% numbers
                showFieldError(input, 'Headquarters location should be primarily text');
                return false;
            }
            
            // Check for inappropriate special characters (allow letters, spaces, numbers, common location symbols)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,\-'()]+$/.test(headquarters)) {
                showFieldError(input, 'Headquarters can only contain letters, numbers, spaces, and location symbols (.,\'-())');
                return false;
            }
            
            if (headquarters.length < 2) {
                showFieldError(input, 'Headquarters must be at least 2 characters');
                return false;
            }
            
            if (headquarters.length > 100) {
                showFieldError(input, 'Headquarters must be less than 100 characters');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateFoundedYear(input) {
            const year = parseInt(input.value);
            const currentYear = new Date().getFullYear();
            
            if (!input.value) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            if (year < 1800) {
                showFieldError(input, 'Please enter a valid founded year');
                return false;
            }
            
            if (year > currentYear) {
                showFieldError(input, 'Founded year cannot be in the future');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        /**
         * Prevent Invalid Characters in Skills Field
         * Blocks typing of numbers and inappropriate special characters
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidSkillsChars(event) {
            const char = event.key;
            
            // Allow control keys (backspace, delete, arrow keys, etc.)
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            // Block numbers
            if (/\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Skills should contain only text, not numbers');
                // Clear error after 3 seconds
                setTimeout(() => {
                    if (!/\d/.test(input.value)) {
                        clearFieldError(input);
                    }
                }, 3000);
                return;
            }
            
            // Block inappropriate special characters (allow letters, spaces, commas, hyphens, periods)
            if (!/[a-zA-ZÀ-ÿ\s,\-\.]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Skills can only contain letters, spaces, commas, hyphens, and periods');
                // Clear error after 3 seconds
                setTimeout(() => {
                    clearFieldError(input);
                }, 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Bio Field
         * Blocks typing of excessive numbers and inappropriate special characters
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidBioChars(event) {
            const char = event.key;
            
            // Allow control keys (backspace, delete, arrow keys, etc.)
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            // Check current content for number density
            const currentText = event.target.value;
            const currentNumbers = (currentText.match(/\d/g) || []).length;
            
            // If typing a number, check if it would exceed 20% threshold
            if (/\d/.test(char)) {
                const totalCharsAfter = currentText.length + 1;
                const numbersAfter = currentNumbers + 1;
                
                if (numbersAfter > totalCharsAfter * 0.2 && totalCharsAfter > 10) { // Only enforce after 10 chars
                    event.preventDefault();
                    const input = event.target;
                    showFieldError(input, 'Professional bio should be primarily text, not numbers');
                    // Clear error after 3 seconds
                    setTimeout(() => {
                        clearFieldError(input);
                    }, 3000);
                    return;
                }
            }
            
            // Block inappropriate special characters (allow letters, spaces, common punctuation)
            if (!/[a-zA-ZÀ-ÿ\s\.,!?;:()\-'"]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Bio can only contain letters, spaces, and common punctuation');
                // Clear error after 3 seconds
                setTimeout(() => {
                    clearFieldError(input);
                }, 3000);
                return;
            }
        }

        function handleProfileFormSubmit(event, userType) {
            event.preventDefault();
            
            // Clear any existing form errors
            clearAllFormErrors(event.target);
            
            // Validate all fields
            const errors = [];
            
            // Basic information validation
            const firstNameValid = validateNameInput(document.getElementById('first_name'));
            const lastNameValid = validateNameInput(document.getElementById('last_name'));
            const phoneValid = validatePhoneInput(document.getElementById('phone'));
            const locationValid = validateLocation(document.getElementById('location'));
            
            if (!firstNameValid) errors.push('First name is invalid');
            if (!lastNameValid) errors.push('Last name is invalid');
            if (!phoneValid) errors.push('Phone number is invalid');
            if (!locationValid) errors.push('Location is invalid');
            
            // User type specific validation
            if (userType === 'job_seeker') {
                const skillsValid = validateSkills(document.getElementById('skills'));
                const bioValid = validateBio(document.getElementById('bio'));
                const educationValid = validateEducation(document.getElementById('education'));
                const salaryValid = validateSalaryRange('desired_salary_min', 'desired_salary_max');
                const linkedinValid = validateLinkedInURL(document.getElementById('linkedin_url'));
                const portfolioValid = validateURL(document.getElementById('portfolio_url'), 'Portfolio URL');
                
                if (!skillsValid) errors.push('Skills information is invalid');
                if (!bioValid) errors.push('Bio is invalid');
                if (!educationValid) errors.push('Education is invalid');
                if (!salaryValid) errors.push('Salary range is invalid');
                if (!linkedinValid) errors.push('LinkedIn URL is invalid');
                if (!portfolioValid) errors.push('Portfolio URL is invalid');
                
            } else if (userType === 'employer') {
                const companyNameValid = validateCompanyName(document.getElementById('company_name'));
                const companyDescValid = validateCompanyDescription(document.getElementById('company_description'));
                const industryValid = validateIndustry(document.getElementById('industry'));
                const websiteValid = validateCompanyWebsite(document.getElementById('website'));
                const foundedYearValid = validateFoundedYear(document.getElementById('founded_year'));
                const headquartersValid = validateHeadquarters(document.getElementById('headquarters'));
                
                if (!companyNameValid) errors.push('Company name is invalid');
                if (!companyDescValid) errors.push('Company description is invalid');
                if (!industryValid) errors.push('Industry is invalid');
                if (!websiteValid) errors.push('Company website is invalid');
                if (!foundedYearValid) errors.push('Founded year is invalid');
                if (!headquartersValid) errors.push('Headquarters is invalid');
            }
            
            // If there are validation errors, prevent submission
            if (errors.length > 0) {
                showNotification('Please fix the following errors: ' + errors.join(', '), 'error');
                
                // Focus on first invalid field
                const firstInvalidField = event.target.querySelector('.error');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                
                return false;
            }
            
            // If all validation passes, submit the form
            showNotification('Updating profile...', 'info');
            event.target.submit();
        }

        /**
         * Prevent Invalid Characters in Company Name Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidCompanyNameChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
                return;
            }
            
            // Check current content for number density
            const currentText = event.target.value;
            const currentNumbers = (currentText.match(/\d/g) || []).length;
            
            // If typing a number, check if it would exceed 30% threshold
            if (/\d/.test(char)) {
                const totalCharsAfter = currentText.length + 1;
                const numbersAfter = currentNumbers + 1;
                
                if (numbersAfter > totalCharsAfter * 0.3 && totalCharsAfter > 5) {
                    event.preventDefault();
                    const input = event.target;
                    showFieldError(input, 'Company name should be primarily text, not numbers');
                    setTimeout(() => clearFieldError(input), 3000);
                    return;
                }
            }
            
            // Block inappropriate special characters (allow letters, spaces, numbers, common business symbols)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,&\-'()]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Company name can only contain letters, numbers, spaces, and common business symbols');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Company Description Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidCompanyDescChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            // Check current content for number density
            const currentText = event.target.value;
            const currentNumbers = (currentText.match(/\d/g) || []).length;
            
            // If typing a number, check if it would exceed 15% threshold
            if (/\d/.test(char)) {
                const totalCharsAfter = currentText.length + 1;
                const numbersAfter = currentNumbers + 1;
                
                if (numbersAfter > totalCharsAfter * 0.15 && totalCharsAfter > 10) {
                    event.preventDefault();
                    const input = event.target;
                    showFieldError(input, 'Company description should be primarily text, not numbers');
                    setTimeout(() => clearFieldError(input), 3000);
                    return;
                }
            }
            
            // Block inappropriate special characters (allow professional punctuation)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"&%$]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Company description can only contain letters, numbers, spaces, and professional punctuation');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Industry Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidIndustryChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
                return;
            }
            
            // Block numbers completely in industry
            if (/\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Industry should contain only text, not numbers');
                setTimeout(() => {
                    if (!/\d/.test(input.value)) {
                        clearFieldError(input);
                    }
                }, 3000);
                return;
            }
            
            // Block inappropriate special characters (allow letters, spaces, hyphens, ampersands)
            if (!/[a-zA-ZÀ-ÿ\s\-&]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Industry can only contain letters, spaces, hyphens, and ampersands');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Location Fields (Headquarters)
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidLocationChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
                return;
            }
            
            // Check current content for number density
            const currentText = event.target.value;
            const currentNumbers = (currentText.match(/\d/g) || []).length;
            
            // If typing a number, check if it would exceed 30% threshold
            if (/\d/.test(char)) {
                const totalCharsAfter = currentText.length + 1;
                const numbersAfter = currentNumbers + 1;
                
                if (numbersAfter > totalCharsAfter * 0.3 && totalCharsAfter > 5) {
                    event.preventDefault();
                    const input = event.target;
                    showFieldError(input, 'Location should be primarily text');
                    setTimeout(() => clearFieldError(input), 3000);
                    return;
                }
            }
            
            // Block inappropriate special characters (allow location symbols)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,\-'()]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Location can only contain letters, numbers, spaces, and location symbols');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        // Utility functions (reuse from main.js)
        function showFieldError(field, message) {
            clearFieldError(field);
            
            field.classList.add('error');
            field.classList.remove('valid');
            
            const errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            errorElement.textContent = message;
            errorElement.setAttribute('role', 'alert');
            errorElement.setAttribute('aria-live', 'polite');
            
            field.parentNode.insertBefore(errorElement, field.nextSibling);
            
            addValidationIcon(field, 'error');
        }

        function clearFieldError(field) {
            field.classList.remove('error');
            
            const errorElement = field.parentNode.querySelector('.field-error');
            if (errorElement) {
                errorElement.remove();
            }
            
            removeValidationIcon(field);
            
            if (field.value.trim()) {
                field.classList.add('valid');
                addValidationIcon(field, 'valid');
            }
        }

        function addValidationIcon(field, type) {
            removeValidationIcon(field);
            
            const icon = document.createElement('i');
            icon.className = `validation-icon fas ${type === 'valid' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
            
            field.parentNode.appendChild(icon);
        }

        function removeValidationIcon(field) {
            const existingIcon = field.parentNode.querySelector('.validation-icon');
            if (existingIcon) {
                existingIcon.remove();
            }
        }

        function clearAllFormErrors(form) {
            const errorFields = form.querySelectorAll('.error');
            errorFields.forEach(field => {
                clearFieldError(field);
            });
            
            const errorMessages = form.querySelectorAll('.field-error');
            errorMessages.forEach(error => {
                error.remove();
            });
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existing = document.querySelectorAll('.notification');
            existing.forEach(n => n.remove());
            
            // Create new notification
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 6px;
                color: white;
                font-weight: 500;
                z-index: 10000;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            `;
            
            // Set background color based on type
            switch(type) {
                case 'success':
                    notification.style.backgroundColor = '#27ae60';
                    break;
                case 'error':
                    notification.style.backgroundColor = '#e74c3c';
                    break;
                case 'warning':
                    notification.style.backgroundColor = '#f39c12';
                    break;
                default:
                    notification.style.backgroundColor = '#3498db';
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    </script>
</body>
</html>