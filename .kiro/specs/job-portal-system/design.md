# Job Portal System Design Document

## Overview

The Job Portal System is a three-tier web application built with HTML5/CSS3/JavaScript frontend, PHP backend, and MySQL database. The system follows a RESTful API architecture with clear separation of concerns between presentation, business logic, and data layers. The platform supports three user types (job seekers, employers, administrators) with role-based access control and responsive design for cross-device compatibility.

## Architecture

### System Architecture
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │    Backend      │    │    Database     │
│                 │    │                 │    │                 │
│ HTML/CSS/JS     │◄──►│ PHP REST API    │◄──►│ MySQL           │
│ Responsive UI   │    │ Authentication  │    │ Relational DB   │
│ AJAX Requests   │    │ Business Logic  │    │ ACID Properties │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), AJAX
- **Backend**: PHP 8.0+, RESTful API design
- **Database**: MySQL 8.0+
- **Authentication**: Session-based with secure cookies
- **File Storage**: Local filesystem with validation
- **Security**: Input validation, prepared statements, CSRF protection

## Components and Interfaces

### Frontend Components

#### 1. Authentication Module
- Login/Registration forms with client-side validation
- Session management and automatic logout
- Password strength validation
- Form submission via AJAX

#### 2. Dashboard Components
- Job Seeker Dashboard: Application tracking, job recommendations
- Employer Dashboard: Job posting management, candidate overview
- Admin Dashboard: System analytics, user management

#### 3. Job Management Interface
- Job listing cards with filtering and search
- Job detail modal/page with application functionality
- Job posting form with rich text editor
- Advanced search with multiple filter criteria

#### 4. Profile Management
- User profile forms with image upload
- Resume upload and management
- Company profile with branding elements
- Profile validation and preview

### Backend API Endpoints

#### Authentication Endpoints
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/session
```

#### User Management Endpoints
```
GET    /api/users/profile
PUT    /api/users/profile
POST   /api/users/upload-resume
GET    /api/users/{id}
DELETE /api/users/{id}
```

#### Job Management Endpoints
```
GET    /api/jobs
POST   /api/jobs
GET    /api/jobs/{id}
PUT    /api/jobs/{id}
DELETE /api/jobs/{id}
GET    /api/jobs/search
```

#### Application Management Endpoints
```
POST   /api/applications
GET    /api/applications/user/{userId}
GET    /api/applications/job/{jobId}
PUT    /api/applications/{id}/status
```

#### Admin Endpoints
```
GET    /api/admin/analytics
GET    /api/admin/users
PUT    /api/admin/users/{id}/status
GET    /api/admin/jobs
DELETE /api/admin/jobs/{id}
```

## Data Models

### User Entity
```sql
users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('job_seeker', 'employer', 'admin') NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
)
```

### Job Seeker Profile Entity
```sql
job_seeker_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT FOREIGN KEY REFERENCES users(id),
    skills TEXT,
    experience_years INT,
    education VARCHAR(255),
    resume_filename VARCHAR(255),
    desired_salary_min DECIMAL(10,2),
    desired_salary_max DECIMAL(10,2),
    availability VARCHAR(50)
)
```

### Employer Profile Entity
```sql
employer_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT FOREIGN KEY REFERENCES users(id),
    company_name VARCHAR(255) NOT NULL,
    company_description TEXT,
    industry VARCHAR(100),
    company_size VARCHAR(50),
    website VARCHAR(255),
    logo_filename VARCHAR(255)
)
```

### Job Posting Entity
```sql
job_postings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employer_id INT FOREIGN KEY REFERENCES users(id),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    location VARCHAR(255) NOT NULL,
    salary_min DECIMAL(10,2),
    salary_max DECIMAL(10,2),
    job_type VARCHAR(50),
    category VARCHAR(100),
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    application_deadline DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
)
```

### Application Entity
```sql
applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT FOREIGN KEY REFERENCES job_postings(id),
    job_seeker_id INT FOREIGN KEY REFERENCES users(id),
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cover_letter TEXT,
    notes TEXT
)
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: User registration creates valid accounts
*For any* valid registration data (email, password, user type), creating a new user account should result in a user record with encrypted password and unique email constraint enforcement
**Validates: Requirements 1.1, 4.1**

### Property 2: Authentication round trip
*For any* valid user credentials, logging in then checking session status should confirm the authenticated user identity
**Validates: Requirements 1.4, 4.3**

### Property 3: Job search filtering consistency
*For any* combination of search filters (location, salary, category, keywords), all returned job postings should match every applied filter criterion
**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5**

### Property 4: Application uniqueness constraint
*For any* job seeker and job posting combination, attempting to apply multiple times should result in only one application record
**Validates: Requirements 3.4**

### Property 5: Application status tracking
*For any* application status update by an employer, the job seeker's dashboard should reflect the same status change
**Validates: Requirements 3.2, 3.3, 6.2**

### Property 6: Job posting management consistency
*For any* job posting modification by an employer, the changes should be reflected in search results while preserving existing application relationships
**Validates: Requirements 5.2**

### Property 7: File upload validation
*For any* file upload attempt, only valid file types should be accepted and stored securely with proper filename sanitization
**Validates: Requirements 1.2, 9.5**

### Property 8: API authentication enforcement
*For any* protected API endpoint, requests without valid authentication should be rejected with appropriate error responses
**Validates: Requirements 9.2**

### Property 9: Responsive design consistency
*For any* viewport size, the interface should maintain usability and visual hierarchy without horizontal scrolling or broken layouts
**Validates: Requirements 8.1, 8.2**

### Property 10: Admin privilege enforcement
*For any* administrative action, only users with admin role should be able to access admin endpoints and modify system data
**Validates: Requirements 7.1, 7.2, 7.3**

## Error Handling

### Frontend Error Handling
- Form validation with real-time feedback
- AJAX error handling with user-friendly messages
- Network connectivity error detection
- File upload error handling (size, type validation)
- Session timeout detection and redirect

### Backend Error Handling
- Input validation with detailed error messages
- Database connection error handling
- File system error handling
- Authentication/authorization error responses
- Rate limiting and abuse prevention

### Database Error Handling
- Connection pooling and retry logic
- Transaction rollback on failures
- Constraint violation handling
- Backup and recovery procedures
- Data integrity validation

## Testing Strategy

### Unit Testing Approach
- Test individual PHP functions and classes
- Test JavaScript utility functions
- Test database query functions
- Test file upload and validation logic
- Test authentication and session management

### Property-Based Testing Approach
- Use PHPUnit with property-based testing extensions
- Configure each property test to run minimum 100 iterations
- Test universal properties across all valid inputs
- Generate random test data for comprehensive coverage
- Validate system behavior under various conditions

### Integration Testing
- Test complete user workflows (registration → login → job application)
- Test API endpoint integration
- Test database transaction integrity
- Test file upload and storage workflows
- Test cross-browser compatibility

### Testing Framework Selection
- **Backend**: PHPUnit for unit and property-based testing
- **Frontend**: Jest for JavaScript testing
- **Integration**: Selenium WebDriver for end-to-end testing
- **Database**: MySQL test database with fixtures
- **API**: Postman/Newman for API testing