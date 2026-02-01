-- SQL: create table to store tutor account form submissions
CREATE TABLE IF NOT EXISTS `tutor_account_submissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `submission` JSON NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
