
<?php
require_once "db_students.php";

$message = "";
$messageType = "success";

if (isset($_GET["message"])) {
    $messages = [
        "added" => "Student added successfully!",
        "updated" => "Student updated successfully!",
        "deleted" => "Student status changed to inactive successfully!",
        "delete_error" => "Unable to delete student. Student may already be inactive or not found."
    ];

    if (isset($messages[$_GET["message"]])) {
        $message = $messages[$_GET["message"]];
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_student"])) {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $course = trim($_POST["course"] ?? "");

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $course === "") {
        $message = "Please enter a valid name, email and course.";
        $messageType = "error";
    } else {
        // Check whether the email already exists
        $checkStmt = $conn->prepare(
            "SELECT student_id FROM students WHERE email = ? LIMIT 1"
        );

        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $message = "This email is already registered. Duplicate student cannot be added.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO students (name, email, course) VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $name, $email, $course);

            if ($stmt->execute()) {
                $stmt->close();
                $checkStmt->close();

                header("Location: students.php?message=added");
                exit;
            } else {
                $message = "Error adding student.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $checkStmt->close();
    }
}

$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");

$coursesResult = $conn->query(
    "SELECT DISTINCT course FROM students ORDER BY course"
);

$courses = [];

while ($courseRow = $coursesResult->fetch_assoc()) {
    $courses[] = $courseRow["course"];
}

$sql = "SELECT student_id, name, email, course, status
        FROM students
        WHERE status = 'active'";

$types = "";
$params = [];

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR course LIKE ?)";
    $term = "%" . $search . "%";

    $types .= "sss";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($courseFilter !== "") {
    $sql .= " AND course = ?";
    $types .= "s";
    $params[] = $courseFilter;
}

$sql .= " ORDER BY student_id DESC";

$stmt = $conn->prepare($sql);

if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Management - Student Hub</title>

<style>
header img.logo {
    width: 180px;
    height: 180px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid rgb(13, 0, 0);
    padding: 6px;
    background: white;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    transition: 0.4s;
}

header img.logo:hover {
    transform: scale(1.08);
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #F0F7FA;
    color: #17324D;
}

header {
    background: linear-gradient(110deg, #002147, #4DC8E0);
    color: white;
    padding: 22px;
    text-align: center;
}

header h1 {
    margin: 0 0 8px;
}

.container {
    width: 94%;
    max-width: 1200px;
    margin: 25px auto;
}

.card {
    background: white;
    padding: 22px;
    margin-bottom: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px #00000012;
}

h2 {
    margin-top: 0;
}

form .fields {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

input, select {
    width: 100%;
    padding: 11px;
    border: 1px solid #B8C9D5;
    border-radius: 6px;
    font-size: 15px;
    background: white;
}

button, .button {
    display: inline-block;
    border: none;
    border-radius: 6px;
    padding: 11px 15px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
    background: #005F73;
    color: white;
}

button:hover, .button:hover {
    opacity: .85;
}

.green {
    background: #16804A;
}

.red {
    background: #C62828;
}

.gray {
    background: #526777;
}

.message {
    padding: 13px;
    border-radius: 6px;
    margin-bottom: 18px;
}

.success {
    background: #DDF5E5;
    color: #176537;
}

.error {
    background: #FCE4E4;
    color: #A31F1F;
}

.table-wrap {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 650px;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #DCE5EB;
    text-align: left;
}

th {
    background: #E4F2F7;
}

tr:hover td {
    background: #F8FBFC;
}

.actions {
    display: flex;
    gap: 7px;
    align-items: center;
}

.actions form {
    margin: 0;
}

@media (max-width: 600px) {
    .container {
        width: 96%;
    }

    .card {
        padding: 14px;
    }
}

footer {
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 20px;
    margin-top: 40px;
}

footer p {
    margin: 5px 0;
}
</style>
</head>

<body>

<header>
    <img
        class="logo"
        src="logo.jpeg"
        alt="Student Hub Logo">

    <h1>Student Management System</h1>
    <p>Student Hub Portal</p>
</header>

<div class="container">

<?php if ($message !== ""): ?>
    <div class="message <?= $messageType === "error" ? "error" : "success" ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<section class="card">
    <h2>Add New Student</h2>

    <form method="POST" action="students.php">
        <div class="fields">
            <input
                type="text"
                name="name"
                placeholder="Student Name"
                required
                maxlength="100">

            <input
                type="email"
                name="email"
                placeholder="Email Address"
                required
                maxlength="150">

            <input
                type="text"
                name="course"
                placeholder="Course"
                required
                maxlength="100">
        </div>

        <p>
            <button type="submit" name="add_student">Add Student</button>
        </p>
    </form>
</section>

<section class="card">
    <h2>Search and Filter Students</h2>

    <form method="GET" action="students.php">
        <div class="fields">
            <input
                type="text"
                name="search"
                placeholder="Search name, email or course"
                value="<?= htmlspecialchars($search) ?>">

            <select name="course">
                <option value="">All Courses</option>

                <?php foreach ($courses as $c): ?>
                    <option
                        value="<?= htmlspecialchars($c) ?>"
                        <?= $courseFilter === $c ? "selected" : "" ?>>
                        <?= htmlspecialchars($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Search / Filter</button>

            <a class="button gray" href="students.php">Clear</a>

            <a class="button green" href="export_students.php">Export CSV</a>
        </div>
    </form>
</section>

<section class="card">
    <h2>Student Records</h2>

    <p>Showing <?= $result->num_rows ?> active student(s).</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($student = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$student["student_id"] ?></td>
                    <td><?= htmlspecialchars($student["name"]) ?></td>
                    <td><?= htmlspecialchars($student["email"]) ?></td>
                    <td><?= htmlspecialchars($student["course"]) ?></td>
                    <td><?= htmlspecialchars($student["status"]) ?></td>

                    <td>
                        <div class="actions">
                            <a
                                class="button"
                                href="edit_student.php?id=<?= (int)$student["student_id"] ?>">
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="delete_student.php"
                                onsubmit="return confirm('Are you sure you want to mark this student as inactive?');">

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int)$student["student_id"] ?>">

                                <button class="red" type="submit">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">No student records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

</div>

<footer>
    <p><b>Student Hub</b></p>
    <p>Learn • Explore • Grow</p>
    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
    <p>Designed &amp; Developed by Heni Patel</p>
</footer>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
