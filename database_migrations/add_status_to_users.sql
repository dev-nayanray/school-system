-- Migration to add status column to users table
ALTER TABLE users
ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1;
