USE clouddesk_db;

-- Add status to users table
ALTER TABLE users 
ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
