# Application Deadline Updates - Design Document

## Architecture Overview

The application deadline update system will enhance the existing job portal with improved deadline management, notifications, and user experience features. The design follows the existing PHP/MySQL architecture while adding new components for deadline tracking and notifications.

## Database Design

### New Tables

#### deadline_modifications
```sql
CREATE TABLE deadline_modifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    old_deadline DATE,
    new_deadline DATE,
    modified_by INT NOT NULL,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (modified_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_created_at (created_at)
);
```

#### notification_preferences
```sql
CREATE TABLE notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    deadline_reminders BOOLEAN DEFAULT TRUE,
    reminder_days_before INT DEFAULT 3,
    email_notifications BOOLEAN DEFAULT TRUE,
    in_app_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_prefs (user_id)
);
```

#### deadline_notifications
```sql
CREATE TABLE deadline_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    notification_type ENUM('7_day', '2_day', 'expired') NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    recipient_id INT NOT NULL,
    
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_job_id (job_id),
    INDEX idx_sent_at (sent_at)
);
```

### Modified Tables

#### system_settings (additions)
```sql
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('min_deadline_days', '3', 'Minimum days required for application deadlines'),
('max_deadline_months', '6', 'Maximum months allowed for application deadlines'),
('urgent_min_hours', '24', 'Minimum hours for urgent job postings'),
('notification_7day_enabled', 'true', 'Enable 7-day deadline notifications'),
('notification_2day_enabled', 'true', 'Enable 2-day deadline notifications');
```

## Component Design

### 1. Deadline Management Service

#### DeadlineManager Class
```php
class DeadlineManager {
    private $pdo;
    
    public function updateJobDeadline($jobId, $newDeadline, $userId, $reason = null)
    public function validateDeadline($deadline, $isUrgent = false)
    public function getDeadlineStatus($deadline)
    public function getJobsNearingDeadline($days = 7)
    public function logDeadlineChange($jobId, $oldDeadline, $newDeadline, $userId, $reason)
}
```

### 2. Notification System

#### NotificationService Class
```php
class NotificationService {
    private $pdo;
    private $emailService;
    
    public function scheduleDeadlineNotifications()
    public function sendDeadlineReminder($jobId, $type)
    public function getUserNotificationPreferences($userId)
    public function updateNotificationPreferences($userId, $preferences)
}
```

### 3. Dashboard Enhancements

#### DeadlineWidget Component
- Visual status indicators (green/yellow/red)
- Quick deadline extension actions
- Summary statistics
- Filtering and sorting options

## API Endpoints

### Deadline Management
- `PUT /api/jobs/{id}/deadline` - Update job deadline
- `GET /api/jobs/{id}/deadline-history` - Get deadline modification history
- `POST /api/jobs/{id}/extend-deadline` - Quick deadline extension

### Notifications
- `GET /api/notifications/preferences` - Get user notification preferences
- `PUT /api/notifications/preferences` - Update notification preferences
- `GET /api/notifications/deadline-alerts` - Get pending deadline notifications

### Dashboard
- `GET /api/dashboard/deadline-summary` - Get deadline status summary
- `GET /api/jobs/deadline-status` - Get jobs with deadline status

## User Interface Design

### 1. Job Posting Edit Form
- Inline deadline editor with calendar picker
- Deadline history display
- Validation feedback
- Quick extension buttons (+7 days, +14 days, +30 days)

### 2. Employer Dashboard
- Deadline status cards with color coding
- Filter by deadline status
- Bulk actions for deadline management
- Notification settings panel

### 3. Job Seeker Interface
- Deadline countdown timers on job cards
- "Urgent" badges for jobs with deadlines within 3 days
- Deadline-based sorting and filtering
- Saved jobs with deadline reminders

### 4. Admin Panel
- Global deadline settings configuration
- Notification system monitoring
- Deadline analytics and reporting
- Bulk deadline management tools

## Validation Rules

### Standard Deadlines
- Minimum: 3 days from current date (configurable)
- Maximum: 6 months from current date (configurable)
- Cannot be set to past dates
- Must be business days for certain job types

### Urgent Deadlines
- Minimum: 24 hours from current date
- Requires special permission or admin approval
- Limited to specific job categories
- Additional validation warnings

### Weekend/Holiday Handling
- Automatic extension if deadline falls on weekend
- Holiday calendar integration (optional)
- Business day calculation for minimum periods

## Notification Logic

### Employer Notifications
1. **7-Day Warning**: Sent when deadline is 7 days away
2. **2-Day Final Notice**: Sent when deadline is 2 days away
3. **Deadline Expired**: Sent day after deadline passes

### Job Seeker Notifications
1. **Saved Job Reminder**: 3 days before deadline for saved jobs
2. **Daily Digest**: Summary of approaching deadlines
3. **Urgent Alert**: Same-day notification for jobs ending today

## Performance Considerations

### Database Optimization
- Indexed deadline fields for fast queries
- Efficient notification scheduling queries
- Pagination for deadline history

### Caching Strategy
- Cache deadline status calculations
- Cache notification preferences
- Cache system settings

### Background Processing
- Scheduled notification processing
- Deadline status updates via cron jobs
- Cleanup of expired notifications

## Security Considerations

### Access Control
- Employers can only modify their own job deadlines
- Admin approval required for urgent deadlines
- Audit trail for all deadline modifications

### Data Validation
- Server-side validation for all deadline updates
- SQL injection prevention
- XSS protection for deadline display

## Testing Strategy

### Unit Tests
- Deadline validation logic
- Notification scheduling
- Status calculation functions

### Integration Tests
- API endpoint functionality
- Database operations
- Email notification delivery

### Property-Based Tests
- Deadline validation across various inputs
- Notification timing accuracy
- Status calculation consistency

## Correctness Properties

### Property 1: Deadline Validation Consistency
**Validates: Requirements 1.4**
For any deadline date D and current date C:
- If D > C + minimum_days AND D < C + maximum_months, then validation passes
- If D <= C OR D >= C + maximum_months, then validation fails
- Urgent deadlines follow separate validation rules

### Property 2: Notification Timing Accuracy
**Validates: Requirements 1.2**
For any job with deadline D:
- 7-day notification sent when current_date = D - 7 days
- 2-day notification sent when current_date = D - 2 days
- No duplicate notifications sent for same job and type

### Property 3: Status Calculation Correctness
**Validates: Requirements 1.3**
For any job deadline D and current date C:
- Status = "active" when D > C + 7 days
- Status = "approaching" when C + 7 days >= D > C
- Status = "expired" when D <= C

## Migration Plan

### Phase 1: Database Setup
1. Create new tables
2. Add system settings
3. Migrate existing data

### Phase 2: Core Functionality
1. Implement deadline management service
2. Add API endpoints
3. Update job posting forms

### Phase 3: Notifications
1. Implement notification service
2. Add email templates
3. Set up scheduled tasks

### Phase 4: UI Enhancements
1. Update dashboard components
2. Add job seeker features
3. Implement admin panel

## Rollback Strategy

### Database Rollback
- Backup existing data before migration
- Reversible migration scripts
- Data integrity checks

### Feature Rollback
- Feature flags for new functionality
- Gradual rollout capability
- Quick disable mechanisms