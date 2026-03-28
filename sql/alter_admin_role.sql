-- Run once if your `admin` table has no `role` column (legacy installs).
-- Use your actual database name in phpMyAdmin (e.g. royalfam_sql).

ALTER TABLE `admin`
  ADD COLUMN `role` varchar(32) NOT NULL DEFAULT 'full'
  COMMENT 'full, coordinator, teacher, records_officer, communications'
  AFTER `password`;
