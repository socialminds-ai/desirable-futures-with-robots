-- Make "admin" a role on facilitators (they already authenticate by email),
-- and retire the separate password-based admins table.

ALTER TABLE facilitators
    ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

DROP TABLE IF EXISTS admins;
