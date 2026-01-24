/**
 * =====================================================
 * JOBPORTAL MAIN JAVASCRIPT
 * =====================================================
 * 
 * Description: Main JavaScript functionality for JobPortal landing page
 * Features: Authentication, Modals, Form handling, Interactive elements
 * Author: JobPortal System
 * Version: 1.0
 * 
 * TABLE OF CONTENTS:
 * 1. Global Variables and Initialization
 * 2. Event Listeners Setup
 * 3. Authentication Functions
 * 4. User Interface Functions
 * 5. Two-Step Login System
 * 6. Navigation Management
 * 7. Notification System
 * 8. Interactive Effects
 * =====================================================
 */

/* =====================================================
   1. GLOBAL VARIABLES AND INITIALIZATION
   ===================================================== */

// Current logged-in user data
let currentUser = null;

/**
 * Main Initialization Function
 * Runs when the DOM is fully loaded
 */
document.addEventListener('DOMContentLoaded', function() {
    // Set up all event listeners
    setupEventListeners();
    
    // Check URL parameters for special actions
    const urlParams = new URLSearchParams(window.location.search);
    
    // Handle logout success message
    if (urlParams.get('logout') === 'success') {
        // Clear any remaining data and show login buttons
        localStorage.clear();
        sessionStorage.clear();
        currentUser = null;
        showNotification('Logged out successfully', 'success');
    }
    
    // Handle password reset token
    const resetToken = urlParams.get('reset_token');
    if (resetToken) {
        // Show reset password modal
        showResetPassword(resetToken);
        // Clean up URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Initialize core functionality
    checkAuthStatus();
    initInteractiveBackground();
    createParticles();
    
    // Force navigation update after a short delay to ensure DOM is ready
    setTimeout(() => {
        updateNavigation();
    }, 100);
});

/* =====================================================
   2. EVENT LISTENERS SETUP
   ===================================================== */

/**
 * Setup Event Listeners
 * Attaches event handlers to forms and interactive elements
 */
function setupEventListeners() {
    // Form submission handlers
    document.getElementById('registerForm').addEventListener('submit', handleRegister);
    document.getElementById('forgotPasswordForm').addEventListener('submit', handleForgotPassword);
    document.getElementById('resetPasswordForm').addEventListener('submit', handleResetPassword);
    
    // Name field validation
    const firstNameField = document.getElementById('firstName');
    const lastNameField = document.getElementById('lastName');
    
    if (firstNameField) {
        // Prevent invalid characters while typing
        firstNameField.addEventListener('keydown', preventInvalidNameChars);
        // Validate on input (as user types)
        firstNameField.addEventListener('input', function() {
            validateNameInput(this);
        });
        // Validate on blur (when user leaves field)
        firstNameField.addEventListener('blur', function() {
            validateNameInput(this);
        });
    }
    
    if (lastNameField) {
        // Prevent invalid characters while typing
        lastNameField.addEventListener('keydown', preventInvalidNameChars);
        // Validate on input (as user types)
        lastNameField.addEventListener('input', function() {
            validateNameInput(this);
        });
        // Validate on blur (when user leaves field)
        lastNameField.addEventListener('blur', function() {
            validateNameInput(this);
        });
    }
    
    // Phone number field validation
    const phoneField = document.getElementById('phone');
    
    if (phoneField) {
        // Prevent invalid characters while typing
        phoneField.addEventListener('keydown', preventInvalidPhoneChars);
        // Validate on input (as user types)
        phoneField.addEventListener('input', function() {
            validatePhoneInput(this);
        });
        // Validate on blur (when user leaves field)
        phoneField.addEventListener('blur', function() {
            validatePhoneInput(this);
        });
    }
    
    // User type selector buttons (for registration)
    document.querySelectorAll('.user-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectUserType(this.dataset.type);
        });
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });
}

/* =====================================================
   3. NAME VALIDATION FUNCTIONS
   ===================================================== */

/**
 * Validate Name Field
 * Checks if name contains only valid characters (letters, spaces, hyphens, apostrophes)
 * @param {string} name - The name to validate
 * @returns {object} - Validation result with isValid and message
 */
