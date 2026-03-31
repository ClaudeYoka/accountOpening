-- Monitoring Tables for Account Opening Application
-- These tables support the business metrics collection

USE accountopening_db;

-- Create audit_logs table if it doesn't exist (for security metrics)
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_timestamp (action, timestamp),
    INDEX idx_user_timestamp (user_id, timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS tblnotification (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    submission_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_emp_read (emp_id, is_read),
    INDEX idx_type_created (type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create login tracking table if it doesn't exist
CREATE TABLE IF NOT EXISTS tbl_logins (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    login_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_emp_time (emp_id, login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample data for testing (optional)
-- Uncomment these lines if you want sample data for testing the monitoring

/*
-- Sample audit logs
INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES
(1, 'LOGIN_SUCCESS', '{"user_id": 1}', '127.0.0.1'),
(2, 'LOGIN_FAILED', '{"reason": "wrong_password"}', '127.0.0.1'),
(1, 'ADMIN_PAGE_ACCESS', '{"page": "dashboard"}', '127.0.0.1');

-- Sample notifications
INSERT INTO tblnotification (emp_id, message, type, is_read) VALUES
(1, 'Nouvelle demande de chéquier reçue', 'chequier', 0),
(2, 'Mise à jour de sécurité disponible', 'system', 0);

-- Sample login records
INSERT INTO tbl_logins (emp_id, ip_address) VALUES
(1, '127.0.0.1'),
(2, '127.0.0.1');
*/