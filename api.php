<?php
/**
 * MAIN API ENDPOINT
 * 
 * Description: Central API handler for all backend operations
 * Features:
 * - User authentication (login, register, logout)
 * - Password reset functionality
 * - User profile management
 * - Job posting CRUD operations
 * - Application management
 * - File upload handling
 * - JSON response formatting
 * 
 * User Types: All (handles authentication)
 * Access: Public endpoint with internal authentication checks
 */

// Database configuration
$host = 'localhost';
$dbname = 'job_portal';
$username = 'root';
$password = '';

// Start session
session_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Test connection
    $pdo->query('SELECT 1');
    
    $action = $input['action'];
    
    switch ($action) {
        case 'register':
            handleRegister($pdo, $input);
            break;
            
        case 'login':
            handleLogin($pdo, $input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'forgot_password':
            handleForgotPassword($pdo, $input);
            break;
            
        case 'reset_password':
            handleResetPassword($pdo, $input);
            break;
            
        case 'apply_job':
            handleJobApplication($pdo, $input);
            break;
            
        case 'upload_resume':
            handleResumeUpload($pdo);
            break;
            

            
        default:
            throw new Exception('Unknown action');
    }
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please try again later.'
    ]);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    
    // Don't expose internal errors to users, but log them
    $user_message = $e->getMessage();
    
    // If it's a validation error (user-friendly), show it
    if (strpos($user_message, 'Invalid') === 0 || 
        strpos($user_message, 'Missing') === 0 || 
        strpos($user_message, 'Email') === 0 ||
        strpos($user_message, 'Password') === 0 ||
        strpos($user_message, 'This account') === 0 ||
        strpos($user_message, 'First name') === 0 ||
        strpos($user_message, 'Last name') === 0 ||
        strpos($user_message, 'Phone number') === 0 ||
        strpos($user_message, 'cannot contain') !== false ||
        strpos($user_message, 'can only contain') !== false ||
        strpos($user_message, 'Please login') === 0 ||
        strpos($user_message, 'Only job seekers') === 0 ||
        strpos($user_message, 'Job ID is required') === 0 ||
        strpos($user_message, 'Job not found') === 0 ||
        strpos($user_message, 'Job already submitted') === 0 ||
        strpos($user_message, 'Application submitted') === 0) {
        // These are user-friendly validation messages
        echo json_encode([
            'success' => false,
            'message' => $user_message
        ]);
    } else {
        // Generic error for security - but make it more helpful
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Please check your input and try again. Make sure names contain only letters, spaces, hyphens, and apostrophes, and phone numbers contain only numbers.'
        ]);
    }
}

