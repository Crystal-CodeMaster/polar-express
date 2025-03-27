-- Populate rides table
INSERT INTO rides (day, time) VALUES
('Saturday', '9:30 AM'),
('Saturday', '11:00 AM'),
('Saturday', '12:30 PM'),
('Saturday', '2:00 PM'),
('Sunday', '9:30 AM'),
('Sunday', '11:00 AM'),
('Sunday', '12:30 PM');

-- Populate volunteer roles table
INSERT INTO volunteer_roles (role_name) VALUES
('Jolly People'),
('Elves'),
('Chefs'),
('Conductors');

-- Populate volunteer slots table
INSERT INTO volunteer_slots (ride_id, role_id, max_volunteers) VALUES
(1, 1, 8), -- Jolly People for Ride 1
(1, 2, 12), -- Elves for Ride 1
(1, 3, 6), -- Chefs for Ride 1
(1, 4, 3), -- Conductors for Ride 1,
(2, 1, 8), -- Jolly People for Ride 2
(2, 2, 12), -- Elves for Ride 2
(2, 3, 6), -- Chefs for Ride 2
(2, 4, 3); -- Conductors for Ride 2

-- Populate ride visibility table
INSERT INTO ride_visibility (ride_id, role_id, visible) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 1),
(2, 1, 1),
(2, 2, 1),
(2, 3, 1),
(2, 4, 1);

-- Populate volunteer signups table with sample data
INSERT INTO volunteer_signups (slot_id, name, email, phone, num_people, notes) VALUES
(1, 'John Doe', 'john.doe@example.com', '123-456-7890', 1, 'Looking forward to helping!'),
(2, 'Jane Smith', 'jane.smith@example.com', NULL, 2, 'Excited for the event!');
