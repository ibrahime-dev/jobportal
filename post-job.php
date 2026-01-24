<?php
/**
 * JOB POSTING FORM
 * 
 * Description: Form for employers to create and edit job postings
 * Features:
 * - Job posting creation form
 * - Job editing and updating
 * - Rich text editor for job descriptions
 * - Category and skill selection
 * - Salary range and benefits setup
 * - Job posting preview
 * - Draft saving functionality
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
    
    // Get job categories
    $stmt = $pdo->prepare("SELECT * FROM job_categories WHERE is_active = TRUE ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $required_fields = ['title', 'description', 'location', 'category_id', 'job_type', 'experience_level', 'application_deadline'];
        $missing_fields = [];
        $validation_errors = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        // Additional deadline validation
        if (!empty($_POST['application_deadline'])) {
            $deadline = $_POST['application_deadline'];
            $deadline_date = new DateTime($deadline);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            
            // Year validation - must be current year or next year only
            $current_year = (int)$today->format('Y');
            $deadline_year = (int)$deadline_date->format('Y');
            
            if ($deadline_year < $current_year) {
                $validation_errors[] = "Application deadline year cannot be in the past";
            }
            
            if ($deadline_year > $current_year + 1) {
                $validation_errors[] = "Application deadline year cannot be more than 1 year from now";
            }
            
            // Check if deadline is in the past
            if ($deadline_date < $today) {
                $validation_errors[] = "Application deadline cannot be in the past";
            }
            
            // Check if deadline is today
            if ($deadline_date->format('Y-m-d') === $today->format('Y-m-d')) {
                $validation_errors[] = "Application deadline cannot be today. Please allow time for applications";
            }
            
            // Check minimum notice (3 days)
            $three_days_from_now = clone $today;
            $three_days_from_now->add(new DateInterval('P3D'));
            
            if ($deadline_date < $three_days_from_now) {
                $validation_errors[] = "Application deadline must be at least 3 days from now";
            }
            
            // Check maximum period (6 months)
            $six_months_from_now = clone $today;
            $six_months_from_now->add(new DateInterval('P6M'));
            
            if ($deadline_date > $six_months_from_now) {
                $validation_errors[] = "Application deadline cannot be more than 6 months from now";
            }
            
            // Job type specific validation
            if (!empty($_POST['job_type'])) {
                $job_type = $_POST['job_type'];
                $days_until_deadline = $deadline_date->diff($today)->days;
                
                $min_recommended_days = [
                    'internship' => 7,
                    'part_time' => 10,
                    'contract' => 14,
                    'full_time' => 14,
                    'remote' => 21
                ];
                
                $min_days = $min_recommended_days[$job_type] ?? 14;
                if ($days_until_deadline < $min_days) {
                    $validation_errors[] = "For " . str_replace('_', ' ', $job_type) . " positions, consider at least {$min_days} days for better candidate pool";
                }
            }
            
            // Experience level validation
            if (!empty($_POST['experience_level'])) {
                $experience_level = $_POST['experience_level'];
                $days_until_deadline = $deadline_date->diff($today)->days;
                
                if (($experience_level === 'senior' || $experience_level === 'executive') && $days_until_deadline < 21) {
                    $validation_errors[] = ucfirst($experience_level) . " level positions typically need at least 3 weeks for quality applications";
                }
            }
        }
        
        if (empty($missing_fields) && empty($validation_errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO job_postings (
                    employer_id, category_id, title, description, requirements, responsibilities,
                    location, salary_min, salary_max, job_type, experience_level, 
                    application_deadline, posted_date, is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), TRUE)
            ");
            
            $deadline = $_POST['application_deadline'];
            $salary_min = !empty($_POST['salary_min']) ? $_POST['salary_min'] : null;
            $salary_max = !empty($_POST['salary_max']) ? $_POST['salary_max'] : null;
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['category_id'],
                $_POST['title'],
                $_POST['description'],
                $_POST['requirements'],
                $_POST['responsibilities'],
                $_POST['location'],
                $salary_min,
                $salary_max,
                $_POST['job_type'],
                $_POST['experience_level'],
                $deadline
            ]);
            
            $success_message = "Job posted successfully!";
        } else {
            if (!empty($missing_fields)) {
                $error_message = "Please fill in all required fields: " . implode(', ', $missing_fields);
            } elseif (!empty($validation_errors)) {
                $error_message = "Validation errors: " . implode('; ', $validation_errors);
            }
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
    <title>Post a Job - JobPortal</title>
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
            max-width: 800px;
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

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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

        .form-group label.required::after {
            content: " *";
            color: #e74c3c;
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
            min-height: 120px;
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

        .form-help {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }

        .salary-group {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: end;
        }

        .salary-separator {
            padding: 12px 0;
            text-align: center;
            font-weight: 500;
            color: #666;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .salary-group {
                grid-template-columns: 1fr;
            }
            
            .salary-separator {
                display: none;
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
                <a href="post-job.php" class="nav-link" style="color: #3498db;">Post Job</a>
                <a href="logout.php" class="btn btn-outline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Post a New Job</h1>
            <p>Attract the best talent by creating a detailed job posting</p>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                    <a href="dashboard.php" style="margin-left: 15px;">View in Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="postJobForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="title" class="required">Job Title</label>
                        <input type="text" id="title" name="title" required 
                               placeholder="e.g. Senior Software Developer"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="required">Category</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" 
                                        <?= ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="job_type" class="required">Job Type</label>
                        <select id="job_type" name="job_type" required>
                            <option value="">Select Job Type</option>
                            <option value="full_time" <?= ($_POST['job_type'] ?? '') == 'full_time' ? 'selected' : '' ?>>Full Time</option>
                            <option value="part_time" <?= ($_POST['job_type'] ?? '') == 'part_time' ? 'selected' : '' ?>>Part Time</option>
                            <option value="contract" <?= ($_POST['job_type'] ?? '') == 'contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="internship" <?= ($_POST['job_type'] ?? '') == 'internship' ? 'selected' : '' ?>>Internship</option>
                            <option value="remote" <?= ($_POST['job_type'] ?? '') == 'remote' ? 'selected' : '' ?>>Remote</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="experience_level" class="required">Experience Level</label>
                        <select id="experience_level" name="experience_level" required>
                            <option value="">Select Experience Level</option>
                            <option value="entry" <?= ($_POST['experience_level'] ?? '') == 'entry' ? 'selected' : '' ?>>Entry Level</option>
                            <option value="mid" <?= ($_POST['experience_level'] ?? '') == 'mid' ? 'selected' : '' ?>>Mid Level</option>
                            <option value="senior" <?= ($_POST['experience_level'] ?? '') == 'senior' ? 'selected' : '' ?>>Senior Level</option>
                            <option value="executive" <?= ($_POST['experience_level'] ?? '') == 'executive' ? 'selected' : '' ?>>Executive</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="location" class="required">Location</label>
                        <input type="text" id="location" name="location" required 
                               placeholder="e.g. New York, NY or Remote"
                               value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Salary Range (Optional)</label>
                    <div class="salary-group">
                        <input type="number" id="salary_min" name="salary_min" placeholder="Minimum salary" 
                               value="<?= htmlspecialchars($_POST['salary_min'] ?? '') ?>">
                        <div class="salary-separator">to</div>
                        <input type="number" id="salary_max" name="salary_max" placeholder="Maximum salary"
                               value="<?= htmlspecialchars($_POST['salary_max'] ?? '') ?>">
                    </div>
                    <div class="form-help">Leave blank if you prefer not to disclose salary information</div>
                </div>

                <div class="form-group">
                    <label for="application_deadline" class="required">Application Deadline</label>
                    <input type="date" id="application_deadline" name="application_deadline" required
                           min="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                           max="<?= date('Y-m-d', strtotime('+1 year')) ?>"
                           value="<?= htmlspecialchars($_POST['application_deadline'] ?? '') ?>">
                    <div class="form-help">Set a deadline for job applications (minimum 3 days, maximum 1 year from now)</div>
                </div>

                <div class="form-group">
                    <label for="description" class="required">Job Description</label>
                    <textarea id="description" name="description" required 
                              placeholder="Provide a detailed description of the job role, company culture, and what makes this opportunity exciting..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="requirements">Requirements</label>
                    <textarea id="requirements" name="requirements" 
                              placeholder="List the required skills, qualifications, and experience..."><?= htmlspecialchars($_POST['requirements'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="responsibilities">Responsibilities</label>
                    <textarea id="responsibilities" name="responsibilities" 
                              placeholder="Describe the key responsibilities and duties for this role..."><?= htmlspecialchars($_POST['responsibilities'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                    <i class="fas fa-plus-circle"></i> Post Job
                </button>
            </form>
        </div>
    </div>

    <script>
        /**
         * POST JOB FORM VALIDATION
         * Comprehensive validation for job posting form
         */

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('postJobForm');
            
            // Add event listeners for real-time validation
            setupJobFormValidation();
            
            // Handle form submission
            form.addEventListener('submit', handleJobFormSubmit);
        });

        function setupJobFormValidation() {
            // Job title validation
            const titleField = document.getElementById('title');
            if (titleField) {
                titleField.addEventListener('keydown', preventInvalidJobTitleChars);
                titleField.addEventListener('input', function() {
                    validateJobTitle(this);
                });
                titleField.addEventListener('blur', function() {
                    validateJobTitle(this);
                });
            }

            // Location validation
            const locationField = document.getElementById('location');
            if (locationField) {
                locationField.addEventListener('keydown', preventInvalidLocationChars);
                locationField.addEventListener('input', function() {
                    validateLocation(this);
                });
                locationField.addEventListener('blur', function() {
                    validateLocation(this);
                });
            }

            // Salary validation
            const salaryMinField = document.getElementById('salary_min');
            const salaryMaxField = document.getElementById('salary_max');
            
            if (salaryMinField && salaryMaxField) {
                salaryMinField.addEventListener('input', function() {
                    validateSalaryRange();
                });
                salaryMaxField.addEventListener('input', function() {
                    validateSalaryRange();
                });
            }

            // Description validation
            const descriptionField = document.getElementById('description');
            if (descriptionField) {
                descriptionField.addEventListener('keydown', preventInvalidDescriptionChars);
                descriptionField.addEventListener('input', function() {
                    validateDescription(this);
                });
                descriptionField.addEventListener('blur', function() {
                    validateDescription(this);
                });
            }

            // Application deadline validation
            const deadlineField = document.getElementById('application_deadline');
            if (deadlineField) {
                deadlineField.addEventListener('change', function() {
                    validateDeadline(this);
                });
            }

            // Requirements validation
            const requirementsField = document.getElementById('requirements');
            if (requirementsField) {
                requirementsField.addEventListener('keydown', preventInvalidRequirementsChars);
                requirementsField.addEventListener('input', function() {
                    validateRequirements(this);
                });
                requirementsField.addEventListener('blur', function() {
                    validateRequirements(this);
                });
            }

            // Responsibilities validation
            const responsibilitiesField = document.getElementById('responsibilities');
            if (responsibilitiesField) {
                responsibilitiesField.addEventListener('keydown', preventInvalidResponsibilitiesChars);
                responsibilitiesField.addEventListener('input', function() {
                    validateResponsibilities(this);
                });
                responsibilitiesField.addEventListener('blur', function() {
                    validateResponsibilities(this);
                });
            }
        }

        function validateJobTitle(input) {
            const title = input.value.trim();
            
            if (!title) {
                showFieldError(input, 'Job title is required');
                return false;
            }
            
            if (title.length < 3) {
                showFieldError(input, 'Job title must be at least 3 characters');
                return false;
            }
            
            if (title.length > 100) {
                showFieldError(input, 'Job title must be less than 100 characters');
                return false;
            }
            
            // Check for excessive numbers (allow some numbers for positions like "Software Engineer II")
            const numberCount = (title.match(/\d/g) || []).length;
            const totalChars = title.length;
            if (numberCount > totalChars * 0.2) { // More than 20% numbers
                showFieldError(input, 'Job title should be primarily text, not numbers');
                return false;
            }
            
            // Allow professional job title characters (letters, spaces, numbers, common symbols)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,\-'()\/&]+$/.test(title)) {
                showFieldError(input, 'Job title can only contain letters, numbers, spaces, and professional symbols (.,\'-()\/&)');
                return false;
            }
            
            // Check for inappropriate content (basic check)
            const inappropriateWords = ['scam', 'fake', 'easy money', 'work from home guaranteed', 'get rich quick'];
            const lowerTitle = title.toLowerCase();
            for (let word of inappropriateWords) {
                if (lowerTitle.includes(word)) {
                    showFieldError(input, 'Please use professional language in job title');
                    return false;
                }
            }
            
            clearFieldError(input);
            return true;
        }

        function validateLocation(input) {
            const location = input.value.trim();
            
            if (!location) {
                showFieldError(input, 'Location is required');
                return false;
            }
            
            if (location.length < 2) {
                showFieldError(input, 'Location must be at least 2 characters');
                return false;
            }
            
            if (location.length > 100) {
                showFieldError(input, 'Location must be less than 100 characters');
                return false;
            }
            
            // Allow location-appropriate characters (letters, spaces, numbers, location symbols)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,\-'()\/]+$/.test(location)) {
                showFieldError(input, 'Location can only contain letters, numbers, spaces, and location symbols (.,\'-()\/)')
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateSalaryRange() {
            const salaryMin = document.getElementById('salary_min');
            const salaryMax = document.getElementById('salary_max');
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
            if (salaryMin.value) {
                if (minValue < 0) {
                    showFieldError(salaryMin, 'Salary cannot be negative');
                    return false;
                }
                
                if (minValue < 1000) {
                    showFieldError(salaryMin, 'Please enter a realistic minimum salary (at least 1,000)');
                    return false;
                }
                
                if (minValue > 10000000) {
                    showFieldError(salaryMin, 'Please enter a reasonable salary amount');
                    return false;
                }
            }
            
            if (salaryMax.value) {
                if (maxValue < 0) {
                    showFieldError(salaryMax, 'Salary cannot be negative');
                    return false;
                }
                
                if (maxValue < 1000) {
                    showFieldError(salaryMax, 'Please enter a realistic maximum salary (at least 1,000)');
                    return false;
                }
                
                if (maxValue > 10000000) {
                    showFieldError(salaryMax, 'Please enter a reasonable salary amount');
                    return false;
                }
            }
            
            // Range validation
            if (salaryMin.value && salaryMax.value && minValue >= maxValue) {
                showFieldError(salaryMax, 'Maximum salary must be greater than minimum salary');
                return false;
            }
            
            // Check for reasonable salary gap
            if (salaryMin.value && salaryMax.value && (maxValue - minValue) < (minValue * 0.1)) {
                showFieldError(salaryMax, 'Salary range should have a meaningful difference');
                return false;
            }
            
            return true;
        }

        function validateDescription(input) {
            const description = input.value.trim();
            
            if (!description) {
                showFieldError(input, 'Job description is required');
                return false;
            }
            
            if (description.length < 100) {
                showFieldError(input, 'Job description must be at least 100 characters for a comprehensive posting');
                return false;
            }
            
            if (description.length > 5000) {
                showFieldError(input, 'Job description must be less than 5000 characters');
                return false;
            }
            
            // Check if content starts with text (not numbers)
            if (/^\d/.test(description)) {
                showFieldError(input, 'Job description must start with text, not numbers');
                return false;
            }
            
            // Check if first word is text-based
            const firstWord = description.split(/\s+/)[0];
            if (firstWord && /^\d+$/.test(firstWord)) {
                showFieldError(input, 'Job description must begin with descriptive text');
                return false;
            }
            
            // Allow professional description characters
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%$\n\r]+$/.test(description)) {
                showFieldError(input, 'Job description contains invalid characters. Please use professional language only');
                return false;
            }
            
            // Check for inappropriate content
            const inappropriateWords = ['scam', 'fake', 'easy money', 'get rich quick', 'no experience needed', 'work from home guaranteed'];
            const lowerDescription = description.toLowerCase();
            for (let word of inappropriateWords) {
                if (lowerDescription.includes(word)) {
                    showFieldError(input, 'Please use professional language in job description');
                    return false;
                }
            }
            
            clearFieldError(input);
            return true;
        }

        function validateDeadline(input) {
            const deadline = input.value;
            
            if (!deadline) {
                showFieldError(input, 'Application deadline is required');
                return false;
            }
            
            const deadlineDate = new Date(deadline);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to start of day
            
            // Check if date is valid
            if (isNaN(deadlineDate.getTime())) {
                showFieldError(input, 'Please enter a valid date');
                return false;
            }
            
            // Year validation - must be current year or next year only
            const currentYear = today.getFullYear();
            const deadlineYear = deadlineDate.getFullYear();
            
            if (deadlineYear < currentYear) {
                showFieldError(input, 'Application deadline year cannot be in the past');
                return false;
            }
            
            if (deadlineYear > currentYear + 1) {
                showFieldError(input, 'Application deadline year cannot be more than 1 year from now');
                return false;
            }
            
            // Check if deadline is in the past
            if (deadlineDate < today) {
                showFieldError(input, 'Application deadline cannot be in the past');
                return false;
            }
            
            // Check if deadline is today (not allowed)
            if (deadlineDate.getTime() === today.getTime()) {
                showFieldError(input, 'Application deadline cannot be today. Please allow time for applications');
                return false;
            }
            
            // Check minimum notice period (at least 3 days from now)
            const threeDaysFromNow = new Date();
            threeDaysFromNow.setDate(threeDaysFromNow.getDate() + 3);
            
            if (deadlineDate < threeDaysFromNow) {
                showFieldError(input, 'Application deadline must be at least 3 days from now to give candidates time to apply');
                return false;
            }
            
            // Check maximum period (not more than 6 months)
            const sixMonthsFromNow = new Date();
            sixMonthsFromNow.setMonth(sixMonthsFromNow.getMonth() + 6);
            
            if (deadlineDate > sixMonthsFromNow) {
                showFieldError(input, 'Application deadline cannot be more than 6 months from now');
                return false;
            }
            
            // Check if deadline falls on weekend (optional warning)
            const dayOfWeek = deadlineDate.getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) { // Sunday = 0, Saturday = 6
                showFieldError(input, 'Consider setting deadline on a weekday for better candidate response');
                // Don't return false - this is just a warning
            }
            
            // Check for reasonable deadline periods based on job type
            const jobTypeField = document.getElementById('job_type');
            if (jobTypeField && jobTypeField.value) {
                const jobType = jobTypeField.value;
                const daysUntilDeadline = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
                
                // Minimum recommended periods by job type
                const minRecommendedDays = {
                    'internship': 7,      // 1 week minimum
                    'part_time': 10,      // 10 days minimum
                    'contract': 14,       // 2 weeks minimum
                    'full_time': 14,      // 2 weeks minimum
                    'remote': 21          // 3 weeks minimum (wider candidate pool)
                };
                
                const minDays = minRecommendedDays[jobType] || 14;
                if (daysUntilDeadline < minDays) {
                    showFieldError(input, `For ${jobType.replace('_', ' ')} positions, consider at least ${minDays} days for better candidate pool`);
                    // Don't return false - this is a recommendation
                }
            }
            
            // Check for experience level considerations
            const experienceField = document.getElementById('experience_level');
            if (experienceField && experienceField.value) {
                const experienceLevel = experienceField.value;
                const daysUntilDeadline = Math.ceil((deadlineDate - today) / (1000 * 60 * 60 * 24));
                
                // Senior and executive positions need more time
                if ((experienceLevel === 'senior' || experienceLevel === 'executive') && daysUntilDeadline < 21) {
                    showFieldError(input, `${experienceLevel} level positions typically need at least 3 weeks for quality applications`);
                    // Don't return false - this is a recommendation
                }
            }
            
            clearFieldError(input);
            return true;
        }

        function validateRequirements(input) {
            const requirements = input.value.trim();
            
            if (!requirements) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            if (requirements.length < 20) {
                showFieldError(input, 'Requirements should be at least 20 characters for better clarity');
                return false;
            }
            
            if (requirements.length > 2000) {
                showFieldError(input, 'Requirements must be less than 2000 characters');
                return false;
            }
            
            // Check if content starts with text (not numbers)
            if (/^\d/.test(requirements)) {
                showFieldError(input, 'Requirements must start with text, not numbers');
                return false;
            }
            
            // Check if first word is text-based
            const firstWord = requirements.split(/\s+/)[0];
            if (firstWord && /^\d+$/.test(firstWord)) {
                showFieldError(input, 'Requirements must begin with descriptive text');
                return false;
            }
            
            // Allow professional requirements characters (letters, spaces, numbers, professional punctuation)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%+\n\r]+$/.test(requirements)) {
                showFieldError(input, 'Requirements can only contain letters, numbers, spaces, and professional punctuation');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function validateResponsibilities(input) {
            const responsibilities = input.value.trim();
            
            if (!responsibilities) {
                clearFieldError(input);
                return true; // Optional field
            }
            
            if (responsibilities.length < 20) {
                showFieldError(input, 'Responsibilities should be at least 20 characters for better clarity');
                return false;
            }
            
            if (responsibilities.length > 2000) {
                showFieldError(input, 'Responsibilities must be less than 2000 characters');
                return false;
            }
            
            // Check if content starts with text (not numbers)
            if (/^\d/.test(responsibilities)) {
                showFieldError(input, 'Responsibilities must start with text, not numbers');
                return false;
            }
            
            // Check if first word is text-based
            const firstWord = responsibilities.split(/\s+/)[0];
            if (firstWord && /^\d+$/.test(firstWord)) {
                showFieldError(input, 'Responsibilities must begin with descriptive text');
                return false;
            }
            
            // Allow professional responsibilities characters (letters, spaces, numbers, professional punctuation)
            if (!/^[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%+\n\r]+$/.test(responsibilities)) {
                showFieldError(input, 'Responsibilities can only contain letters, numbers, spaces, and professional punctuation');
                return false;
            }
            
            clearFieldError(input);
            return true;
        }

        function handleJobFormSubmit(event) {
            event.preventDefault();
            
            // Clear any existing form errors
            clearAllFormErrors(event.target);
            
            // Validate all fields
            const titleValid = validateJobTitle(document.getElementById('title'));
            const locationValid = validateLocation(document.getElementById('location'));
            const salaryValid = validateSalaryRange();
            const descriptionValid = validateDescription(document.getElementById('description'));
            const deadlineValid = validateDeadline(document.getElementById('application_deadline'));
            const requirementsValid = validateRequirements(document.getElementById('requirements'));
            const responsibilitiesValid = validateResponsibilities(document.getElementById('responsibilities'));
            
            // Check required dropdowns
            const categoryValid = validateRequiredSelect(document.getElementById('category_id'), 'Please select a job category');
            const jobTypeValid = validateRequiredSelect(document.getElementById('job_type'), 'Please select a job type');
            const experienceValid = validateRequiredSelect(document.getElementById('experience_level'), 'Please select an experience level');
            
            // Collect all validation errors
            const errors = [];
            
            if (!titleValid) errors.push('Job title is invalid');
            if (!categoryValid) errors.push('Job category is required');
            if (!jobTypeValid) errors.push('Job type is required');
            if (!experienceValid) errors.push('Experience level is required');
            if (!locationValid) errors.push('Location is invalid');
            if (!salaryValid) errors.push('Salary range is invalid');
            if (!descriptionValid) errors.push('Job description is invalid');
            if (!deadlineValid) errors.push('Application deadline is invalid');
            if (!requirementsValid) errors.push('Requirements are invalid');
            if (!responsibilitiesValid) errors.push('Responsibilities are invalid');
            
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
            showNotification('Posting job...', 'info');
            event.target.submit();
        }

        function validateRequiredSelect(select, message) {
            if (!select.value) {
                showFieldError(select, message);
                return false;
            }
            
            clearFieldError(select);
            return true;
        }

        // Utility functions for error handling
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
        }

        function clearFieldError(field) {
            field.classList.remove('error');
            
            const errorElement = field.parentNode.querySelector('.field-error');
            if (errorElement) {
                errorElement.remove();
            }
            
            if (field.value.trim()) {
                field.classList.add('valid');
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

        /**
         * Prevent Invalid Characters in Job Title Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidJobTitleChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
                return;
            }
            
            // Check current content for number density
            const currentText = event.target.value;
            const currentNumbers = (currentText.match(/\d/g) || []).length;
            
            // If typing a number, check if it would exceed 20% threshold
            if (/\d/.test(char)) {
                const totalCharsAfter = currentText.length + 1;
                const numbersAfter = currentNumbers + 1;
                
                if (numbersAfter > totalCharsAfter * 0.2 && totalCharsAfter > 5) {
                    event.preventDefault();
                    const input = event.target;
                    showFieldError(input, 'Job title should be primarily text, not numbers');
                    setTimeout(() => clearFieldError(input), 3000);
                    return;
                }
            }
            
            // Block inappropriate special characters (allow professional job title symbols)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,\-'()\/&]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Job title can only contain letters, numbers, spaces, and professional symbols');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Location Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidLocationChars(event) {
            const char = event.key;
            
            // Allow control keys
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
                return;
            }
            
            // Block inappropriate special characters (allow location symbols)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,\-'()\/]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Location can only contain letters, numbers, spaces, and location symbols');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Description Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidDescriptionChars(event) {
            const char = event.key;
            
            // Allow control keys and Enter for line breaks
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            const currentText = event.target.value;
            
            // If field is empty or starts with whitespace, prevent numbers as first character
            if ((/^\s*$/.test(currentText) || currentText.length === 0) && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Job description must start with text, not numbers');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // If first non-whitespace character would be a number, prevent it
            const trimmedText = currentText.trim();
            if (trimmedText.length === 0 && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Job description must begin with descriptive text');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // Block inappropriate special characters (allow professional description symbols)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%$]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Job description contains invalid characters. Please use professional language only');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Requirements Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidRequirementsChars(event) {
            const char = event.key;
            
            // Allow control keys and Enter for line breaks
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            const currentText = event.target.value;
            
            // If field is empty or starts with whitespace, prevent numbers as first character
            if ((/^\s*$/.test(currentText) || currentText.length === 0) && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Requirements must start with text, not numbers');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // If first non-whitespace character would be a number, prevent it
            const trimmedText = currentText.trim();
            if (trimmedText.length === 0 && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Requirements must begin with descriptive text');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // Block inappropriate special characters (allow professional punctuation)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%+]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Requirements can only contain letters, numbers, spaces, and professional punctuation');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }

        /**
         * Prevent Invalid Characters in Responsibilities Field
         * @param {KeyboardEvent} event - The keyboard event
         */
        function preventInvalidResponsibilitiesChars(event) {
            const char = event.key;
            
            // Allow control keys and Enter for line breaks
            if (event.ctrlKey || event.metaKey || 
                ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab', 'Enter'].includes(char)) {
                return;
            }
            
            const currentText = event.target.value;
            
            // If field is empty or starts with whitespace, prevent numbers as first character
            if ((/^\s*$/.test(currentText) || currentText.length === 0) && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Responsibilities must start with text, not numbers');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // If first non-whitespace character would be a number, prevent it
            const trimmedText = currentText.trim();
            if (trimmedText.length === 0 && /\d/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Responsibilities must begin with descriptive text');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
            
            // Block inappropriate special characters (allow professional punctuation)
            if (!/[a-zA-ZÀ-ÿ\s\d\.,!?;:()\-'"\/&%+]/.test(char)) {
                event.preventDefault();
                const input = event.target;
                showFieldError(input, 'Responsibilities can only contain letters, numbers, spaces, and professional punctuation');
                setTimeout(() => clearFieldError(input), 3000);
                return;
            }
        }
    </script>
</body>
</html>