function handleRegister($pdo, $input) {
    // Validate required fields
    $required = ['first_name', 'last_name', 'email', 'password', 'user_type'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate name fields
    $first_name_validation = validateName($input['first_name'], 'First name');
    if (!$first_name_validation['valid']) {
        throw new Exception($first_name_validation['message']);
    }
    
    $last_name_validation = validateName($input['last_name'], 'Last name');
    if (!$last_name_validation['valid']) {
        throw new Exception($last_name_validation['message']);
    }
    
    // Validate phone number if provided
    if (!empty($input['phone'])) {
        $phone_validation = validatePhone($input['phone']);
        if (!$phone_validation['valid']) {
            throw new Exception($phone_validation['message']);
        }
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Validate password
    if (strlen($input['password']) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    // Validate user type
    if (!in_array($input['user_type'], ['job_seeker', 'employer'])) {
        throw new Exception('Invalid user type');
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        throw new Exception('Email already registered');
    }
    
    // Normalize names (trim whitespace, normalize spaces)
    $first_name = normalizeName($input['first_name']);
    $last_name = normalizeName($input['last_name']);
    
    // Normalize phone number if provided
    $phone = !empty($input['phone']) ? normalizePhone($input['phone']) : null;
    
    // Hash password
    $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password_hash, user_type, first_name, last_name, phone, location, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $input['email'],
        $password_hash,
        $input['user_type'],
        $first_name,
        $last_name,
        $phone,
        $input['location'] ?? null
    ]);
    
    $user_id = $pdo->lastInsertId();
    
    // Create profile based on user type
    if ($input['user_type'] === 'job_seeker') {
        $stmt = $pdo->prepare("INSERT INTO job_seeker_profiles (user_id, created_at) VALUES (?, NOW())");
        $stmt->execute([$user_id]);
    } elseif ($input['user_type'] === 'employer') {
        $stmt = $pdo->prepare("INSERT INTO employer_profiles (user_id, created_at) VALUES (?, NOW())");
        $stmt->execute([$user_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully',
        'user_id' => $user_id
    ]);
}

function handleLogin($pdo, $input) {
    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('Email and password are required');
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Get user from database
    $stmt = $pdo->prepare("
        SELECT id, email, password_hash, user_type, first_name, last_name, phone, location, is_active 
        FROM users WHERE email = ?
    ");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Invalid email or password');
    }
    
    if (!$user['is_active']) {
        throw new Exception('Account is deactivated');
    }
    
    // Verify password
    if (!password_verify($input['password'], $user['password_hash'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Validate user type if provided
    if (!empty($input['user_type']) && $user['user_type'] !== $input['user_type']) {
        // Provide specific error message based on actual vs selected user type
        $actual_type = ucfirst(str_replace('_', ' ', $user['user_type']));
        $selected_type = ucfirst(str_replace('_', ' ', $input['user_type']));
        throw new Exception("This account is registered as {$actual_type}, but you selected {$selected_type}. Please select the correct account type.");
    }
    
    // Create session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['login_time'] = time();
    
    // Prepare user data for response
    $user_data = [
        'id' => $user['id'],
        'email' => $user['email'],
        'user_type' => $user['user_type'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'phone' => $user['phone'],
        'location' => $user['location']
    ];
    
    // Determine redirect URL based on user type
    $redirect_url = 'dashboard.php'; // default
    if ($user['user_type'] === 'job_seeker') {
        $redirect_url = 'home.php';
    } elseif ($user['user_type'] === 'employer') {
        $redirect_url = 'employer-home.php';
    } elseif ($user['user_type'] === 'admin') {
        $redirect_url = 'admin-home.php';
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user_data,
        'redirect' => $redirect_url
    ]);
}

function handleLogout() {
    session_destroy();
    
    // Cookie clearing removed - using localStorage only
    
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully'
    ]);
}

function handleJobApplication($pdo, $input) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to apply for jobs');
    }
    
    // Check if user is job seeker
    if ($_SESSION['user_type'] !== 'job_seeker') {
        throw new Exception('Only job seekers can apply for jobs');
    }
    
    if (empty($input['job_id'])) {
        throw new Exception('Job ID is required');
    }
    
    $job_id = $input['job_id'];
    $job_seeker_id = $_SESSION['user_id'];
    
    // Check if job exists and is active
    $stmt = $pdo->prepare("SELECT id, title FROM job_postings WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        throw new Exception('Job not found or no longer available');
    }
    
    // Check if already applied
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND job_seeker_id = ?");
    $stmt->execute([$job_id, $job_seeker_id]);
    if ($stmt->fetch()) {
        throw new Exception('Job already submitted! You have already applied for this position.');
    }
    
    // Create application
    $stmt = $pdo->prepare("
        INSERT INTO applications (job_id, job_seeker_id, status, applied_date) 
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmt->execute([$job_id, $job_seeker_id]);
    
    // Update application count
    $stmt = $pdo->prepare("
        UPDATE job_postings 
        SET applications_count = (SELECT COUNT(*) FROM applications WHERE job_id = ?) 
        WHERE id = ?
    ");
    $stmt->execute([$job_id, $job_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!'
    ]);
}

function handleResumeUpload($pdo) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login to upload resume');
    }
    
    // Check if user is job seeker
    if ($_SESSION['user_type'] !== 'job_seeker') {
        throw new Exception('Only job seekers can upload resumes');
    }
    
    if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }
    
    $file = $_FILES['resume'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Validate file type
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Invalid file type. Please upload PDF, DOC, or DOCX files only.');
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/resumes/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'resume_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE job_seeker_profiles 
        SET resume_filename = ?, resume_original_name = ?, updated_at = NOW() 
        WHERE user_id = ?
    ");
    $stmt->execute([$filename, $file['name'], $_SESSION['user_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Resume uploaded successfully!',
        'filename' => $filename
    ]);
}

function handleForgotPassword($pdo, $input) {
    $email = trim($input['email']);
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
        return;
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing tokens for this user
    $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    
    // Insert new token
    $stmt = $pdo->prepare("
        INSERT INTO password_reset_tokens (user_id, token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $token, $expires_at]);
    
    // In a real application, you would send an email here
    // For demo purposes, we'll just return success
    // You can integrate with services like SendGrid, Mailgun, or PHPMailer
    
    // Generate reset link
    $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
    
    // In a real application, you would send an email here
    // For demo purposes, we'll log the link and show it in the response
    error_log("Password reset link for {$email}: {$reset_link}");
    
    // Here you would typically send an email using PHPMailer, SendGrid, etc.
    // sendPasswordResetEmail($email, $user['first_name'], $reset_link);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset link sent to your email!',
        'demo_link' => $reset_link // Remove this in production - only for demo
    ]);
}

