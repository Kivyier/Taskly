-- ─── Create Database ─────────────────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `task_db`;
USE `task_db`;

-- ─── Create Tasks Table ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tasks` (
  `id`               INT AUTO_INCREMENT PRIMARY KEY,
  `task_name`        VARCHAR(255) NOT NULL,
  `task_description` TEXT NOT NULL,
  `priority`         ENUM('p1','p2','p3','p4') DEFAULT 'p4',
  `label`            VARCHAR(50) DEFAULT '',
  `status`           ENUM('pending','completed') DEFAULT 'pending',
  `task_date`        DATE NOT NULL DEFAULT (CURRENT_DATE),
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── Sample Data ────────────────────────────────────────────────────
INSERT INTO `tasks` (`task_name`, `task_description`, `priority`, `label`, `status`, `task_date`) VALUES
('Buy groceries',        'Milk, eggs, bread, and vegetables',     'p2', 'Personal', 'pending',   CURDATE()),
('Finish lab report',    'Complete COMP016 Lab Activity 5',       'p1', 'School',   'pending',   CURDATE()),
('Morning jog',          'Run 3km around the campus oval',        'p3', 'Health',   'completed', CURDATE()),
('Review pull requests', 'Check team PRs on the Taskly repo',     'p2', 'Work',     'pending',   CURDATE()),
('Clean the kitchen',    'Wash dishes and wipe countertops',      'p4', 'Home',     'pending',   CURDATE());
