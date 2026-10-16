
<?php
require_once "db_students.php";

$message = "";
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $course = trim($_POST["course"] ?? "");

    if (!$id || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $course === "") {
        $message = "Please enter valid student details.";
    } else {
        $check = $conn->prepare(
            "SELECT student_id FROM students WHERE student_id = ? AND status = 'active'"
        );
        $check->bind_param("i", $id);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if (!$exists) {
            $message = "Student not found or already inactive.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE students SET name = ?, email = ?, course = ? WHERE student_id = ? AND status = 'active'"
            );
            $stmt->bind_param("sssi", $name, $email, $course, $id);

            if ($stmt->execute() && $stmt->affected_rows >= 0) {
                $stmt->close();
                header("Location: students.php?message=updated");
                exit;
            } else {
                $message = "Error updating student.";
            }
            $stmt->close();
        }
    }
}

if (!$id) {
    die("Invalid student ID.");
}

$stmt = $conn->prepare(
    "SELECT student_id, name, email, course FROM students WHERE student_id = ? AND status = 'active'"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found or already inactive.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Student</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #F0F7FA;
    color: #17324D;
    margin: 0;
    padding: 25px;
}
.container {
    max-width: 600px;
    margin: 35px auto;
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #00000012;
}
input {
    width: 100%;
    padding: 12px;
    margin: 8px 0 16px;
    box-sizing: border-box;
    border: 1px solid #B8C9D5;
    border-radius: 6px;
}
button, a {
    display: inline-block;
    padding: 11px 16px;
    border: none;
    border-radius: 6px;
    background: #005F73;
    color: white;
    text-decoration: none;
    cursor: pointer;
}
.error {
    padding: 12px;
    background: #FCE4E4;
    color: #A31F1F;
    border-radius: 6px;
}
</style>
</head>
<body>
<div class="container">
    <h2>Edit Student Details</h2>

    <?php if ($message !== ""): ?>
        <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id" value="<?= (int)$student["student_id"] ?>">

        <label>Student Name</label>
        <input type="text" name="name" maxlength="100" required
               value="<?= htmlspecialchars($student["name"]) ?>">

        <label>Email Address</label>
        <input type="email" name="email" maxlength="150" required
               value="<?= htmlspecialchars($student["email"]) ?>">

        <label>Course</label>
        <input type="text" name="course" maxlength="100" required
               value="<?= htmlspecialchars($student["course"]) ?>">

        <button type="submit">Update Student</button>
        <a href="students.php">Back</a>
    </form>
</div>
</body>
</html>
<?php $conn->close(); ?>