function handleResetPassword($pdo, $input) {
    $token = trim($input['token']);
    $password = trim($input['password']);
    
    if (empty($token) || empty($password)) {
        throw new Exception('Token and password are required');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // Find valid token
    $stmt = $pdo->prepare("
        SELECT user_id FROM password_reset_tokens 
        WHERE token = ? AND expires_at > NOW() AND used = 0
    ");
    $stmt->execute([$token]);
    $reset_token = $stmt->fetch();
    
    if (!$reset_token) {
        throw new Exception('Invalid or expired reset token');
    }
    
    // Update user password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$password_hash, $reset_token['user_id']]);
    
    // Mark token as used
    $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
    $stmt->execute([$token]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully!'
    ]);
}

// Email simulation function (replace with real email service in production)
function sendPasswordResetEmail($email, $name, $reset_link) {
    // In production, use PHPMailer, SendGrid, Mailgun, or similar service
    // This is just a placeholder for demonstration
    
    $subject = "Password Reset - JobPortal";
    $message = "
    <html>
    <head>
        <title>Password Reset</title>
    </head>
    <body>
        <h2>Password Reset Request</h2>
        <p>Hello {$name},</p>
        <p>You requested a password reset for your JobPortal account.</p>
        <p>Click the link below to reset your password:</p>
        <p><a href='{$reset_link}' style='background: #2a5298; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request this reset, please ignore this email.</p>
        <p>Best regards,<br>JobPortal Team</p>
    </body>
    </html>
    ";
    
    // Headers for HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@jobportal.com" . "\r\n";
    
    // Send email (this may not work on all servers without proper SMTP configuration)
    // mail($email, $subject, $message, $headers);
    
    // Log email for demo purposes
    error_log("Email would be sent to: {$email}");
    error_log("Subject: {$subject}");
    error_log("Reset link: {$reset_link}");
}

/**
 * Validate Name Field
 * Server-side validation for first name and last name fields
 * @param string $name - The name to validate
 * @param string $field_name - Field name for error messages
 * @return array - Validation result with 'valid' and 'message' keys
 */
function validateName($name, $field_name = 'Name') {
    // Trim whitespace
    $trimmed_name = trim($name);
    
    // Check if empty
    if (empty($trimmed_name)) {
        return [
            'valid' => false,
            'message' => $field_name . ' is required'
        ];
    }
    
    // Check for numbers (0-9)
    if (preg_match('/\d/', $trimmed_name)) {
        return [
            'valid' => false,
            'message' => 'Please check your ' . strtolower($field_name) . ' spelling. Names cannot contain numbers (0-9).'
        ];
    }
    
    // Check for invalid special characters (allow only letters, spaces, hyphens, apostrophes)
    // Using Unicode-aware regex for international characters
    if (!preg_match('/^[\p{L}\s\-\']+$/u', $trimmed_name)) {
        return [
            'valid' => false,
            'message' => 'Please check your ' . strtolower($field_name) . ' spelling. Names can only contain letters, spaces, hyphens (-), and apostrophes (\').'
        ];
    }
    
    // Check length (reasonable limits)
    if (mb_strlen($trimmed_name) > 50) {
        return [
            'valid' => false,
            'message' => $field_name . ' must be less than 50 characters'
        ];
    }
    
    // Check minimum length
    if (mb_strlen($trimmed_name) < 1) {
        return [
            'valid' => false,
            'message' => $field_name . ' must contain at least one character'
        ];
    }
    
    return [
        'valid' => true,
        'message' => ''
    ];
}

/**
 * Normalize Name Input
 * Cleans and normalizes name input for consistent storage
 * @param string $name - The name to normalize
 * @return string - Normalized name
 */
function normalizeName($name) {
    // Trim whitespace
    $normalized = trim($name);
    
    // Replace multiple spaces with single space
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    
    // Capitalize first letter of each word (proper case)
    $normalized = mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    
    return $normalized;
}

/**
 * Validate Phone Number Field (Ethiopian Standard)
 * Server-side validation for Ethiopian phone number format
 * @param string $phone - The phone number to validate
 * @return array - Validation result with 'valid' and 'message' keys
 */
function validatePhone($phone) {
    // Trim whitespace
    $trimmed_phone = trim($phone);
    
    // Allow empty phone numbers (not required field)
    if (empty($trimmed_phone)) {
        return [
            'valid' => true,
            'message' => ''
        ];
    }
    
    // Check for letters (A-Z, a-z)
    if (preg_match('/[a-zA-Z]/', $trimmed_phone)) {
        return [
            'valid' => false,
            'message' => 'Phone numbers can only contain numbers'
        ];
    }
    
    // Check for invalid special characters (allow only numbers, spaces, hyphens, plus signs)
    if (!preg_match('/^[\d\s\-\+]+$/', $trimmed_phone)) {
        return [
            'valid' => false,
            'message' => 'Phone numbers can only contain numbers, spaces, hyphens, and plus signs'
        ];
    }
    
    // Extract only digits to check format
    $digits_only = preg_replace('/\D/', '', $trimmed_phone);
    
    // Ethiopian phone number validation
    if (strlen($digits_only) > 0) {
        // Check for international format (+251XXXXXXXXX)
        if (strpos($trimmed_phone, '+251') === 0) {
            $phone_without_country_code = substr($digits_only, 3); // Remove 251
            if (strlen($phone_without_country_code) !== 9) {
                return [
                    'valid' => false,
                    'message' => 'Ethiopian international format should be +251XXXXXXXXX (12 digits total)'
                ];
            }
            // Check if it's a valid Ethiopian mobile or landline
            if (!preg_match('/^(9|11)/', $phone_without_country_code)) {
                return [
                    'valid' => false,
                    'message' => 'Ethiopian phone numbers should start with 09 (mobile) or 011 (landline)'
                ];
            }
        }
        // Check for local mobile format (09XXXXXXXX)
        else if (strpos($digits_only, '09') === 0) {
            if (strlen($digits_only) !== 10) {
                return [
                    'valid' => false,
                    'message' => 'Ethiopian mobile numbers should be 10 digits (09XXXXXXXX)'
                ];
            }
        }
        // Check for local landline format (011XXXXXXX)
        else if (strpos($digits_only, '011') === 0) {
            if (strlen($digits_only) !== 10) {
                return [
                    'valid' => false,
                    'message' => 'Ethiopian landline numbers should be 10 digits (011XXXXXXX)'
                ];
            }
        }
        // Invalid format
        else {
            return [
                'valid' => false,
                'message' => 'Please enter a valid Ethiopian phone number: 09XXXXXXXX (mobile), 011XXXXXXX (landline), or +251XXXXXXXXX (international)'
            ];
        }
    }
    
    return [
        'valid' => true,
        'message' => ''
    ];
}

/**
 * Normalize Phone Number Input
 * Cleans and normalizes phone number input for consistent storage
 * @param string $phone - The phone number to normalize
 * @return string - Normalized phone number
 */
function normalizePhone($phone) {
    // Trim whitespace
    $normalized = trim($phone);
    
    // Replace multiple spaces with single space
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    
    return $normalized;
}


?>