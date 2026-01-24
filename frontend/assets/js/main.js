// Global variables
let currentUser = null;
const API_BASE_URL = '../backend/api';

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupEventListeners();
    checkAuthStatus();
});

// Initialize application
function initializeApp() {
    console.log('JobPortal application initialized');
}

// Setup event listeners
function setupEventListeners() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // User type selector
    const userTypeButtons = document.querySelectorAll('.user-type-btn');
    userTypeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            selectUserType(this.dataset.type);
        });
    });

    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    if (hamburger) {
        hamburger.addEventListener('click', toggleMobileMenu);
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
}

// Authentication functions
async function handleLogin(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const loginData = {
        email: formData.get('email'),
        password: formData.get('password')
    };

    try {
        showLoading('Logging in...');
        
        const response = await fetch(`${API_BASE_URL}/auth/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(loginData)
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            currentUser = result.user;
            localStorage.setItem('user', JSON.stringify(currentUser));
            closeModal('loginModal');
            redirectToDashboard(currentUser.user_type);
            showNotification('Login successful!', 'success');
        } else {
            showNotification(result.message || 'Login failed', 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Login error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

async function handleRegister(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Validate password confirmation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');
    
    if (password !== confirmPassword) {
        showNotification('Passwords do not match', 'error');
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
        showLoading('Creating account...');
        
        const response = await fetch(`${API_BASE_URL}/auth/register.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(registerData)
        });

        const result = await response.json();
        hideLoading();

        if (result.success) {
            closeModal('registerModal');
            showNotification('Account created successfully! Please login.', 'success');
            showLogin();
        } else {
            showNotification(result.message || 'Registration failed', 'error');
        }
    } catch (error) {
        hideLoading();
        console.error('Registration error:', error);
        showNotification('Network error. Please try again.', 'error');
    }
}

async function checkAuthStatus() {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            currentUser = JSON.parse(storedUser);
            
            // Verify session with server
            const response = await fetch(`${API_BASE_URL}/auth/session.php`);
            const result = await response.json();
            
            if (result.success && result.user) {
                currentUser = result.user;
                updateNavigation();
            } else {
                logout();
            }
        } catch (error) {
            console.error('Session check error:', error);
            logout();
        }
    }
}

function logout() {
    currentUser = null;
    localStorage.removeItem('user');
    
    // Call logout endpoint
    fetch(`${API_BASE_URL}/auth/logout.php`, {
        method: 'POST'
    }).catch(error => console.error('Logout error:', error));
    
    updateNavigation();
    window.location.href = 'index.html';
}

// UI Functions - FIXED
function showLogin() {
    const modal = document.getElementById('loginModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    
    // Focus on first input
    setTimeout(() => {
        const firstInput = modal.querySelector('input[type="email"]');
        if (firstInput) firstInput.focus();
    }, 100);
}

function showRegister(userType = 'job_seeker') {
    const modal = document.getElementById('registerModal');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
    selectUserType(userType);
    
    // Focus on first input
    setTimeout(() => {
        const firstInput = modal.querySelector('input[type="text"]');
        if (firstInput) firstInput.focus();
    }, 100);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
        
        // Clear form data
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

function switchToRegister() {
    closeModal('loginModal');
    showRegister();
}

function switchToLogin() {
    closeModal('registerModal');
    showLogin();
}

function selectUserType(type) {
    // Update buttons
    document.querySelectorAll('.user-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    const activeBtn = document.querySelector(`[data-type="${type}"]`);
    if (activeBtn) {
        activeBtn.classList.add('active');
    }
    
    // Update hidden input
    const userTypeInput = document.getElementById('userType');
    if (userTypeInput) {
        userTypeInput.value = type;
    }
}

function redirectToDashboard(userType) {
    switch(userType) {
        case 'job_seeker':
            window.location.href = 'job-seeker-dashboard.html';
            break;
        case 'employer':
            window.location.href = 'employer-dashboard.html';
            break;
        case 'admin':
            window.location.href = 'admin-dashboard.html';
            break;
        default:
            window.location.href = 'index.html';
    }
}

function updateNavigation() {
    const navButtons = document.querySelector('.nav-buttons');
    
    if (currentUser) {
        navButtons.innerHTML = `
            <span class="user-greeting">Hello, ${currentUser.first_name}</span>
            <button class="btn btn-outline" onclick="redirectToDashboard('${currentUser.user_type}')">Dashboard</button>
            <button class="btn btn-primary" onclick="logout()">Logout</button>
        `;
    } else {
        navButtons.innerHTML = `
            <button class="btn btn-outline" onclick="showLogin()">Login</button>
            <button class="btn btn-primary" onclick="showRegister()">Register</button>
        `;
    }
}

function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.classList.toggle('active');
}

// Utility functions
function showLoading(message = 'Loading...') {
    // Create loading overlay if it doesn't exist
    let loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loadingOverlay';
        loadingOverlay.innerHTML = `
            <div class="loading-content">
                <div class="spinner"></div>
                <p id="loadingMessage">${message}</p>
            </div>
        `;
        loadingOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 3000;
        `;
        document.body.appendChild(loadingOverlay);
        
        // Add spinner styles
        const style = document.createElement('style');
        style.textContent = `
            .loading-content {
                text-align: center;
                color: white;
            }
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.getElementById('loadingMessage').textContent = message;
    loadingOverlay.style.display = 'flex';
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 6px;
        color: white;
        font-weight: 500;
        z-index: 4000;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
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
    
    // Add animation styles if not already added
    if (!document.getElementById('notificationStyles')) {
        const style = document.createElement('style');
        style.id = 'notificationStyles';
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePassword(password) {
    return password.length >= 6;
}

function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/\s/g, ''));
}