<?php

$conn = new mysqli(
    "localhost",
    "root",
    "",
    "studenthub_events"
);

if ($conn->connect_error) {
    http_response_code(500);
    die("Database connection failed.");
}

$conn->set_charset("utf8mb4");

?>