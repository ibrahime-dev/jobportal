<?php
/**
 * CONTACT PAGE
 * 
 * Description: Contact page with company information and contact details
 * Features:
 * - Company contact information
 * - Office locations and details
 * - Social media links
 * - FAQ section
 * 
 * User Types: Public access
 * Access: Available to all visitors
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - JobPortal</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%232a5298;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%231e3c72;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23grad)'/%3E%3Ctext x='50' y='65' font-family='Arial,sans-serif' font-size='45' fill='white' text-anchor='middle'%3E💼%3C/text%3E%3C/svg%3E">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .contact-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .contact-hero p {
            font-size: 1.3rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .contact-content {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .contact-info-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-bottom: 80px;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .contact-info {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .contact-info h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2rem;
            text-align: center;
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .contact-item:hover {
            transform: translateX(5px);
        }

        .contact-icon {
            font-size: 1.5rem;
            color: #667eea;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }

        .contact-details h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .contact-details p {
            color: #666;
            margin: 0;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            text-decoration: none;
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .social-link:hover {
            transform: translateY(-3px);
        }

        .faq-section {
            background: white;
            padding: 80px 0;
        }

        .faq-section h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 50px;
            font-size: 2.5rem;
            position: relative;
        }

        .faq-section h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
        }

        .faq-item {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .faq-item h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }

        .faq-item p {
            color: #666;
            line-height: 1.6;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
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

        .office-locations {
            background: white;
            padding: 80px 0;
        }

        .office-locations h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 50px;
            font-size: 2.5rem;
            position: relative;
        }

        .office-locations h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }

        .offices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .office-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid #667eea;
            transition: transform 0.3s ease;
        }

        .office-card:hover {
            transform: translateY(-5px);
        }

        .office-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .office-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .contact-hero h1 {
                font-size: 2.5rem;
            }

            .contact-hero p {
                font-size: 1.1rem;
            }

            .contact-info-section {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .contact-info {
                padding: 30px 20px;
            }

            .faq-grid {
                grid-template-columns: 1fr;
            }

            .offices-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
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
                <a href="index.php" class="nav-link">Home</a>
                <a href="jobs.php" class="nav-link">Jobs</a>
                <a href="about.php" class="nav-link">About</a>
                <a href="contact.php" class="nav-link" style="color: #667eea;">Contact</a>
                <div class="nav-buttons">
                    <a href="index.php" class="btn btn-outline">Login</a>
                    <a href="index.php" class="btn btn-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="contact-hero">
        <div class="container">
            <h1><i class="fas fa-map-marker-alt"></i> Contact Information</h1>
            <p>Find all the ways to get in touch with us. We're here to help you succeed.</p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="contact-content">
        <div class="container">
            <div class="contact-info-section">
                <!-- Main Contact Information -->
                <div class="contact-info">
                    <h2><i class="fas fa-info-circle"></i> Get in Touch</h2>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Headquarters</h4>
                            <p>addise abeba<br>dessie, kombolcha<br>Ethiopia</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Phone Number</h4>
                            <p>+1 (555) 123-4567<br>Mon - Fri, 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Email Address</h4>
                            <p>info@jobportal.com<br>support@jobportal.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Business Hours</h4>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 4:00 PM<br>Sunday: Closed</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>

                <!-- Support Information -->
                <div class="contact-info">
                    <h2><i class="fas fa-headset"></i> Support Center</h2>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-life-ring"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Technical Support</h4>
                            <p>support@jobportal.com<br>24/7 Online Support Available</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Business Partnerships</h4>
                            <p>partnerships@jobportal.com<br>For collaboration opportunities</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Media & Press</h4>
                            <p>press@jobportal.com<br>For media inquiries and press releases</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Security & Privacy</h4>
                            <p>security@jobportal.com<br>Report security issues or concerns</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="contact-details">
                            <h4>Feedback</h4>
                            <p>feedback@jobportal.com<br>We value your suggestions</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Locations -->
    <section class="office-locations">
        <div class="container">
            <h2>Our Locations</h2>
            <div class="offices-grid">
                <div class="office-card">
                    <h3><i class="fas fa-building"></i> Main Office</h3>
                    <p><strong>Address:</strong><br>ETHIOPIA<br>ADDISE ABEBA</p>
                    <p><strong>Phone:</strong> +25193846846</p>
                    
                </div>
                
                <div class="office-card">
                    <h3><i class="fas fa-building"></i> Regional Office</h3>
                    <p><strong>Address:</strong><br>AMHARA <br>Dessie, kombolcha</p>
                    <p><strong>Phone:</strong> +251975354644564</p>
                    
                </div>
                
                <div class="office-card">
                    <h3><i class="fas fa-building"></i> Support Center</h3>
                    <p><strong>Address:</strong><br>addise abeba<br>bolle, piasa</p>
                    <p><strong>Phone:</strong> +25284694869843</p>
                    <p><strong>Hours:</strong> 24/7 Support Available</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2>Frequently Asked Questions</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> How do I create an account?</h4>
                    <p>Click on the "Register" button on the homepage and choose whether you're a Job Seeker or Employer. Fill in your details and verify your email to get started.</p>
                </div>
                
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Is JobPortal free to use?</h4>
                    <p>Yes! Job seekers can use JobPortal completely free. Employers have access to basic features for free, with premium options available for enhanced visibility.</p>
                </div>
                
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> How do I apply for jobs?</h4>
                    <p>Browse our job listings, click on any job that interests you, and hit the "Apply Now" button. Make sure your profile and resume are up to date for the best results.</p>
                </div>
                
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> Can I edit my job postings?</h4>
                    <p>Absolutely! Employers can edit their job postings at any time through their dashboard. You can update job descriptions, requirements, and application deadlines.</p>
                </div>
                
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> How secure is my data?</h4>
                    <p>We take security seriously. Your data is protected with enterprise-grade encryption, secure servers, and we never share your personal information without consent.</p>
                </div>
                
                <div class="faq-item">
                    <h4><i class="fas fa-question-circle"></i> How can I contact support?</h4>
                    <p>You can reach our support team through this contact form, email us at support@jobportal.com, or call us during business hours at +1 (555) 123-4567.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer style="background: #2c3e50; color: white; padding: 40px 0; text-align: center;">
        <div class="container">
            <div style="margin-bottom: 20px;">
                <div class="logo-icon" style="display: inline-block; margin-right: 10px;">
                    <i class="fas fa-briefcase" style="color: #3498db;"></i>
                </div>
                <span style="font-size: 1.5rem; font-weight: bold;">JobPortal</span>
            </div>
            <p style="opacity: 0.8; margin-bottom: 20px;">Connecting talent with opportunity</p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-bottom: 20px;">
                <a href="index.php" style="color: white; text-decoration: none;">Home</a>
                <a href="jobs.php" style="color: white; text-decoration: none;">Jobs</a>
                <a href="about.php" style="color: white; text-decoration: none;">About</a>
                <a href="contact.php" style="color: white; text-decoration: none;">Contact</a>
            </div>
            <p style="opacity: 0.6; font-size: 0.9rem;">&copy; 2025 JobPortal. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>