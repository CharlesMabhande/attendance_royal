-- Run once on existing databases (phpMyAdmin or mysql CLI).
-- Adds public visibility flag: only rows with published = 1 appear on result.php.

ALTER TABLE `user_mark`
  ADD COLUMN `published` tinyint(1) NOT NULL DEFAULT 0
  COMMENT '1 = visible on public result.php; super-admins only may publish'
  AFTER `u_image`;
