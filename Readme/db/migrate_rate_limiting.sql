-- Rate Limiting Tables Migration
-- Exécutez ce script pour initialiser les tables de rate limiting

-- Table principale de logging des tentatives
CREATE TABLE IF NOT EXISTS `rate_limit_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `action` varchar(100) NOT NULL COMMENT 'Action identifier (login, form_submit, api_call, etc)',
    `identifier` varchar(64) NOT NULL COMMENT 'SHA256 hash of IP + User Agent',
    `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    `user_agent` varchar(255),
    `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes pour performances
    INDEX idx_action_identifier_time (action, identifier, timestamp),
    INDEX idx_action_time (action, timestamp),
    INDEX idx_identifier (identifier),
    INDEX idx_ip_address (ip_address),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Rate limiting - Enregistrement des tentatives par action';

-- Table de whitelist (trusted sources)
CREATE TABLE IF NOT EXISTS `rate_limit_whitelist` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `identifier` varchar(64) NOT NULL UNIQUE,
    `reason` varchar(255),
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Rate limiting - Whitelist de sources fiables (API, admins)';

-- Table d'audit des violations (pour alertes)
CREATE TABLE IF NOT EXISTS `rate_limit_violations` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `action` varchar(100) NOT NULL,
    `identifier` varchar(64) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `attempt_count` int(11) NOT NULL,
    `max_allowed` int(11) NOT NULL,
    `blocked_until` datetime NOT NULL,
    `violation_type` varchar(50) COMMENT 'Type: brute_force, spam, ddos, etc',
    `status` varchar(20) DEFAULT 'active' COMMENT 'active, resolved, investigated',
    `notes` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_action_status (action, status),
    INDEX idx_identifier (identifier),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Rate limiting - Violations détectées (audit compliance)';

-- Politique de taille pour la table rate_limit_logs (auto-cleanup)
-- Les vieilles entrées ( > 24h) sont supprimées automatiquement par RateLimiter::autoCleanup()

-- Procédure stockée pour nettoyer les logs (optionnel - appelé par cron)
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_cleanup_rate_limit_logs()
BEGIN
    DELETE FROM rate_limit_logs 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    DELETE FROM rate_limit_violations 
    WHERE status = 'resolved' AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$
DELIMITER ;

-- Cron job suggestion (exécutez toutes les nuits):
-- 0 2 * * * mysql -u user -ppassword database -e "CALL sp_cleanup_rate_limit_logs();"

-- Procédure pour détecter les violations massives
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_detect_rate_limit_violations()
BEGIN
    -- Détecter brute force login (> 10 tentatives en 5 min)
    INSERT INTO rate_limit_violations (action, identifier, ip_address, attempt_count, max_allowed, blocked_until, violation_type, status)
    SELECT 
        'login' as action,
        identifier,
        ip_address,
        COUNT(*) as attempt_count,
        5 as max_allowed,
        DATE_ADD(NOW(), INTERVAL 15 MINUTE) as blocked_until,
        'brute_force' as violation_type,
        'active' as status
    FROM rate_limit_logs
    WHERE action = 'login' 
    AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    GROUP BY identifier, ip_address
    HAVING COUNT(*) > 10
    ON DUPLICATE KEY UPDATE 
        attempt_count = VALUES(attempt_count),
        blocked_until = VALUES(blocked_until),
        updated_at = NOW();
    
    -- Détecter form spam (> 20 soumissions en 10 min)
    INSERT INTO rate_limit_violations (action, identifier, ip_address, attempt_count, max_allowed, blocked_until, violation_type, status)
    SELECT 
        'form_submit' as action,
        identifier,
        ip_address,
        COUNT(*) as attempt_count,
        5 as max_allowed,
        DATE_ADD(NOW(), INTERVAL 30 MINUTE) as blocked_until,
        'spam' as violation_type,
        'active' as status
    FROM rate_limit_logs
    WHERE action = 'form_submit'
    AND timestamp > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    GROUP BY identifier, ip_address
    HAVING COUNT(*) > 20;
END$$
DELIMITER ;

-- Exécuter la détection toutes les 5 minutes:
-- */5 * * * * mysql -u user -ppassword database -e "CALL sp_detect_rate_limit_violations();"

-- ============================================
-- CONFIGURATION RECOMMANDÉE PAR ACTION
-- ============================================
-- 
-- login:              5 attempts / 300 secondes (5 min)
-- password_reset:     3 attempts / 600 secondes (10 min)
-- api_call:          100 attempts / 3600 secondes (1 hour)
-- file_upload:       10 attempts / 600 secondes (10 min)
-- form_submit:        5 attempts / 60 secondes (1 min)
-- notification:      20 attempts / 3600 secondes (1 hour)
-- message_send:      10 attempts / 60 secondes (1 min)
-- account_creation:   3 attempts / 3600 secondes (1 hour)
-- export_data:        5 attempts / 3600 secondes (1 hour)
-- 

-- ============================================
-- EXEMPLES DE WHITELIST (À AJOUTER)
-- ============================================
-- 
-- INSERT INTO rate_limit_whitelist (identifier, reason) VALUES 
-- ('admin_ip_hash', 'Administrator workstation'),
-- ('api_server_hash', 'Internal API Server'),
-- ('monitoring_hash', 'Monitoring service');
