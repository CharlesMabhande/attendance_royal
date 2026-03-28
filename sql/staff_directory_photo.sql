-- Add staff profile photo column (run once if you already created school_staff without it)
SET NAMES utf8mb4;

ALTER TABLE `school_staff`
  ADD COLUMN `photo_filename` varchar(255) DEFAULT NULL AFTER `notes`;