function validateName(name) {
    // Trim whitespace
    const trimmedName = name.trim();
    
    // Check if empty
    if (!trimmedName) {
        return {
            isValid: false,
            message: 'This field is required'
        };
    }
    
    // Check for numbers (0-9)
    if (/\d/.test(trimmedName)) {
        return {
            isValid: false,
            message: 'Please check your spelling. Names cannot contain numbers (0-9).'
        };
    }
    
    // Check for invalid special characters (allow only letters, spaces, hyphens, apostrophes)
    if (!/^[a-zA-ZÀ-ÿ\s\-']+$/.test(trimmedName)) {
        return {
            isValid: false,
            message: 'Please check your spelling. Names can only contain letters, spaces, hyphens (-), and apostrophes (\').'
        };
    }
    
    // Check length (reasonable limits)
    if (trimmedName.length > 50) {
        return {
            isValid: false,
            message: 'Name must be less than 50 characters'
        };
    }
    
    return {
        isValid: true,
        message: ''
    };
}

/**
 * Real-time Name Validation
 * Validates name input as user types and shows immediate feedback
 * @param {HTMLInputElement} input - The input field to validate
 */
function validateNameInput(input) {
    const validation = validateName(input.value);
    const fieldName = input.name === 'first_name' ? 'First Name' : 'Last Name';
    
    if (validation.isValid) {
        clearFieldError(input);
    } else {
        showFieldError(input, validation.message);
    }
    
    return validation.isValid;
}

/* =====================================================
   3.1 PHONE NUMBER VALIDATION FUNCTIONS
   ===================================================== */

/**
 * Validate Phone Number Field (Ethiopian Standard)
 * Checks if phone number follows Ethiopian phone number format
 * @param {string} phone - The phone number to validate
 * @returns {object} - Validation result with isValid and message
 */
function validatePhone(phone) {
    // Trim whitespace
    const trimmedPhone = phone.trim();
    
    // Allow empty phone numbers (not required field)
    if (!trimmedPhone) {
        return {
            isValid: true,
            message: ''
        };
    }
    
    // Check for letters (A-Z, a-z)
    if (/[a-zA-Z]/.test(trimmedPhone)) {
        return {
            isValid: false,
            message: 'Phone numbers can only contain numbers'
        };
    }
    
    // Check for invalid special characters (allow only numbers, spaces, hyphens, plus signs)
    if (!/^[\d\s\-\+]+$/.test(trimmedPhone)) {
        return {
            isValid: false,
            message: 'Phone numbers can only contain numbers, spaces, hyphens, and plus signs'
        };
    }
    
    // Extract only digits to check format
    const digitsOnly = trimmedPhone.replace(/\D/g, '');
    
    // Ethiopian phone number validation
    if (digitsOnly.length > 0) {
        // Check for international format (+251XXXXXXXXX)
        if (trimmedPhone.startsWith('+251')) {
            const phoneWithoutCountryCode = digitsOnly.substring(3); // Remove 251
            if (phoneWithoutCountryCode.length !== 9) {
                return {
                    isValid: false,
                    message: 'Ethiopian international format should be +251XXXXXXXXX (12 digits total)'
                };
            }
            // Check if it's a valid Ethiopian mobile or landline
            if (!phoneWithoutCountryCode.startsWith('9') && !phoneWithoutCountryCode.startsWith('11')) {
                return {
                    isValid: false,
                    message: 'Ethiopian phone numbers should start with 09 (mobile) or 011 (landline)'
                };
            }
        }
        // Check for local mobile format (09XXXXXXXX)
        else if (digitsOnly.startsWith('09')) {
            if (digitsOnly.length !== 10) {
                return {
                    isValid: false,
                    message: 'Ethiopian mobile numbers should be 10 digits (09XXXXXXXX)'
                };
            }
        }
        // Check for local landline format (011XXXXXXX)
        else if (digitsOnly.startsWith('011')) {
            if (digitsOnly.length !== 10) {
                return {
                    isValid: false,
                    message: 'Ethiopian landline numbers should be 10 digits (011XXXXXXX)'
                };
            }
        }
        // Invalid format
        else {
            return {
                isValid: false,
                message: 'Please enter a valid Ethiopian phone number: 09XXXXXXXX (mobile), 011XXXXXXX (landline), or +251XXXXXXXXX (international)'
            };
        }
    }
    
    return {
        isValid: true,
        message: ''
    };
}

/**
 * Real-time Phone Number Validation
 * Validates phone input as user types and shows immediate feedback
 * @param {HTMLInputElement} input - The input field to validate
 */
function validatePhoneInput(input) {
    const validation = validatePhone(input.value);
    
    if (validation.isValid) {
        clearFieldError(input);
    } else {
        showFieldError(input, validation.message);
    }
    
    return validation.isValid;
}

/**
 * Prevent Invalid Phone Characters (Ethiopian Standard)
 * Blocks typing of invalid characters in phone number fields
 * @param {KeyboardEvent} event - The keyboard event
 */
function preventInvalidPhoneChars(event) {
    const char = event.key;
    
    // Allow control keys (backspace, delete, arrow keys, etc.)
    if (event.ctrlKey || event.metaKey || 
        ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
        return;
    }
    
    // Block letters
    if (/[a-zA-Z]/.test(char)) {
        event.preventDefault();
        const input = event.target;
        showFieldError(input, 'Phone numbers can only contain numbers');
        // Clear error after 3 seconds
        setTimeout(() => {
            if (!/[a-zA-Z]/.test(input.value)) {
                clearFieldError(input);
            }
        }, 3000);
        return;
    }
    
    // Block invalid special characters (allow numbers, spaces, hyphens, plus signs only)
    if (!/[\d\s\-\+]/.test(char)) {
        event.preventDefault();
        const input = event.target;
        showFieldError(input, 'Phone numbers can only contain numbers, spaces, hyphens, and plus signs');
        // Clear error after 3 seconds
        setTimeout(() => {
            clearFieldError(input);
        }, 3000);
        return;
    }
}

/**
 * Show Field Error
 * Displays error message for a specific form field
 * @param {HTMLInputElement} field - The input field with error
 * @param {string} message - Error message to display
 */
function showFieldError(field, message) {
    // Remove existing error
    clearFieldError(field);
    
    // Add error class to field
    field.classList.add('error');
    field.classList.remove('valid');
    
    // Create error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    errorElement.setAttribute('role', 'alert'); // For accessibility
    errorElement.setAttribute('aria-live', 'polite');
    
    // Insert error message after the field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
    
    // Add validation icon
    addValidationIcon(field, 'error');
}

/**
 * Clear Field Error
 * Removes error styling and message from a form field
 * @param {HTMLInputElement} field - The input field to clear
 */
function clearFieldError(field) {
    // Remove error class
    field.classList.remove('error');
    
    // Remove error message
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.remove();
    }
    
    // Remove validation icon
    removeValidationIcon(field);
    
    // Add valid class if field has content
    if (field.value.trim()) {
        field.classList.add('valid');
        addValidationIcon(field, 'valid');
    }
}

