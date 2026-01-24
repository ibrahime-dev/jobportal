<?php
/**
 * MAIN LANDING PAGE
 * 
 * Description: Main entry point of the JobPortal system
 * Features:
 * - Hero section with job search and employer registration
 * - User registration modal (Job Seeker/Employer)
 * - Two-step login system with user type selection
 * - Features showcase section
 * - Responsive design with interactive elements
 * 
 * User Types: Public (visitors), Job Seekers, Employers, Admins
 * Access: Public access for registration and login
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPortal - Find Your Dream Job</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%232a5298;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%231e3c72;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23grad)'/%3E%3Ctext x='50' y='65' font-family='Arial,sans-serif' font-size='45' fill='white' text-anchor='middle'%3E💼%3C/text%3E%3C/svg%3E">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">

</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <div class="logo-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="logo-text">
                    <h2 class="main-title">JobPortal</h2>
                    <span class="sub-title">Find Your Future</span>
                </div>
            </div>
            <div class="nav-menu">
                <a href="#home" class="nav-link">Home</a>
                <a href="jobs.php" class="nav-link">Jobs</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <div class="nav-buttons" id="navButtons">
                    <button class="btn btn-outline" onclick="showLogin()">Login</button>
                    <button class="btn btn-primary" onclick="showRegister()">Register</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <!-- Floating Background Elements -->
        <div class="floating-elements">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>
        
        <div class="hero-container">
            <div class="hero-content">
                <h1>Find Your <span class="highlight">Dream Job</span> Today</h1>
                <p>Connect with top employers and discover opportunities that match your skills and develop chances.</p>
                <div class="hero-buttons">
                    <button class="btn btn-primary btn-large" onclick="showRegister('job_seeker')">Find Jobs</button>
                    <button class="btn btn-outline btn-large" onclick="showRegister('employer')">Post Jobs</button>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-logo">
                    <div class="hero-logo-inner">
                        <i class="fas fa-briefcase hero-logo-icon"></i>
                        <div class="hero-logo-text">JobPortal</div>
                        <div class="hero-logo-subtext">Find Your Future</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Why Choose JobPortal?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-search"></i>
                    <h3>Smart Job Search</h3>
                    <p>Advanced filters to find jobs  that match your skills, location, and salary expectations.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-user-tie"></i>
                    <h3>Professional Profiles</h3>
                    <p>Create detailed profiles and upload resumes to showcase your experience to employers.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-handshake"></i>
                    <h3>Direct Applications</h3>
                    <p>Apply directly to jobs and track your application status in real-time.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-building"></i>
                    <h3>Company Profiles</h3>
                    <p>Employers can create detailed company profiles to attract top talent.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('loginModal')">&times;</span>
            <h2>Login to Your Account</h2>
            
            <!-- Step 1: Email and Password -->
            <div id="loginStep1" class="login-step">
                <form id="loginCredentialsForm">
                    <div class="form-group">
                        <label for="loginEmail">Email</label>
                        <input type="email" id="loginEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input type="password" id="loginPassword" name="password" required>
                    </div>
                    <button type="button" class="btn btn-primary btn-full" onclick="proceedToUserTypeSelection()">
                        Next: Choose Account Type
                    </button>
                </form>

            </div>
            
            <!-- Step 2: User Type Selection -->
            <div id="loginStep2" class="login-step" style="display: none;">
                <div class="step-header">
                    <p style="text-align: center; color: #666; margin-bottom: 20px;">
                        <strong>Email:</strong> <span id="displayEmail"></span><br>
                        <small>Choose your account type to continue:</small>
                    </p>
                </div>
                
                <div class="login-type-selector">
                    <button type="button" class="login-type-btn" data-type="job_seeker" onclick="selectLoginTypeAndLogin('job_seeker')">
                        <i class="fas fa-user"></i>
                        <span>Job Seeker</span>
                        <small>Find and apply for jobs</small>
                    </button>
                    <button type="button" class="login-type-btn" data-type="employer" onclick="selectLoginTypeAndLogin('employer')">
                        <i class="fas fa-building"></i>
                        <span>Employer</span>
                        <small>Post jobs and hire talent</small>
                    </button>
                    <button type="button" class="login-type-btn" data-type="admin" onclick="selectLoginTypeAndLogin('admin')">
                        <i class="fas fa-shield-alt"></i>
                        <span>Admin</span>
                        <small>System administration</small>
                    </button>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" class="btn btn-outline" onclick="backToCredentials()">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </button>
                </div>
            </div>
            
            <p class="modal-footer">
                Don't have an account? <a href="#" onclick="switchToRegister()">Register here</a><br>
                <a href="#" onclick="showForgotPassword()">Forgot your password?</a>
            </p>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('registerModal')">&times;</span>
            <h2>Create Your Account</h2>
            
            <!-- User Type Selection with Visual Indicator -->
            <div class="user-type-selector">
                <div class="selection-header">
                    <p>Choose your account type:</p>
                </div>
                <div class="button-container">
                    <button type="button" class="user-type-btn job-seeker-btn selected" data-type="job_seeker" onclick="selectAccountType('job_seeker')">
                        <i class="fas fa-user"></i>
                        <span>Job Seeker</span>
                        <small>Find and apply for jobs</small>
                    </button>
                    <button type="button" class="user-type-btn employer-btn" data-type="employer" onclick="selectAccountType('employer')">
                        <i class="fas fa-building"></i>
                        <span>Employer</span>
                        <small>Post jobs and hire talent</small>
                    </button>
                </div>
                <div class="selection-indicator">
                    <div class="indicator-text">
                        Selected: <strong id="selectedTypeText">Job Seeker</strong>
                    </div>
                </div>
            </div>

            <form id="registerForm">
                <input type="hidden" id="userType" name="user_type" value="job_seeker">
                
                <div class="form-group">
                    <label for="firstName">First Name *</label>
                    <input type="text" id="firstName" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="lastName">Last Name *</label>
                    <input type="text" id="lastName" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label for="registerEmail">Email Address *</label>
                    <input type="email" id="registerEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="09XXXXXXXX or +251XXXXXXXXX">
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location">
                </div>
                
                <div class="form-group">
                    <label for="registerPassword">Password * (at least 6 characters)</label>
                    <input type="password" id="registerPassword" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i> Create My Account
                </button>
            </form>
            <p class="modal-footer">
                Already have an account? <a href="#" onclick="switchToLogin()">Login here</a>
            </p>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('forgotPasswordModal')">&times;</span>
            <h2>Reset Your Password</h2>
            <p style="color: #666; margin-bottom: 20px; text-align: center;">Enter your email address and we'll send you a link to reset your password.</p>
            <form id="forgotPasswordForm">
                <div class="form-group">
                    <label for="forgotEmail">Email Address</label>
                    <input type="email" id="forgotEmail" name="email" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
            </form>
            <p class="modal-footer">
                Remember your password? <a href="#" onclick="switchToLogin()">Login here</a>
            </p>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('resetPasswordModal')">&times;</span>
            <h2>Set New Password</h2>
            <form id="resetPasswordForm">
                <input type="hidden" id="resetToken" name="token">
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirmNewPassword">Confirm New Password</label>
                    <input type="password" id="confirmNewPassword" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Update Password</button>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    
    <!-- Registration Button Persistence Script -->
    <script>
    // Global variable to track selected user type
    let selectedAccountType = 'job_seeker';
    let persistenceInterval = null;
    
    // Main function to select account type and maintain persistence
    function selectAccountType(type) {
        console.log('Selecting account type:', type);
        selectedAccountType = type;
        
        // Update button states immediately
        updateButtonStates();
        
        // Update hidden input
        const hiddenInput = document.getElementById('userType');
        if (hiddenInput) {
            hiddenInput.value = type;
        }
        
        // Update indicator text
        const indicatorText = document.getElementById('selectedTypeText');
        if (indicatorText) {
            indicatorText.textContent = type === 'job_seeker' ? 'Job Seeker' : 'Employer';
        }
        
        // Start persistence mechanism
        startPersistence();
    }
    
    // Function to update button visual states
    function updateButtonStates() {
        // Remove selected class from all buttons
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.classList.remove('selected');
        });
        
        // Add selected class to the chosen button
        const selectedButton = document.querySelector(`[data-type="${selectedAccountType}"]`);
        if (selectedButton) {
            selectedButton.classList.add('selected');
            console.log('Button updated:', selectedAccountType);
        }
    }
    
    // Function to maintain button selection throughout form interaction
    function startPersistence() {
        // Clear any existing interval
        if (persistenceInterval) {
            clearInterval(persistenceInterval);
        }
        
        // Start new persistence interval
        persistenceInterval = setInterval(() => {
            const modal = document.getElementById('registerModal');
            if (modal && modal.style.display !== 'none') {
                // Continuously maintain the selection
                updateButtonStates();
                
                // Ensure hidden input stays correct
                const hiddenInput = document.getElementById('userType');
                if (hiddenInput && hiddenInput.value !== selectedAccountType) {
                    hiddenInput.value = selectedAccountType;
                }
            } else {
                // Stop persistence when modal is closed
                clearInterval(persistenceInterval);
                persistenceInterval = null;
            }
        }, 150); // Check every 150ms
    }
    
    // Enhanced showRegister function to handle initial selection
    const originalShowRegister = window.showRegister;
    window.showRegister = function(userType) {
        // Call original function first
        if (originalShowRegister) {
            originalShowRegister(userType);
        }
        
        // Set up the selection after modal is shown
        setTimeout(() => {
            selectAccountType(userType || 'job_seeker');
        }, 100);
    };
    
    // Make function globally available
    window.selectAccountType = selectAccountType;
    
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Registration persistence system initialized');
        
        // Set default selection
        selectAccountType('job_seeker');
        
        // Add event listeners to form elements to maintain selection during interaction
        document.addEventListener('input', function(e) {
            if (e.target.closest('#registerModal')) {
                // Maintain selection when user interacts with form
                setTimeout(updateButtonStates, 10);
            }
        });
        
        document.addEventListener('focus', function(e) {
            if (e.target.closest('#registerModal')) {
                // Maintain selection when user focuses on form elements
                setTimeout(updateButtonStates, 10);
            }
        }, true);
        
        document.addEventListener('click', function(e) {
            if (e.target.closest('#registerModal') && !e.target.closest('.user-type-btn')) {
                // Maintain selection when user clicks elsewhere in modal
                setTimeout(updateButtonStates, 10);
            }
        });
    });
    </script>

    <!-- Footer -->
    <footer style="background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 60px 0 30px; margin-top: 80px;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <!-- Company Info -->
                <div>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
                        <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);">
                            <i class="fas fa-briefcase" style="color: white; font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <h3 style="color: #3498db; margin: 0; font-size: 1.5rem; font-weight: 700;">JobPortal</h3>
                            <span style="color: #bdc3c7; font-size: 0.8rem; font-weight: 500; letter-spacing: 1px; text-transform: uppercase;">Find Your Future</span>
                        </div>
                    </div>
                    <p style="color: #bdc3c7; line-height: 1.6; margin-bottom: 20px;">
                        Your premier destination for career opportunities and talent acquisition. Connecting job seekers with their dream careers and helping employers find exceptional talent.
                    </p>
                    <div style="display: flex; gap: 15px;">
                        <a href="#" style="color: #3498db; font-size: 1.5rem; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" style="color: #3498db; font-size: 1.5rem; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: #3498db; font-size: 1.5rem; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" style="color: #3498db; font-size: 1.5rem; transition: all 0.3s ease; text-decoration: none;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- For Job Seekers -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">For Job Seekers</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="jobs.php" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" onclick="showRegister('job_seeker')" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <i class="fas fa-user-plus"></i> Create Profile
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-file-upload"></i> Upload Resume
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-bell"></i> Job Alerts
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- For Employers -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">For Employers</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 10px;">
                            <a href="#" onclick="showRegister('employer')" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <i class="fas fa-plus-circle"></i> Post Jobs
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-users"></i> Find Talent
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-building"></i> Company Branding
                            </a>
                        </li>
                        <li style="margin-bottom: 10px;">
                            <a href="#" style="color: #bdc3c7; text-decoration: none; transition: color 0.3s ease; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-chart-line"></i> Hiring Analytics
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Support & Contact -->
                <div>
                    <h4 style="color: white; margin-bottom: 20px; font-size: 1.2rem;">Support & Contact</h4>
                    <div style="color: #bdc3c7; line-height: 1.8;">
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-envelope" style="color: #3498db;"></i>
                            <span>support@jobportal.com</span>
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-phone" style="color: #3498db;"></i>
                            <span>+251903975664</span>
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: flex-start; gap: 10px;">
                            <i class="fas fa-map-marker-alt" style="color: #3498db; margin-top: 2px;"></i>
                            <span>Ethiopia<br> Dessie, Kombolcha <br></span>
                        </p>
                        <p style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-clock" style="color: #3498db;"></i>
                            <span>24/7 Support Available</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Newsletter Signup -->
            <div style="background: rgba(52, 73, 94, 0.5); padding: 30px; border-radius: 15px; margin-bottom: 40px; text-align: center;">
                <h4 style="color: white; margin-bottom: 15px; font-size: 1.3rem;">Stay Updated</h4>
                <p style="color: #bdc3c7; margin-bottom: 20px;">Get the latest job opportunities and career tips delivered to your inbox.</p>
                <div style="display: flex; max-width: 400px; margin: 0 auto; gap: 10px;">
                    <input type="email" placeholder="Enter your email address" style="flex: 1; padding: 12px 15px; border: none; border-radius: 8px; font-size: 1rem;">
                    <button style="background: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: background 0.3s ease;">
                        Subscribe
                    </button>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div style="border-top: 1px solid #4a5568; padding-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="color: #bdc3c7;">
                    <p>&copy; 2025 JobPortal. All rights reserved. | Connecting careers, building futures.</p>
                </div>
                <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Privacy Policy</a>
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Terms of Service</a>
                    <a href="#" style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem; transition: color 0.3s ease;">Accessibility</a>
                </div>
            </div>
        </div>
    </footer>


</body>
</html>
