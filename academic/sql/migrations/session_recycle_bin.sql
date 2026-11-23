-- Run against the existing academic_tutoring_web_business database in MariaDB.
-- Earlier versions recorded edits but did not record creation timestamps.
ALTER TABLE tutors
    ADD COLUMN IF NOT EXISTS date_added DATETIME NULL DEFAULT NULL;

ALTER TABLE sessions
    ADD COLUMN IF NOT EXISTS date_added DATETIME NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS deleted_by INT NULL,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL;

-- Set the default for future inserts after adding the columns, so existing
-- records with unknown creation times do not get the migration timestamp.
ALTER TABLE tutors ALTER COLUMN date_added SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE sessions ALTER COLUMN date_added SET DEFAULT CURRENT_TIMESTAMP;

-- Fresh installs may already have this relationship under a generated name.
SET @add_deleted_by_fk = IF(EXISTS (
    SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sessions'
      AND COLUMN_NAME = 'deleted_by' AND REFERENCED_TABLE_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME = 'users' AND REFERENCED_COLUMN_NAME = 'user_id'
), 'DO 0', 'ALTER TABLE sessions ADD FOREIGN KEY (deleted_by) REFERENCES users(user_id) ON DELETE SET NULL');
PREPARE add_deleted_by_fk FROM @add_deleted_by_fk;
EXECUTE add_deleted_by_fk;
DEALLOCATE PREPARE add_deleted_by_fk;