/**
 * Add Validation Icon
 * Adds checkmark or error icon to form field
 * @param {HTMLInputElement} field - The input field
 * @param {string} type - 'valid' or 'error'
 */
function addValidationIcon(field, type) {
    removeValidationIcon(field);
    
    const icon = document.createElement('i');
    icon.className = `validation-icon fas ${type === 'valid' ? 'fa-check-circle' : 'fa-exclamation-circle'}`;
    
    field.parentNode.appendChild(icon);
}

/**
 * Remove Validation Icon
 * Removes validation icon from form field
 * @param {HTMLInputElement} field - The input field
 */
function removeValidationIcon(field) {
    const existingIcon = field.parentNode.querySelector('.validation-icon');
    if (existingIcon) {
        existingIcon.remove();
    }
}

/**
 * Show Multiple Field Errors
 * Displays errors for multiple fields simultaneously
 * @param {Array} fieldErrors - Array of {field, message} objects
 */
function showMultipleFieldErrors(fieldErrors) {
    fieldErrors.forEach(({field, message}) => {
        showFieldError(field, message);
    });
}

/**
 * Clear All Form Errors
 * Removes all error messages from a form
 * @param {HTMLFormElement} form - The form to clear errors from
 */
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

/**
 * Prevent Invalid Characters
 * Blocks typing of invalid characters in name fields
 * @param {KeyboardEvent} event - The keyboard event
 */
