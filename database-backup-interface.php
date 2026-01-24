<?php
/**
 * DATABASE BACKUP WEB INTERFACE
 * 
 * Description: Web-based interface for database backup and recovery
 * Features: Create backups, restore database, view backup history
 * Access: Admin only
 */

session_start();

// Check admin authentication
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'database-backup-system.php';
$backupSystem = new DatabaseBackupSystem();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Backup & Recovery - JobPortal Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 5px solid #3498db;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .backup-list {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .backup-item {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .backup-item:last-child {
            border-bottom: none;
        }
        
        .backup-info h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .backup-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: none;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card i {
            font-size: 2.5rem;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-database"></i> Database Backup & Recovery</h1>
            <p>Protect your JobPortal data with automated backups and easy recovery</p>
        </div>
        
        <div class="content">
            <!-- Status Messages -->
            <div id="statusMessage" class="status"></div>
            <div id="loadingMessage" class="loading">
                <div class="spinner"></div>
                <p>Processing request...</p>
            </div>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <i class="fas fa-server"></i>
                    <h3 id="connectionStatus">Checking...</h3>
                    <p>Database Connection</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-table"></i>
                    <h3 id="tableCount">-</h3>
                    <p>Database Tables</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-archive"></i>
                    <h3 id="backupCount">-</h3>
                    <p>Available Backups</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <h3 id="lastBackup">-</h3>
                    <p>Last Backup</p>
                </div>
            </div>
            
            <!-- Create Backup Section -->
            <div class="section">
                <h2><i class="fas fa-plus-circle"></i> Create New Backup</h2>
                <form id="createBackupForm">
                    <div class="form-group">
                        <label for="backupDescription">Backup Description (Optional)</label>
                        <input type="text" id="backupDescription" name="description" placeholder="e.g., Before system update">
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Create Backup Now
                    </button>
                </form>
            </div>
            
            <!-- Backup List Section -->
            <div class="section">
                <h2><i class="fas fa-list"></i> Available Backups</h2>
                <button onclick="loadBackups()" class="btn btn-primary">
                    <i class="fas fa-refresh"></i> Refresh List
                </button>
                <div id="backupList" class="backup-list" style="margin-top: 20px;">
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>Loading backups...</p>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Recovery Section -->
            <div class="section" style="border-left-color: #e74c3c;">
                <h2><i class="fas fa-exclamation-triangle"></i> Emergency Recovery</h2>
                <p style="color: #e74c3c; margin-bottom: 20px;">
                    <strong>Warning:</strong> Database restoration will completely replace your current database. 
                    Make sure to create a backup before proceeding!
                </p>
                <div class="form-group">
                    <label for="restoreFile">Select Backup File to Restore</label>
                    <select id="restoreFile" name="restore_file">
                        <option value="">Select a backup file...</option>
                    </select>
                </div>
                <button onclick="confirmRestore()" class="btn btn-danger">
                    <i class="fas fa-undo"></i> Restore Database
                </button>
            </div>
        </div>
    </div>

    <script>
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            testConnection();
            loadBackups();
        });
        
        // Test database connection
        function testConnection() {
            fetch('database-backup-system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=test'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('connectionStatus').textContent = 'Connected';
                    document.getElementById('tableCount').textContent = data.tables;
                } else {
                    document.getElementById('connectionStatus').textContent = 'Failed';
                    document.getElementById('tableCount').textContent = 'N/A';
                }
            })
            .catch(error => {
                document.getElementById('connectionStatus').textContent = 'Error';
                console.error('Connection test failed:', error);
            });
        }
        
        // Load backup list
        function loadBackups() {
            fetch('database-backup-system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=list'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBackups(data.backups);
                    updateBackupStats(data.backups);
                } else {
                    showStatus('Failed to load backups', 'error');
                }
            })
            .catch(error => {
                showStatus('Error loading backups: ' + error.message, 'error');
            });
        }
        
        // Display backups in the list
        function displayBackups(backups) {
            const backupList = document.getElementById('backupList');
            const restoreSelect = document.getElementById('restoreFile');
            
            if (backups.length === 0) {
                backupList.innerHTML = `
                    <div style="padding: 40px; text-align: center; color: #666;">
                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 10px;"></i>
                        <p>No backups found. Create your first backup above.</p>
                    </div>
                `;
                restoreSelect.innerHTML = '<option value="">No backups available</option>';
                return;
            }
            
            let html = '';
            restoreSelect.innerHTML = '<option value="">Select a backup file...</option>';
            
            backups.forEach(backup => {
                html += `
                    <div class="backup-item">
                        <div class="backup-info">
                            <h4>${backup.filename}</h4>
                            <p><i class="fas fa-calendar"></i> ${backup.date} | 
                               <i class="fas fa-hdd"></i> ${backup.size} | 
                               <i class="fas fa-table"></i> ${backup.tables_count} tables</p>
                            ${backup.description ? `<p><i class="fas fa-info-circle"></i> ${backup.description}</p>` : ''}
                        </div>
                        <div class="backup-actions">
                            <button onclick="downloadBackup('${backup.filename}')" class="btn btn-primary">
                                <i class="fas fa-download"></i> Download
                            </button>
                            <button onclick="confirmRestore('${backup.filename}')" class="btn btn-warning">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                        </div>
                    </div>
                `;
                
                restoreSelect.innerHTML += `<option value="${backup.filename}">${backup.filename} (${backup.date})</option>`;
            });
            
            backupList.innerHTML = html;
        }
        
        // Update backup statistics
        function updateBackupStats(backups) {
            document.getElementById('backupCount').textContent = backups.length;
            
            if (backups.length > 0) {
                document.getElementById('lastBackup').textContent = backups[0].date.split(' ')[0];
            } else {
                document.getElementById('lastBackup').textContent = 'Never';
            }
        }
        
        // Create backup form handler
        document.getElementById('createBackupForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const description = document.getElementById('backupDescription').value;
            
            showLoading(true);
            
            fetch('database-backup-system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=create&description=${encodeURIComponent(description)}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    showStatus(`Backup created successfully: ${data.filename} (${data.size})`, 'success');
                    document.getElementById('backupDescription').value = '';
                    loadBackups(); // Refresh the list
                } else {
                    showStatus('Backup failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showStatus('Error creating backup: ' + error.message, 'error');
            });
        });
        
        // Confirm restore
        function confirmRestore(filename) {
            if (!filename) {
                filename = document.getElementById('restoreFile').value;
            }
            
            if (!filename) {
                showStatus('Please select a backup file to restore', 'error');
                return;
            }
            
            if (confirm(`Are you sure you want to restore the database from "${filename}"?\n\nThis will COMPLETELY REPLACE your current database!\n\nMake sure you have a recent backup before proceeding.`)) {
                restoreDatabase(filename);
            }
        }
        
        // Restore database
        function restoreDatabase(filename) {
            showLoading(true);
            
            fetch('database-backup-system.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=restore&filename=${encodeURIComponent(filename)}`
            })
            .then(response => response.json())
            .then(data => {
                showLoading(false);
                
                if (data.success) {
                    showStatus(`Database restored successfully from ${filename}. ${data.tables_restored} tables restored.`, 'success');
                    testConnection(); // Refresh connection status
                } else {
                    showStatus('Restore failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showLoading(false);
                showStatus('Error restoring database: ' + error.message, 'error');
            });
        }
        
        // Download backup (placeholder - would need server-side implementation)
        function downloadBackup(filename) {
            window.open(`backups/database/${filename}`, '_blank');
        }
        
        // Show status message
        function showStatus(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = `status ${type}`;
            statusDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 5000);
        }
        
        // Show/hide loading
        function showLoading(show) {
            document.getElementById('loadingMessage').style.display = show ? 'block' : 'none';
        }
    </script>
</body>
</html>