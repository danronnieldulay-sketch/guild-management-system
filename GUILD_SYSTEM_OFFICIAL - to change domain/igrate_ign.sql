-- Migration: Move IGN from users table to members and applications tables
-- This allows users to have multiple IGNs (one per guild)

-- Step 1: Add IGN column to members table
ALTER TABLE members ADD COLUMN ign VARCHAR(50) NULL AFTER role;

-- Step 2: Add IGN column to applications table
ALTER TABLE applications ADD COLUMN ign VARCHAR(50) NULL AFTER message;

-- Step 3: Remove IGN column from users table
ALTER TABLE users DROP COLUMN ign;

-- Note: Existing members' IGN values should be populated manually or through PHP migration script
-- New registrations will not have an IGN in the users table
