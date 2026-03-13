-- ИИС ППР: Интеллектуальная система поддержки принятия решений
-- Подбор и распределение на программы стажировок и практик
-- СУБД: MySQL (phpMyAdmin)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- База данных
CREATE DATABASE IF NOT EXISTS `taranczov1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taranczov1`;

-- Роли пользователей
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL COMMENT 'student, hr, manager, admin',
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Пользователи
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Связь пользователь-роль (многие-ко-многим)
CREATE TABLE `user_roles` (
  `user_id` int unsigned NOT NULL,
  `role_id` int unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник навыков/компетенций
CREATE TABLE `skills` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Справочник языков
CREATE TABLE `languages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Кандидаты (анкеты студентов)
CREATE TABLE `candidates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `university` varchar(255) DEFAULT NULL,
  `specialty` varchar(255) DEFAULT NULL,
  `course` tinyint unsigned DEFAULT NULL COMMENT 'курс обучения',
  `graduation_year` year DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL COMMENT 'средний балл',
  `experience_years` decimal(3,1) DEFAULT 0,
  `projects_count` int unsigned DEFAULT 0,
  `motivation_text` text,
  `experience_text` text,
  `interests_text` text,
  `city` varchar(100) DEFAULT NULL,
  `work_format` varchar(50) DEFAULT NULL COMMENT 'очно, удаленно, гибрид',
  `available_from` date DEFAULT NULL,
  `available_to` date DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft' COMMENT 'draft, submitted, in_review, assigned, rejected',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  CONSTRAINT `candidates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Навыки кандидата (многие-ко-многим)
CREATE TABLE `candidate_skills` (
  `candidate_id` int unsigned NOT NULL,
  `skill_id` int unsigned NOT NULL,
  `level` varchar(20) DEFAULT NULL COMMENT 'базовый, средний, продвинутый',
  PRIMARY KEY (`candidate_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `candidate_skills_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidate_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Языки кандидата (многие-ко-многим)
CREATE TABLE `candidate_languages` (
  `candidate_id` int unsigned NOT NULL,
  `language_id` int unsigned NOT NULL,
  `level` varchar(20) DEFAULT NULL COMMENT 'A1, A2, B1, B2, C1, C2',
  PRIMARY KEY (`candidate_id`,`language_id`),
  KEY `language_id` (`language_id`),
  CONSTRAINT `candidate_languages_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidate_languages_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Программы стажировок
CREATE TABLE `programs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL COMMENT 'направление: аналитика, разработка, маркетинг и т.д.',
  `description` text,
  `city` varchar(100) DEFAULT NULL,
  `work_format` varchar(50) DEFAULT NULL,
  `duration_weeks` int unsigned DEFAULT NULL,
  `min_course` tinyint unsigned DEFAULT NULL,
  `min_gpa` decimal(3,2) DEFAULT NULL,
  `min_experience_years` decimal(3,1) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `direction` (`direction`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Требования программы к навыкам
CREATE TABLE `program_required_skills` (
  `program_id` int unsigned NOT NULL,
  `skill_id` int unsigned NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `weight` decimal(3,2) DEFAULT 1.00 COMMENT 'вес критерия при скоринге',
  PRIMARY KEY (`program_id`,`skill_id`),
  KEY `skill_id` (`skill_id`),
  CONSTRAINT `program_skills_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `program_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Квоты программ
CREATE TABLE `program_quota` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int unsigned NOT NULL,
  `total_places` int unsigned NOT NULL,
  `occupied_places` int unsigned NOT NULL DEFAULT 0,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `program_quota_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Запуски интеллектуального анализа (скоринг)
CREATE TABLE `scoring_runs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int unsigned NOT NULL,
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `finished_at` datetime DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'running' COMMENT 'running, completed, failed',
  `initiated_by` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  KEY `initiated_by` (`initiated_by`),
  CONSTRAINT `scoring_runs_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scoring_runs_user` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Результаты скоринга (оценка кандидат-программа)
CREATE TABLE `scores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `scoring_run_id` int unsigned NOT NULL,
  `candidate_id` int unsigned NOT NULL,
  `program_id` int unsigned NOT NULL,
  `score` decimal(5,2) NOT NULL COMMENT 'интегральная оценка соответствия 0-100',
  `admitted` tinyint(1) NOT NULL COMMENT 'прошел минимальные требования',
  `explanation` text COMMENT 'объяснение факторов оценки',
  `rank_position` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `run_candidate_program` (`scoring_run_id`,`candidate_id`,`program_id`),
  KEY `candidate_id` (`candidate_id`),
  KEY `program_id` (`program_id`),
  KEY `score` (`score`),
  CONSTRAINT `scores_run` FOREIGN KEY (`scoring_run_id`) REFERENCES `scoring_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scores_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Распределение (итоговое решение)
CREATE TABLE `assignments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `candidate_id` int unsigned NOT NULL,
  `program_id` int unsigned NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'proposed' COMMENT 'proposed, approved, rejected',
  `decided_by` int unsigned DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `comment` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `candidate_program` (`candidate_id`,`program_id`),
  KEY `program_id` (`program_id`),
  KEY `decided_by` (`decided_by`),
  CONSTRAINT `assignments_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignments_decided_by` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Уведомления
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text,
  `type` varchar(50) DEFAULT 'info',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  CONSTRAINT `notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Журнал аудита
CREATE TABLE `audit_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int unsigned DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `audit_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Начальные данные: роли
INSERT INTO `roles` (`code`, `name`) VALUES
('student', 'Студент'),
('hr', 'HR-оператор'),
('manager', 'Руководитель программы'),
('admin', 'Администратор');

-- Начальные данные: языки
INSERT INTO `languages` (`name`, `code`) VALUES
('Русский', 'ru'),
('Казахский', 'kk'),
('Английский', 'en');

-- Начальные данные: навыки (примеры)
INSERT INTO `skills` (`name`, `category`) VALUES
('Python', 'Программирование'),
('PHP', 'Программирование'),
('SQL', 'Базы данных'),
('Анализ данных', 'Аналитика'),
('Машинное обучение', 'Аналитика'),
('Коммуникации', 'Soft skills'),
('Управление проектами', 'Soft skills');

-- Тестовый администратор. После импорта выполните: php update_admin_password.php
INSERT INTO `users` (`email`, `password_hash`, `full_name`) VALUES
('admin@iis.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Администратор системы');

INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 4);

SET FOREIGN_KEY_CHECKS = 1;
