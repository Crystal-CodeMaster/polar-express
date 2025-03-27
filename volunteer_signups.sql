CREATE TABLE IF NOT EXISTS volunteer_signups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    volunteer_id INTEGER NOT NULL,
    slot_id INTEGER NOT NULL,
    num_people INTEGER NOT NULL DEFAULT 1,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (volunteer_id) REFERENCES volunteers (id),
    FOREIGN KEY (slot_id) REFERENCES volunteer_slots (id)
);