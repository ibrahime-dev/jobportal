<?php
/**
 * PASSWORD RESET PAGE
 * 
 * Description: Handles password reset functionality with secure tokens
 * Features:
 * - Token validation for password reset
 * - Secure password reset form
 * - Password strength validation
 * - Token expiration handling
 * - Success/error messaging
 * - Redirect to login after reset
 * 
 * User Types: All users (public access)
 * Access: Public page accessed via email reset links
 */

// Check if token is provided
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - JobPortal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }

        .reset-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 90%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            color: #2a5298;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .logo i {
            margin-right: 10px;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2a5298;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #2a5298;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-bottom: 20px;
        }

        .btn:hover {
            background: #1e3c72;
        }

        .back-link {
            color: #2a5298;
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .notification {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .notification.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="logo">
            <i class="fas fa-briefcase"></i>JobPortal
        </div>
        <h1>Reset Your Password</h1>
        <p class="subtitle">Enter your new password below to complete the reset process.</p>
        
        <div id="notification" style="display: none;"></div>
        
        <form id="resetPasswordForm">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required minlength="6" placeholder="Enter your new password">
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <input type="password" id="confirmPassword" name="confirm_password" required minlength="6" placeholder="Confirm your new password">
            </div>
            
            <button type="submit" class="btn">Update Password</button>
        </form>
        
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </div>

    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
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
                    showNotification('Password updated successfully! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    showNotification(result.message || 'Failed to update password', 'error');
                }
            } catch (error) {
                console.error('Reset password error:', error);
                showNotification('Network error. Please try again.', 'error');
            }
        });

        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
        }
    </script>
</body>
</html>