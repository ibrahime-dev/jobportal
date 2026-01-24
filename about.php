<?php
/**
 * ABOUT PAGE
 * 
 * Description: About page showcasing JobPortal features and information
 * Features:
 * - Company information and mission
 * - Platform features showcase
 * - Statistics and achievements
 * - Team information
 * - Technology stack highlights
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
    <title>About Us - JobPortal</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%232a5298;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%231e3c72;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='20' fill='url(%23grad)'/%3E%3Ctext x='50' y='65' font-family='Arial,sans-serif' font-size='45' fill='white' text-anchor='middle'%3E💼%3C/text%3E%3C/svg%3E">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .about-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .about-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .about-hero p {
            font-size: 1.3rem;
            max-width: 600px;
            margin: 0 auto;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .about-content {
            padding: 80px 0;
            background: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section {
            margin-bottom: 80px;
        }

        .section h2 {
            font-size: 2.5rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .section h2::after {
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

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        .stats-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }

        .stat-item {
            padding: 20px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .mission-section {
            background: white;
            padding: 80px 0;
        }

        .mission-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .mission-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
        }

        .mission-image {
            text-align: center;
            font-size: 8rem;
            color: #667eea;
            opacity: 0.8;
        }

        .tech-stack {
            background: #f8f9fa;
            padding: 80px 0;
        }

        .tech-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .tech-item {
            background: white;
            padding: 30px 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .tech-item:hover {
            transform: translateY(-5px);
        }

        .tech-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .tech-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta-section h2 {
            color: white;
            margin-bottom: 30px;
        }

        .cta-section h2::after {
            background: rgba(255,255,255,0.3);
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 40px;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: white;
            color: #667eea;
        }

        .btn-primary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-outline:hover {
            background: white;
            color: #667eea;
        }

        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2.5rem;
            }

            .about-hero p {
                font-size: 1.1rem;
            }

            .section h2 {
                font-size: 2rem;
            }

            .mission-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .mission-image {
                font-size: 5rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
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
                <a href="about.php" class="nav-link" style="color: #667eea;">About</a>
                <a href="contact.php" class="nav-link">Contact</a>
                <div class="nav-buttons">
                    <a href="index.php" class="btn btn-outline">Login</a>
                    <a href="index.php" class="btn btn-primary">Register</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <h1><i class="fas fa-users"></i> About JobPortal</h1>
            <p>Connecting talented professionals with amazing opportunities through innovative technology and exceptional user experience.</p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="about-content">
        <div class="container">
            <!-- Features Section -->
            <div class="section">
                <h2>Why Choose JobPortal?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3>Smart Job Matching</h3>
                        <p>Our advanced search and filtering system helps you find the perfect job opportunities that match your skills and preferences.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>Secure & Private</h3>
                        <p>Enterprise-grade security with multi-layer protection ensures your personal information and data remain safe and confidential.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Mobile Responsive</h3>
                        <p>Access JobPortal from any device with our fully responsive design that works seamlessly on desktop, tablet, and mobile.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3>Application Tracking</h3>
                        <p>Keep track of all your job applications with our comprehensive tracking system and get real-time updates on your progress.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3>Company Profiles</h3>
                        <p>Discover detailed company information, culture, and values to make informed decisions about your career opportunities.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3>Real-time Updates</h3>
                        <p>Get instant notifications about new job opportunities, application status changes, and important updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <h2>Our Impact in Numbers</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Active Job Seekers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">2,500+</div>
                    <div class="stat-label">Partner Companies</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">15,000+</div>
                    <div class="stat-label">Job Opportunities</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">8,500+</div>
                    <div class="stat-label">Successful Placements</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="mission-section">
        <div class="container">
            <div class="section">
                <h2>Our Mission</h2>
                <div class="mission-content">
                    <div class="mission-text">
                        <p>At JobPortal, we believe that finding the right job should be simple, efficient, and rewarding. Our mission is to bridge the gap between talented professionals and innovative companies by providing a comprehensive platform that streamlines the entire recruitment process.</p>
                        
                        <p>We are committed to creating meaningful connections that drive career growth and business success. Through cutting-edge technology, user-centric design, and unwavering dedication to excellence, we empower job seekers to discover their dream careers while helping employers find the perfect candidates.</p>
                        
                        <p>Our platform is built on the principles of transparency, security, and innovation, ensuring that every interaction is valuable, every opportunity is genuine, and every success story contributes to a thriving professional community.</p>
                    </div>
                    <div class="mission-image">
                        <i class="fas fa-rocket"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technology Stack -->
    <section class="tech-stack">
        <div class="container">
            <div class="section">
                <h2>Built with Modern Technology</h2>
                <div class="tech-grid">
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #e34c26;">
                            <i class="fab fa-html5"></i>
                        </div>
                        <div class="tech-name">HTML5</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #1572b6;">
                            <i class="fab fa-css3-alt"></i>
                        </div>
                        <div class="tech-name">CSS3</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #f7df1e;">
                            <i class="fab fa-js-square"></i>
                        </div>
                        <div class="tech-name">JavaScript</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #777bb4;">
                            <i class="fab fa-php"></i>
                        </div>
                        <div class="tech-name">PHP 8.2</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #4479a1;">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="tech-name">MySQL</div>
                    </div>
                    <div class="tech-item">
                        <div class="tech-icon" style="color: #d22128;">
                            <i class="fab fa-apache"></i>
                        </div>
                        <div class="tech-name">Apache</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="container">
            <h2>Ready to Start Your Journey?</h2>
            <p>Join thousands of professionals who have found their dream jobs through JobPortal</p>
            <div class="cta-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Join as Job Seeker
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-building"></i> Post Jobs as Employer
                </a>
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