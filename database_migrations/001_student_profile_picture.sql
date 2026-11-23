ALTER TABLE students
    ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(255) NULL AFTER address;
