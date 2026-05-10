<?php
require_once 'config.php';

try {
    echo "Checking database schema...\n";
    
    // Check if otp_code exists in users table
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Applying schema updates for OTP...\n";
        $pdo->exec("ALTER TABLE users 
        ADD COLUMN otp_code VARCHAR(10) NULL,
        ADD COLUMN otp_expiration DATETIME NULL;");
        echo "Users table updated with OTP columns.\n";
    } else {
        echo "OTP columns already exist.\n";
    }
    
    // Check if slaconfigs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'slaconfigs'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Creating slaconfigs table...\n";
        $pdo->exec("CREATE TABLE IF NOT EXISTS slaconfigs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            priority VARCHAR(50) NOT NULL UNIQUE,
            first_response_minutes INT NOT NULL,
            resolution_minutes INT NOT NULL
        );");
        
        $pdo->exec("INSERT IGNORE INTO slaconfigs (priority, first_response_minutes, resolution_minutes) VALUES 
        ('Critical', 15, 60),
        ('High', 60, 240),
        ('Medium', 480, 1440),
        ('Low', 1440, 4320);");
        echo "SLA configs created and seeded.\n";
    } else {
        echo "slaconfigs table already exists.\n";
    }
    
    // Check if tickets table has SLA columns
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'sla_status'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Updating tickets table for SLA...\n";
        $pdo->exec("ALTER TABLE tickets 
        ADD COLUMN sla_status ENUM('on-track', 'warning', 'breached') DEFAULT 'on-track',
        ADD COLUMN sla_first_response_due DATETIME NULL,
        ADD COLUMN sla_resolution_due DATETIME NULL,
        ADD COLUMN first_response_at DATETIME NULL,
        ADD COLUMN resolved_at DATETIME NULL;");
        echo "Tickets table updated for SLA.\n";
    } else {
        echo "SLA columns in tickets already exist.\n";
    }
    
    // Check if tickets table has attachment_path
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets LIKE 'attachment_path'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Adding attachment_path to tickets table...\n";
        $pdo->exec("ALTER TABLE tickets ADD COLUMN attachment_path VARCHAR(255) NULL;");
        echo "attachment_path added.\n";
    } else {
        echo "attachment_path already exists.\n";
    }
    
    // Seed users if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Seeding default users...\n";
        $users = [
            ['Admin User', 'admin@clouddesk.com', password_hash('Admin@123', PASSWORD_DEFAULT), 'admin'],
            ['Jane Doe', 'user@clouddesk.com', password_hash('User@123', PASSWORD_DEFAULT), 'user'],
            ['John Tech', 'tech@clouddesk.com', password_hash('Tech@123', PASSWORD_DEFAULT), 'technician']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        foreach ($users as $user) {
            $stmt->execute($user);
        }
        echo "Default users seeded.\n";
    } else {
        echo "Users table already has data.\n";
    }
    
    echo "Database up to date.\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
