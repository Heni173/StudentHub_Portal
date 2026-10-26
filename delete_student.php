<?php
require_once "db_students.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: students.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: students.php?message=delete_error");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE students
     SET status = 'inactive'
     WHERE student_id = ? AND status = 'active'"
);

if (!$stmt) {
    $conn->close();
    header("Location: students.php?message=delete_error");
    exit;
}

$stmt->bind_param("i", $id);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();

    // Add student delete activity to audit_logs
    require_once "db.php";

    $userId = (int)($_SESSION["user_id"] ?? 0);
    $username = trim($_SESSION["username"] ?? $_SESSION["email"] ?? "admin");
    $action = "DELETE_STUDENT";
    $details = "Student marked inactive (Student ID: " . $id . ")";

    $logStmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, username, action, details, created_at)
         VALUES (?, ?, ?, ?, NOW())"
    );

    if ($logStmt) {
        $logStmt->bind_param(
            "isss",
            $userId,
            $username,
            $action,
            $details
        );

        if (!$logStmt->execute()) {
            error_log("Student audit log insert failed: " . $logStmt->error);
        }

        $logStmt->close();
    } else {
        error_log("Student audit log prepare failed: " . $conn->error);
    }

    $conn->close();

    header("Location: students.php?message=deleted");
    exit;
}

$stmt->close();
$conn->close();

header("Location: students.php?message=delete_error");
exit;
?>