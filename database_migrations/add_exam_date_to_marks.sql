-- Migration to add exam_date column to marks table
ALTER TABLE marks
ADD COLUMN exam_date DATE DEFAULT NULL;
