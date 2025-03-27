-- Step 1: Create a new table without redundant columns
CREATE TABLE new_volunteers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT,
    password TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    num_people INTEGER DEFAULT 1 -- Retain this if necessary for general group tracking
);

-- Step 2: Copy data from the old table to the new table
INSERT INTO new_volunteers (id, name, email, phone, password, created_at, num_people)
SELECT id, name, email, phone, password, created_at, num_people
FROM volunteers;

-- Step 3: Drop the old table
DROP TABLE volunteers;

-- Step 4: Rename the new table to volunteers
ALTER TABLE new_volunteers RENAME TO volunteers;
