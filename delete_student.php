
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
    $conn->close();

    header("Location: students.php?message=deleted");
    exit;
}

$stmt->close();
$conn->close();

header("Location: students.php?message=delete_error");
exit;
?>
