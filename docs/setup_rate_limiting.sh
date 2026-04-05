#!/bin/bash
# setup_rate_limiting.sh
# Script d'installation automatique du système de rate limiting
# 
# Usage: bash setup_rate_limiting.sh
# Ou depuis Docker: docker-compose exec web bash /var/www/html/setup_rate_limiting.sh

set -e

echo "🔒 Rate Limiting Setup Script"
echo "=============================="
echo ""

# Configuration
DB_HOST="${DB_HOST:-db}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-rootpassword}"
DB_NAME="${DB_NAME:-account_opening}"

echo "📊 Configuration détectée:"
echo "  Database Host: $DB_HOST"
echo "  Database User: $DB_USER"
echo "  Database Name: $DB_NAME"
echo ""

# Step 1: Check database connection
echo "✓ Vérification de la connexion à la base de données..."
if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" > /dev/null 2>&1; then
    echo "❌ Erreur: Impossible de connecter à la base de données"
    echo "   Vérifier les credentials dans .env"
    exit 1
fi
echo "✅ Connexion OK"
echo ""

# Step 2: Create tables
echo "✓ Création des tables de rate limiting..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << 'EOF'

-- Table principale pour logging
CREATE TABLE IF NOT EXISTS `rate_limit_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `action` varchar(100) NOT NULL,
    `identifier` varchar(64) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` varchar(255),
    `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action_identifier_time (action, identifier, timestamp),
    INDEX idx_action_time (action, timestamp),
    INDEX idx_identifier (identifier),
    INDEX idx_ip_address (ip_address),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table whitelist
CREATE TABLE IF NOT EXISTS `rate_limit_whitelist` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `identifier` varchar(64) NOT NULL UNIQUE,
    `reason` varchar(255),
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table violations
CREATE TABLE IF NOT EXISTS `rate_limit_violations` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `action` varchar(100) NOT NULL,
    `identifier` varchar(64) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `attempt_count` int(11) NOT NULL,
    `max_allowed` int(11) NOT NULL,
    `blocked_until` datetime NOT NULL,
    `violation_type` varchar(50),
    `status` varchar(20) DEFAULT 'active',
    `notes` text,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_action_status (action, status),
    INDEX idx_identifier (identifier),
    INDEX idx_blocked_until (blocked_until),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedures
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_cleanup_rate_limit_logs()
BEGIN
    DELETE FROM rate_limit_logs 
    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    DELETE FROM rate_limit_violations 
    WHERE status = 'resolved' AND updated_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$
DELIMITER ;

EOF

echo "✅ Tables créées"
echo ""

# Step 3: Verify tables
echo "✓ Vérification des tables..."
TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
  "SELECT COUNT(*) FROM information_schema.tables 
   WHERE table_schema='$DB_NAME' 
   AND table_name IN ('rate_limit_logs', 'rate_limit_whitelist', 'rate_limit_violations');" \
  | tail -1)

if [ "$TABLES" -eq 3 ]; then
    echo "✅ Toutes les tables sont créées"
else
    echo "⚠️  Attention: Seulement $TABLES/3 tables trouvées"
fi
echo ""

# Step 4: Display next steps
echo "🎉 Setup Terminé!"
echo ""
echo "📋 Prochaines étapes:"
echo "  1. Intégrer RateLimiter dans les fichiers PHP"
echo "  2. Consulter: RATE_LIMITING_GUIDE.md"
echo "  3. Tester localement"
echo "  4. Configurer cron jobs (optionnel mais recommandé)"
echo ""
echo "✓ Fichiers clés:"
echo "  - includes/RateLimiter.php (classe principale)"
echo "  - admin/rate_limiting_dashboard.php (monitoring)"
echo "  - RATE_LIMITING_GUIDE.md (documentation)"
echo ""
echo "📊 Dashboard: http://localhost:8080/admin/rate_limiting_dashboard.php"
echo ""
