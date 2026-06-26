-- Add photo_url column to ActivityLog table
ALTER TABLE ActivityLog ADD COLUMN photo_url VARCHAR(500) NULL DEFAULT NULL AFTER logged_on;
