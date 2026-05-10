USE clouddesk_db;

-- Update users table for OTP
ALTER TABLE users 
ADD COLUMN otp_code VARCHAR(10) NULL,
ADD COLUMN otp_expiration DATETIME NULL;

-- Update tickets table for SLA
ALTER TABLE tickets 
ADD COLUMN sla_status ENUM('on-track', 'warning', 'breached') DEFAULT 'on-track',
ADD COLUMN sla_first_response_due DATETIME NULL,
ADD COLUMN sla_resolution_due DATETIME NULL,
ADD COLUMN first_response_at DATETIME NULL,
ADD COLUMN resolved_at DATETIME NULL;

-- Create SLA Configs table
CREATE TABLE IF NOT EXISTS slaconfigs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    priority VARCHAR(50) NOT NULL UNIQUE,
    first_response_minutes INT NOT NULL,
    resolution_minutes INT NOT NULL
);

-- Seed SLA Configs
INSERT IGNORE INTO slaconfigs (priority, first_response_minutes, resolution_minutes) VALUES 
('Critical', 15, 60),
('High', 60, 240),
('Medium', 480, 1440),
('Low', 1440, 4320);
