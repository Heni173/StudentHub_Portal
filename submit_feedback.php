<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Feedback.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$feedback = trim($_POST["feedback"] ?? "");

if (
    $name === "" ||
    $email === "" ||
    $feedback === "" ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($name) > 100 ||
    strlen($email) > 150 ||
    strlen($feedback) > 5000
) {
    header("Location: Feedback.html?status=error");
    exit;
}

$conn = new mysqli("localhost", "root", "", "studenthub");

if ($conn->connect_error) {
    error_log("Feedback database connection failed: " . $conn->connect_error);
    header("Location: Feedback.html?status=error");
    exit;
}

$conn->set_charset("utf8mb4");

$createTable = "CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    feedback TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($createTable)) {
    error_log("Feedback table creation failed: " . $conn->error);
    $conn->close();
    header("Location: Feedback.html?status=error");
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO feedbacks (name, email, feedback) VALUES (?, ?, ?)"
);

if (!$stmt) {
    error_log("Feedback statement preparation failed: " . $conn->error);
    $conn->close();
    header("Location: Feedback.html?status=error");
    exit;
}

$stmt->bind_param("sss", $name, $email, $feedback);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: Feedback.html?status=success");
    exit;
}

error_log("Feedback insertion failed: " . $stmt->error);
$stmt->close();
$conn->close();

header("Location: Feedback.html?status=error");
exit;
?>