function preventInvalidNameChars(event) {
    const char = event.key;
    
    // Allow control keys (backspace, delete, arrow keys, etc.)
    if (event.ctrlKey || event.metaKey || 
        ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Tab'].includes(char)) {
        return;
    }
    
    // Block numbers
    if (/\d/.test(char)) {
        event.preventDefault();
        const input = event.target;
        showFieldError(input, 'Please check your spelling. Names cannot contain numbers (0-9).');
        // Clear error after 3 seconds
        setTimeout(() => {
            if (input.value === input.value.replace(/\d/g, '')) {
                clearFieldError(input);
            }
        }, 3000);
        return;
    }
    
    // Block invalid special characters (allow letters, spaces, hyphens, apostrophes)
    if (!/[a-zA-ZÀ-ÿ\s\-']/.test(char)) {
        event.preventDefault();
        const input = event.target;
        showFieldError(input, 'Please check your spelling. Names can only contain letters, spaces, hyphens (-), and apostrophes (\').');
        // Clear error after 3 seconds
        setTimeout(() => {
            clearFieldError(input);
        }, 3000);
        return;
    }
}

/* =====================================================
   4. AUTHENTICATION FUNCTIONS
   ===================================================== */

/**
 * Handle User Registration
 * Processes registration form submission and creates new user account
 */
async function handleRegister(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Clear any existing form errors
    clearAllFormErrors(form);
    
    // Validate name fields before proceeding
    const firstNameField = document.getElementById('firstName');
    const lastNameField = document.getElementById('lastName');
    const phoneField = document.getElementById('phone');
    
    const firstNameValid = validateNameInput(firstNameField);
    const lastNameValid = validateNameInput(lastNameField);
    const phoneValid = validatePhoneInput(phoneField);
    
    // Collect all validation errors
    const errors = [];
    
    if (!firstNameValid) {
        errors.push('Please check first name spelling');
    }
    
    if (!lastNameValid) {
        errors.push('Please check last name spelling');
    }
    
    if (!phoneValid) {
        errors.push('Please check phone number format');
    }
    
    // Validate password confirmation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password !== confirmPassword) {
        errors.push('Passwords do not match');
    }
    
    // If there are validation errors, prevent submission
    if (errors.length > 0) {
        showNotification('Please fix the following: ' + errors.join(', ') + '. Names can only contain letters, spaces, hyphens (-), and apostrophes (\'), and phone numbers can only contain numbers.', 'error');
        
        // Highlight invalid fields
        if (!firstNameValid) {
            firstNameField.focus();
        } else if (!lastNameValid) {
            lastNameField.focus();
        } else if (!phoneValid) {
            phoneField.focus();
        }
        
        return;
    }

    const registerData = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        location: formData.get('location'),
        password: password,
        user_type: formData.get('user_type')
    };

    try {
        showNotification('Creating account...', 'info');
        
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'register',
                ...registerData
            })
        });

        const result = await response.json();

        if (result.success) {
            closeModal('registerModal');
            showNotification('Account created successfully! Please login.', 'success');
            
            // Clear form for next use
            form.reset();
            clearAllFormErrors(form);
            
            showLogin();
        } else {
            showNotification(result.message || 'Registration failed', 'error');
        }
    } catch (error) {
        console.error('Registration error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

/**
 * Check Authentication Status
 * Verifies if user is logged in by checking localStorage
 */
function checkAuthStatus() {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            currentUser = JSON.parse(storedUser);
            updateNavigation();
        } catch (e) {
            // If stored user data is corrupted, clear it
            localStorage.clear();
            currentUser = null;
            updateNavigation();
        }
    } else {
        // Ensure clean state when no user data
        currentUser = null;
        updateNavigation();
    }
}

/**
 * User Logout Function
 * Clears user data and redirects to logout page
 */
function logout() {
    currentUser = null;
    localStorage.removeItem('user');
    // Clear all localStorage data for complete cleanup
    localStorage.clear();
    sessionStorage.clear();
    
    // Show notification and redirect to logout.php for complete cleanup
    showNotification('Logging out...', 'info');
    setTimeout(() => {
        window.location.href = 'logout.php';
    }, 500);
}

/**
 * Handle Forgot Password Request
 * Sends password reset email to user
 */
