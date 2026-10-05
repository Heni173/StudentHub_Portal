CREATE DATABASE studenthub;

USE studenthub;

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    course VARCHAR(100)
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(100),
    event_date DATE,
    venue VARCHAR(100)
);

CREATE TABLE registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    event_id INT,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    FOREIGN KEY (event_id) REFERENCES events(event_id)
);

INSERT INTO students (name, email, course)
VALUES
('Rahul', 'rahul@gmail.com', 'Computer'),
('Priya', 'priya@gmail.com', 'Computer'),
('Amit', 'amit@gmail.com', 'IT');

INSERT INTO events (event_name, event_date, venue)
VALUES
('Hackathon', '2026-10-10', 'Lab 301'),
('Web Workshop', '2026-10-15', 'Lab 302'),
('AI Seminar', '2026-10-20', 'Auditorium');

INSERT INTO registrations (student_id, event_id)
VALUES
(1, 1),
(1, 2),
(2, 1),
(3, 3);