-- Add teacher_id column if it doesn't exist
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS teacher_id VARCHAR(10) UNIQUE;

-- Add created_at column if it doesn't exist
ALTER TABLE teachers ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP; 