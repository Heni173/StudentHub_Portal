CREATE DATABASE IF NOT EXISTS studenthub_events;

USE studenthub_events;

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    category VARCHAR(50) NOT NULL,
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open'
);