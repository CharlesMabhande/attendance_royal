-- Staff directory: teachers & ancillary staff (Royal Family Junior School)
-- Run this once in phpMyAdmin on your school database (e.g. royalfam_sql).

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `school_staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(200) NOT NULL,
  `staff_category` enum('teacher','ancillary') NOT NULL DEFAULT 'teacher',
  `job_title` varchar(150) NOT NULL DEFAULT '',
  `role_at_school` varchar(200) NOT NULL DEFAULT '',
  `work_level` varchar(120) NOT NULL DEFAULT '',
  `qualifications` text,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `date_started` date DEFAULT NULL,
  `date_left` date DEFAULT NULL,
  `status` enum('active','left') NOT NULL DEFAULT 'active',
  `notes` text,
  `photo_filename` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`staff_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_certificate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