async function handleForgotPassword(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const email = formData.get('email');

    try {
        showNotification('Sending reset link...', 'info');
        
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'forgot_password',
                email: email
            })
        });

        const result = await response.json();

        if (result.success) {
            closeModal('forgotPasswordModal');
            let message = 'Password reset link sent to your email!';
            
            // Show demo link in development (remove in production)
            if (result.demo_link) {
                message += '\n\nDEMO: ' + result.demo_link;
                console.log('Demo reset link:', result.demo_link);
            }
            
            showNotification(message, 'success');
            document.getElementById('forgotPasswordForm').reset();
        } else {
            showNotification(result.message || 'Failed to send reset link', 'error');
        }
    } catch (error) {
        console.error('Forgot password error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

/**
 * Handle Password Reset
 * Updates user password using reset token
 */
async function handleResetPassword(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    const token = formData.get('token');
    
    if (password !== confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }

    try {
        showNotification('Updating password...', 'info');
        
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reset_password',
                token: token,
                password: password
            })
        });

        const result = await response.json();

        if (result.success) {
            closeModal('resetPasswordModal');
            showNotification('Password updated successfully! Please login.', 'success');
            document.getElementById('resetPasswordForm').reset();
            showLogin();
        } else {
            showNotification(result.message || 'Failed to update password', 'error');
        }
    } catch (error) {
        console.error('Reset password error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

/* =====================================================
   4. USER INTERFACE FUNCTIONS
   ===================================================== */

/**
 * Show Login Modal
 * Displays the login modal and resets form to step 1
 */
function showLogin() {
    resetLoginForm(); // Reset to step 1
    document.getElementById('loginModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

/**
 * Show Registration Modal
 * Displays registration modal with specified user type selected
 */
function showRegister(userType = 'job_seeker') {
    document.getElementById('registerModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // The selectAccountType function will be called by the enhanced version in index.php
    console.log('Registration modal opened for user type:', userType);
}

/**
 * Close Modal
 * Hides specified modal and restores body scroll
 */
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = 'auto';
}

function switchToRegister() {
    closeModal('loginModal');
    showRegister();
}

function switchToLogin() {
    closeModal('registerModal');
    closeModal('forgotPasswordModal');
    showLogin();
}

function showForgotPassword() {
    closeModal('loginModal');
    document.getElementById('forgotPasswordModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function showResetPassword(token) {
    closeModal('forgotPasswordModal');
    document.getElementById('resetToken').value = token;
    document.getElementById('resetPasswordModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// User type selection is now handled by selectAccountType function in index.php

/* =====================================================
   5. TWO-STEP LOGIN SYSTEM
   ===================================================== */

/**
 * Proceed to User Type Selection (Step 2)
 * Validates credentials and moves to user type selection
 */
function proceedToUserTypeSelection() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    
    if (!email || !password) {
        showNotification('Please enter both email and password', 'error');
        return;
    }
    
    // Store credentials temporarily
    window.loginCredentials = { email, password };
    
    // Show email in step 2
    document.getElementById('displayEmail').textContent = email;
    
    // Switch to step 2
    document.getElementById('loginStep1').style.display = 'none';
    document.getElementById('loginStep2').style.display = 'block';
}

/**
 * Back to Credentials (Step 1)
 * Returns to email/password input step
 */
function backToCredentials() {
    document.getElementById('loginStep2').style.display = 'none';
    document.getElementById('loginStep1').style.display = 'block';
}

/**
 * Select Login Type and Perform Login
 * Handles user type selection and initiates login process
 */
function selectLoginTypeAndLogin(userType) {
    if (!window.loginCredentials) {
        showNotification('Please enter your credentials first', 'error');
        backToCredentials();
        return;
    }
    
    // Perform login with selected user type
    performLogin(window.loginCredentials.email, window.loginCredentials.password, userType);
}

/**
 * Perform Login API Call
 * Sends login request to server with credentials and user type
 */
async function performLogin(email, password, userType) {
    try {
        showNotification('Logging in...', 'info');
        
        const response = await fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'login',
                email: email,
                password: password,
                user_type: userType
            })
        });

        const result = await response.json();

        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('user', JSON.stringify(currentUser));
            closeModal('loginModal');
            updateNavigation();
            showNotification('Login successful! Redirecting...', 'success');
            
            // Clear temporary credentials
            window.loginCredentials = null;
            
            // Reset login form
            resetLoginForm();
            
            // Redirect based on user type
            setTimeout(() => {
                window.location.href = result.redirect || 'dashboard.php';
            }, 1000);
        } else {
            // Show specific error message
            let errorMessage = result.message || 'Login failed';
            
            // If it's a user type mismatch, stay on step 2 to allow correction
            if (errorMessage.includes('account is registered as')) {
                showNotification(errorMessage, 'error');
                // Stay on step 2 so user can select correct type
            } else {
                showNotification(errorMessage, 'error');
                // Go back to step 1 for other errors
                backToCredentials();
            }
        }
    } catch (error) {
        console.error('Login error:', error);
        
        // Check if it's a network error or server error
        if (error.name === 'TypeError' || error.message.includes('fetch')) {
            showNotification('Network error. Please check your connection and try again.', 'error');
        } else {
            showNotification('An unexpected error occurred. Please try again.', 'error');
        }
        
        backToCredentials();
    }
}

/**
 * Reset Login Form
 * Clears form data and returns to step 1
 */
function resetLoginForm() {
    document.getElementById('loginEmail').value = '';
    document.getElementById('loginPassword').value = '';
    document.getElementById('loginStep2').style.display = 'none';
    document.getElementById('loginStep1').style.display = 'block';
    window.loginCredentials = null;
}

/* =====================================================
   6. NAVIGATION MANAGEMENT
   ===================================================== */

/**
 * Update Navigation Bar
 * Changes navigation buttons based on user authentication status
 */
function updateNavigation() {
    const navButtons = document.getElementById('navButtons');
    
    if (currentUser) {
        // Show logged-in user navigation
        navButtons.innerHTML = `
            <span class="user-greeting">Welcome, ${currentUser.first_name}!</span>
            <button class="btn btn-outline" onclick="showDashboard()">Dashboard</button>
            <button class="btn btn-primary" onclick="logout()">Logout</button>
        `;
    } else {
        // Show guest navigation
        navButtons.innerHTML = `
            <button class="btn btn-outline" onclick="showLogin()">Login</button>
            <button class="btn btn-primary" onclick="showRegister()">Register</button>
        `;
    }
}

/**
 * Show Dashboard
 * Redirects user to appropriate dashboard based on user type
 */
function showDashboard() {
    if (currentUser) {
        if (currentUser.user_type === 'job_seeker') {
            window.location.href = 'home.php';
        } else if (currentUser.user_type === 'employer') {
            window.location.href = 'employer-home.php';
        } else if (currentUser.user_type === 'admin') {
            window.location.href = 'admin-home.php';
        } else {
            window.location.href = 'dashboard.php';
        }
    }
}

/* =====================================================
   7. NOTIFICATION SYSTEM
   ===================================================== */

/**
 * Show Notification Toast
 * Displays temporary notification message to user
 * @param {string} message - The message to display
 * @param {string} type - Notification type (success, error, warning, info)
 */
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelectorAll('.notification');
    existing.forEach(n => n.remove());
    
    // Create new notification
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/* =====================================================
   8. INTERACTIVE EFFECTS
   ===================================================== */

/**
 * Initialize Interactive Background
 * Adds parallax effect to hero section on mouse movement
 */
function initInteractiveBackground() {
    const hero = document.querySelector('.hero');
    const heroContent = document.querySelector('.hero-content');
    
    if (hero && heroContent) {
        hero.addEventListener('mousemove', function(e) {
            const rect = hero.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width * 100;
            const y = (e.clientY - rect.top) / rect.height * 100;
            
            // Subtle parallax effect for content only
            const moveX = (x / 100 - 0.5) * 8;
            const moveY = (y / 100 - 0.5) * 8;
            
            heroContent.style.transform = `translate(${moveX}px, ${moveY}px)`;
        });
        
        hero.addEventListener('mouseleave', function() {
            heroContent.style.transform = 'translate(0, 0)';
        });
    }
}

/**
 * Create Particle System
 * Adds animated particles to hero section background
 */
function createParticles() {
    const hero = document.querySelector('.hero');
    if (!hero) return;
    
    const particleContainer = document.createElement('div');
    particleContainer.className = 'particles';
    particleContainer.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 2;
    `;
    
    hero.appendChild(particleContainer);
    
    // Create subtle white particles only
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        const size = Math.random() * 2 + 1;
        
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            animation: twinkle ${3 + Math.random() * 2}s infinite;
            animation-delay: ${Math.random() * 2}s;
        `;
        particleContainer.appendChild(particle);
    }
}

/**
 * Add Twinkle Animation
 * Creates CSS animation for particle twinkling effect
 */
const twinkleStyle = document.createElement('style');
twinkleStyle.textContent = `
    @keyframes twinkle {
        0%, 100% { 
            opacity: 0.2; 
            transform: scale(0.8); 
        }
        50% { 
            opacity: 0.8; 
            transform: scale(1); 
        }
    }
`;
document.head.appendChild(twinkleStyle);