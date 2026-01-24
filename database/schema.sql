-- =====================================================
-- JOB PORTAL DATABASE SCHEMA (FIXED FOR INSERT COMPATIBILITY)
-- MySQL 8+
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS schema_version;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS job_results;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS job_postings;
DROP TABLE IF EXISTS employer_profiles;
DROP TABLE IF EXISTS job_seeker_profiles;
DROP TABLE IF EXISTS job_categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USERS
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('job_seeker','employer','admin') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- JOB CATEGORIES
-- =====================================================
CREATE TABLE job_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- JOB SEEKER PROFILES (1–1)
-- =====================================================
CREATE TABLE job_seeker_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    skills TEXT,
    experience_years INT DEFAULT 0,
    education VARCHAR(500),
    desired_salary_min DECIMAL(10,2),
    desired_salary_max DECIMAL(10,2),
    bio TEXT,
    resume_filename VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_js_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
);

-- =====================================================
-- EMPLOYER PROFILES (1–1)
-- =====================================================
CREATE TABLE employer_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    company_description TEXT,
    industry VARCHAR(100),
    company_size VARCHAR(50),
    website VARCHAR(255),
    headquarters VARCHAR(255),
    logo_filename VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_emp_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT
);

-- =====================================================
-- JOB POSTINGS
-- =====================================================
CREATE TABLE job_postings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    category_id INT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    location VARCHAR(255) NOT NULL,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    job_type ENUM('full_time','part_time','contract','freelance','internship','remote') NOT NULL,
    experience_level ENUM('entry','junior','mid','senior','executive') NOT NULL,
    application_deadline DATE DEFAULT NULL,
    views_count INT DEFAULT 0,
    applications_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_job_employer
        FOREIGN KEY (employer_id) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_job_category
        FOREIGN KEY (category_id) REFERENCES job_categories(id)
        ON DELETE SET NULL
);

-- =====================================================
-- APPLICATIONS
-- =====================================================
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    job_seeker_id INT NOT NULL,
    status ENUM('pending','reviewed','shortlisted','interviewed','accepted','rejected') DEFAULT 'pending',
    cover_letter TEXT,
    notes TEXT,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status_updated_at TIMESTAMP,

    CONSTRAINT fk_app_job
        FOREIGN KEY (job_id) REFERENCES job_postings(id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_app_seeker
        FOREIGN KEY (job_seeker_id) REFERENCES users(id)
        ON DELETE RESTRICT,

    CONSTRAINT uq_job_seeker UNIQUE (job_id, job_seeker_id)
);

-- =====================================================
-- JOB RESULTS
-- =====================================================
CREATE TABLE job_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL UNIQUE,
    total_positions INT NOT NULL,
    total_applicants INT DEFAULT 0,
    hired_candidates INT DEFAULT 0,
    result_description TEXT,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_result_job
        FOREIGN KEY (job_id) REFERENCES job_postings(id)
        ON DELETE RESTRICT,

    CHECK (total_positions > 0),
    CHECK (hired_candidates <= total_applicants)
);

-- =====================================================
-- DATA INSERTS (UNCHANGED)
-- =====================================================

INSERT INTO job_categories (name, description, icon, sort_order) VALUES
('Technology','Software development, IT, and tech-related positions','laptop-code',1),
('Healthcare','Medical, nursing, and healthcare positions','heartbeat',2),
('Finance','Banking, accounting, and financial services','chart-line',3),
('Education','Teaching, training, and educational roles','graduation-cap',4),
('Marketing','Digital marketing, advertising, and promotion','bullhorn',5),
('Sales','Sales representatives and business development','handshake',6),
('Engineering','Civil, mechanical, and engineering positions','cogs',7),
('Design','Graphic design, UI/UX, and creative roles','palette',8),
('Human Resources','HR, recruitment, and people management','users',9),
('Customer Service','Support, service, and customer relations','headset',10),
('Manufacturing','Production, quality control, and operations','industry',11),
('Legal','Legal counsel, paralegal, and law-related roles','gavel',12);

INSERT INTO users (email,password_hash,user_type,first_name,last_name,is_active,email_verified) VALUES
('admin@jobportal.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin','System','Administrator',TRUE,TRUE);

INSERT INTO users (email,password_hash,user_type,first_name,last_name,phone,location,is_active,email_verified)
VALUES ('employer@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','employer','John','Smith','+1234567890','New York, NY',TRUE,TRUE);

INSERT INTO users (email,password_hash,user_type,first_name,last_name,phone,location,is_active,email_verified)
VALUES ('jobseeker@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','job_seeker','Jane','Doe','+1234567891','San Francisco, CA',TRUE,TRUE);

INSERT INTO employer_profiles (user_id,company_name,company_description,industry,company_size,website,headquarters)
VALUES (2,'TechCorp Solutions','Leading technology solutions provider specializing in web development and digital transformation.','Technology','50-200','https://techcorp.example.com','New York, NY');

INSERT INTO job_seeker_profiles (user_id,skills,experience_years,education,desired_salary_min,desired_salary_max,bio)
VALUES (3,'JavaScript, React, Node.js, Python, SQL',3,'Bachelor of Computer Science',60000.00,80000.00,'Passionate full-stack developer with 3 years of experience building modern web applications.');

INSERT INTO job_postings (employer_id,category_id,title,description,requirements,responsibilities,location,salary_min,salary_max,job_type,experience_level)
VALUES
(2,1,'Senior Full Stack Developer','We are looking for an experienced full stack developer to join our growing team.','Bachelor degree in Computer Science or related field. 3+ years of experience with React, Node.js, and databases.','Develop and maintain web applications, collaborate with cross-functional teams, mentor junior developers.','New York, NY',70000.00,90000.00,'full_time','senior'),
(2,1,'Frontend Developer','Join our team as a frontend developer and help create amazing user experiences.','2+ years of experience with React, HTML, CSS, JavaScript. Knowledge of modern frontend tools and frameworks.','Build responsive user interfaces, optimize application performance, work closely with designers.','Remote',50000.00,70000.00,'remote','mid'),
(2,2,'Digital Marketing Specialist','We need a creative digital marketing specialist to drive our online presence.','Bachelor degree in Marketing or related field. Experience with SEO, SEM, social media marketing.','Develop marketing campaigns, manage social media accounts, analyze marketing metrics.','New York, NY',45000.00,60000.00,'full_time','mid');

-- =====================================================
-- SCHEMA VERSION
-- =====================================================
CREATE TABLE schema_version (
    version VARCHAR(20) PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT
);

INSERT INTO schema_version (version, description)
VALUES ('1.0.0', 'Initial clean job portal schema');
