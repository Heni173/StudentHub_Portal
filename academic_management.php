<?php
// Development only. Production ma '0' kari do.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "timeout.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}

require_once "db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Please check db.php.");
}

$conn->set_charset("utf8mb4");

if (empty($_SESSION["academic_csrf"])) {
    $_SESSION["academic_csrf"] = bin2hex(random_bytes(32));
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$message = "";
$messageType = "success";

$studentId = (int)($_GET["student_id"] ?? $_POST["student_id"] ?? 0);
$semester = (int)($_GET["semester"] ?? $_POST["semester"] ?? 1);

if ($semester < 1 || $semester > 12) {
    $semester = 1;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["academic_csrf"], $token)) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";
    } else {
        $action = $_POST["action"] ?? "";

        $check = $conn->prepare(
            "SELECT id FROM users
             WHERE id = ? AND LOWER(TRIM(role)) = 'student'
             LIMIT 1"
        );

        if (!$check) {
            $message = "Database Error: " . $conn->error;
            $messageType = "error";
        } else {
            $check->bind_param("i", $studentId);
            $check->execute();
            $studentExists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$studentExists) {
                $message = "Please select a valid student.";
                $messageType = "error";

            } elseif ($action === "save_academic") {

                $cgpaInput = trim($_POST["cgpa"] ?? "");
                $attendanceInput = trim($_POST["attendance"] ?? "");

                if (
                    ($cgpaInput !== "" &&
                        (!is_numeric($cgpaInput) ||
                         (float)$cgpaInput < 0 ||
                         (float)$cgpaInput > 10)) ||
                    ($attendanceInput !== "" &&
                        (!is_numeric($attendanceInput) ||
                         (float)$attendanceInput < 0 ||
                         (float)$attendanceInput > 100))
                ) {
                    $message = "CGPA must be 0-10 and attendance must be 0-100.";
                    $messageType = "error";

                } elseif ($cgpaInput === "" && $attendanceInput === "") {

                    $message = "Enter CGPA or attendance before saving.";
                    $messageType = "error";

                } else {

                    $cgpaValue = ($cgpaInput === "") ? null : (float)$cgpaInput;
                    $attendanceValue = ($attendanceInput === "") ? null : (float)$attendanceInput;

                    $stmt = $conn->prepare(
                        "INSERT INTO academic_details (user_id, cgpa, attendance)
                         VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         cgpa = VALUES(cgpa),
                         attendance = VALUES(attendance)"
                    );

                    if (!$stmt) {
                        $message = "Database Error: " . $conn->error;
                        $messageType = "error";
                    } else {
                        $stmt->bind_param("idd", $studentId, $cgpaValue, $attendanceValue);

                        if ($stmt->execute()) {
                            $message = "Academic details saved successfully.";
                            $messageType = "success";
                        } else {
                            $message = "Database Error: " . $stmt->error;
                            $messageType = "error";
                        }

                        $stmt->close();
                    }
                }

            } elseif ($action === "save_marks") {

                $subject = trim($_POST["subject_name"] ?? "");
                $obtainedInput = trim($_POST["marks_obtained"] ?? "");
                $totalInput = trim($_POST["total_marks"] ?? "");

                if (
                    $subject === "" ||
                    strlen($subject) > 150 ||
                    $obtainedInput === "" ||
                    $totalInput === "" ||
                    !is_numeric($obtainedInput) ||
                    !is_numeric($totalInput) ||
                    (float)$obtainedInput < 0 ||
                    (float)$totalInput <= 0 ||
                    (float)$obtainedInput > (float)$totalInput
                ) {
                    $message = "Enter a valid subject name and marks.";
                    $messageType = "error";
                } else {
                    $obtained = (float)$obtainedInput;
                    $total = (float)$totalInput;

                    $stmt = $conn->prepare(
                        "INSERT INTO subject_marks
                         (user_id, semester, subject_name, marks_obtained, total_marks)
                         VALUES (?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                         marks_obtained = VALUES(marks_obtained),
                         total_marks = VALUES(total_marks)"
                    );

                    if (!$stmt) {
                        $message = "Database Error: " . $conn->error;
                        $messageType = "error";
                    } else {
                        $stmt->bind_param(
                            "iisdd",
                            $studentId,
                            $semester,
                            $subject,
                            $obtained,
                            $total
                        );

                        if ($stmt->execute()) {
                            $message = "Subject marks saved successfully.";
                            $messageType = "success";
                        } else {
                            $message = "Database Error: " . $stmt->error;
                            $messageType = "error";
                        }

                        $stmt->close();
                    }
                }

            } elseif ($action === "delete_marks") {

                $markId = (int)($_POST["mark_id"] ?? 0);

                $stmt = $conn->prepare(
                    "DELETE FROM subject_marks
                     WHERE id = ? AND user_id = ? AND semester = ?"
                );

                if (!$stmt) {
                    $message = "Database Error: " . $conn->error;
                    $messageType = "error";
                } else {
                    $stmt->bind_param(
                        "iii",
                        $markId,
                        $studentId,
                        $semester
                    );

                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        $message = "Subject marks deleted successfully.";
                        $messageType = "success";
                    } else {
                        $message = "Unable to delete this subject record.";
                        $messageType = "error";
                    }

                    $stmt->close();
                }
            }
        }
    }
}

$students = [];

$result = $conn->query(
    "SELECT id, full_name, username, enrollment
     FROM users
     WHERE LOWER(TRIM(role)) = 'student'
     ORDER BY full_name"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $result->free();
}

$currentStudent = null;
$cgpa = "";
$attendance = "";
$subjects = [];

if ($studentId > 0) {
    $stmt = $conn->prepare(
        "SELECT id, full_name, username, enrollment
         FROM users
         WHERE id = ? AND LOWER(TRIM(role)) = 'student'
         LIMIT 1"
    );

    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $currentStudent = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if ($currentStudent) {
        $stmt = $conn->prepare(
            "SELECT cgpa, attendance
             FROM academic_details
             WHERE user_id = ? LIMIT 1"
        );

        if ($stmt) {
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $academic = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($academic) {
                $cgpa = $academic["cgpa"] ?? "";
                $attendance = $academic["attendance"] ?? "";
            }
        }

        $stmt = $conn->prepare(
            "SELECT id, subject_name, marks_obtained, total_marks
             FROM subject_marks
             WHERE user_id = ? AND semester = ?
             ORDER BY subject_name"
        );

        if ($stmt) {
            $stmt->bind_param("ii", $studentId, $semester);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                $subjects[] = $row;
            }

            $stmt->close();
        }
    } else {
        $studentId = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Management - Student Hub</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #F8FAFC, #EAF4F8);
    color: #2E2E2E;
    min-height: 100vh;
    transition: background .4s, color .4s;
}

#notification {
    background: #005F73;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: bold;
}

#closeNotification {
    margin-left: 15px;
    border: none;
    padding: 5px 10px;
    border-radius: 15px;
    cursor: pointer;
}

header {
    position: relative;
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 35px 20px;
    box-shadow: 0 8px 20px rgba(0,0,0,.18);
}

header img.logo {
    width: 180px;
    height: 180px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid #0d0000;
    padding: 6px;
    background: white;
    box-shadow: 0 10px 25px rgba(0,0,0,.25);
    transition: .4s;
}

header img.logo:hover {
    transform: scale(1.08);
}

header h1 {
    margin: 18px 0;
    font-size: 38px;
    letter-spacing: 2px;
}

#themeButton {
    position: absolute;
    top: 18px;
    right: 25px;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    background: white;
    color: #002147;
    font-size: 20px;
    cursor: pointer;
}

#menuButton {
    display: none;
    background: white;
    color: #002147;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
}

nav {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 15px;
}

nav a {
    text-decoration: none;
    color: white;
    background: rgba(255,255,255,.15);
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    transition: .3s;
}

nav a:hover,
nav a.active {
    background: white;
    color: #002147;
    transform: translateY(-3px);
}

main {
    width: 90%;
    max-width: 1500px;
    margin: auto;
    padding: 35px 0 45px;
}

.page-heading {
    text-align: center;
    color: #002147;
    font-size: 30px;
    margin-bottom: 10px;
}

.welcome-text {
    text-align: center;
    font-size: 17px;
    margin-bottom: 30px;
    line-height: 1.6;
}

.section-title {
    text-align: center;
    color: #002147;
    font-size: 28px;
    margin: 35px 0 22px;
}

.feature-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 22px;
}

.feature-card,
.form-card,
.table-card {
    background: white;
    border-top: 5px solid #005F73;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
    margin-bottom: 25px;
    min-width: 0;
}

.feature-card h3,
.form-card h3,
.table-card h3 {
    font-size: 22px;
    color: #002147;
    margin-bottom: 16px;
}

.feature-card p {
    line-height: 1.7;
    margin-bottom: 12px;
}

.feature-icon {
    font-size: 30px;
    margin-bottom: 12px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0 20px;
}

label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
}

input, select {
    width: 100%;
    padding: 12px;
    border: 1px solid #bdcbd5;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
    background: white;
    color: #222;
}

input:focus, select:focus {
    outline: 2px solid #4dc8e0;
    border-color: #005F73;
}

.action-link, button.primary-button {
    display: inline-block;
    border: none;
    text-decoration: none;
    color: white;
    background: linear-gradient(135deg, #002147, #005F73);
    padding: 11px 22px;
    border-radius: 25px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: .3s;
}

.action-link:hover, button.primary-button:hover {
    transform: scale(1.03);
    color: white;
}

button.delete-button {
    background: #b42318;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 14px;
    cursor: pointer;
}

.message {
    padding: 14px 18px;
    border-radius: 8px;
    margin: 15px 0 25px;
    font-weight: 600;
}

.message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.student-info {
    background: #EAF4F8;
    border-left: 5px solid #005F73;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    line-height: 1.9;
    overflow-wrap: anywhere;
}

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
    background: white;
    color: #2E2E2E;
}

thead th {
    background: linear-gradient(135deg, #002147, #005F73);
    color: white;
    padding: 15px;
    text-align: left;
    white-space: nowrap;
}

tbody td {
    padding: 13px 15px;
    border: 1px solid #d6e3eb;
    vertical-align: middle;
}

tbody tr:nth-child(even) {
    background: #EAF4F8;
}

tbody tr:hover {
    background: #d7f0f5;
}

.inline-form {
    display: inline;
}

.quick-notice {
    background: white;
    border-left: 5px solid #005F73;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
    line-height: 1.7;
}

.quick-notice h3 {
    color: #002147;
    margin-bottom: 10px;
}

footer {
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 22px;
    margin-top: 20px;
    line-height: 1.8;
}

/* Dark Mode */

body.dark {
    background: linear-gradient(135deg, #121820, #202a35);
    color: #f1f5f9;
}

body.dark .feature-card,
body.dark .form-card,
body.dark .table-card,
body.dark .quick-notice {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .feature-card h3,
body.dark .form-card h3,
body.dark .table-card h3,
body.dark .section-title,
body.dark .page-heading,
body.dark .quick-notice h3 {
    color: #4dc8e0;
}

body.dark #themeButton {
    background: #222;
    color: #4dc8e0;
}

body.dark input,
body.dark select {
    background: #333e4b;
    color: white;
    border-color: #465363;
}

body.dark .student-info {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .table-responsive,
body.dark table {
    background: #222b35;
    color: #f1f5f9;
}

body.dark tbody tr:nth-child(even),
body.dark tbody tr:nth-child(odd) {
    background: #222b35;
    color: #f1f5f9;
}

body.dark tbody tr:hover {
    background: #333e4b;
}

body.dark tbody td {
    border-color: #465363;
}

/* Tablet */

@media(max-width: 1100px) {
    nav {
        gap: 10px;
    }

    nav a {
        padding: 9px 14px;
    }
}

/* Mobile */

@media(max-width: 768px) {
    header {
        padding: 25px 15px;
    }

    header img.logo {
        width: 140px;
        height: 140px;
    }

    header h1 {
        font-size: 30px;
    }

    #menuButton {
        display: inline-block;
        margin-top: 5px;
    }

    nav {
        display: none;
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        margin-top: 15px;
        width: 100%;
    }

    nav.show {
        display: flex;
    }

    nav a {
        width: 100%;
        max-width: 350px;
        margin: 0 auto;
        text-align: center;
    }

    main {
        width: 95%;
        padding: 25px 0 35px;
    }

    #themeButton {
        top: 15px;
        right: 15px;
        width: 38px;
        height: 38px;
    }

    .feature-grid,
    .form-grid {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 25px;
    }
}

@media(max-width: 520px) {
    header img.logo {
        width: 110px;
        height: 110px;
    }

    header h1 {
        font-size: 25px;
        letter-spacing: 1px;
    }

    .page-heading {
        font-size: 24px;
    }

    .section-title {
        font-size: 23px;
    }

    .welcome-text {
        font-size: 15px;
    }

    .feature-card,
    .form-card,
    .table-card {
        padding: 18px;
    }

    footer {
        font-size: 14px;
    }
}

button:focus-visible,
a:focus-visible {
    outline: 3px solid #4dc8e0;
    outline-offset: 3px;
}
</style>
</head>

<body>

<div id="notification">
    🔔 New updates are available!
    <button id="closeNotification" type="button"
            aria-label="Close notification">✕</button>
</div>

<header>
    <button id="themeButton" type="button"
            title="Change theme"
            aria-label="Switch to dark mode">🌙</button>

    <img class="logo" src="logo.jpeg" alt="Student Hub Logo">

    <h1>STUDENT HUB</h1>

    <button id="menuButton" type="button"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="navigation">☰ Menu</button>

    <nav id="navigation">
        <a href="Admin.php" class="active">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="academic_management.php">Academics</a>
        <a href="admin_events.php">Events</a>
        <a href="assignments.php">Assignments</a>
        <a href="announcements.php">Notices</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2 class="page-heading">🎓 Academic Management</h2>

    <p class="welcome-text">
        Manage student CGPA, attendance and semester-wise subject marks
        from one place.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?= h($messageType) ?>" role="status">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <section class="form-card">
        <h3>👨‍🎓 Select Student</h3>

        <form method="get" action="academic_management.php">
            <label for="student_id">Student Name / Enrollment</label>
            <select id="student_id" name="student_id" required>
                <option value="">-- Select Student --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int)$student["id"] ?>"
                        <?= $studentId === (int)$student["id"] ? "selected" : "" ?>>
                        <?= h($student["full_name"] ?: $student["username"]) ?>
                        <?= !empty($student["enrollment"]) ? " - " . h($student["enrollment"]) : "" ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="semester">Semester</label>
            <select id="semester" name="semester">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= $semester === $i ? "selected" : "" ?>>
                        Semester <?= $i ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="primary-button">
                Load Student Details
            </button>
        </form>

        <?php if (count($students) === 0): ?>
            <p>No student accounts found in the users table.</p>
        <?php endif; ?>
    </section>

    <?php if ($currentStudent): ?>

        <div class="student-info">
            <strong>Student:</strong>
            <?= h($currentStudent["full_name"] ?: $currentStudent["username"]) ?><br>

            <strong>Username:</strong>
            <?= h($currentStudent["username"]) ?><br>

            <strong>Enrollment:</strong>
            <?= h($currentStudent["enrollment"] ?: "Not available") ?><br>

            <strong>Selected Semester:</strong>
            <?= $semester ?>
        </div>

        <section class="form-card">
            <h3>📊 CGPA &amp; Attendance</h3>

            <form method="post" action="academic_management.php">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION["academic_csrf"]) ?>">
                <input type="hidden" name="action" value="save_academic">
                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                <input type="hidden" name="semester" value="<?= $semester ?>">

                <div class="form-grid">
                    <div>
                        <label for="cgpa">CGPA (0 to 10)</label>
                        <input type="number" id="cgpa" name="cgpa"
                               min="0" max="10" step="0.01"
                               value="<?= h($cgpa) ?>" placeholder="e.g. 8.50">
                    </div>

                    <div>
                        <label for="attendance">Attendance (%)</label>
                        <input type="number" id="attendance" name="attendance"
                               min="0" max="100" step="0.01"
                               value="<?= h($attendance) ?>" placeholder="e.g. 85">
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    Save Academic Details
                </button>
            </form>
        </section>

        <section class="form-card">
            <h3>📝 Add / Update Subject Marks</h3>

            <p style="line-height:1.7; margin-bottom:18px;">
                Enter each subject separately. To update existing marks,
                use the same subject name and semester.
            </p>

            <form method="post" action="academic_management.php">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION["academic_csrf"]) ?>">
                <input type="hidden" name="action" value="save_marks">
                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                <input type="hidden" name="semester" value="<?= $semester ?>">

                <label for="subject_name">Subject Name</label>
                <input type="text" id="subject_name" name="subject_name"
                       maxlength="150" required
                       placeholder="e.g. Programming in C">

                <div class="form-grid">
                    <div>
                        <label for="marks_obtained">Marks Obtained</label>
                        <input type="number" id="marks_obtained"
                               name="marks_obtained" min="0"
                               step="0.01" required placeholder="e.g. 85">
                    </div>

                    <div>
                        <label for="total_marks">Total Marks</label>
                        <input type="number" id="total_marks"
                               name="total_marks" min="0.01"
                               step="0.01" required placeholder="e.g. 100">
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    Save Subject Marks
                </button>
            </form>
        </section>

        <section class="table-card">
            <h3>📚 Semester <?= $semester ?> Subject Marks</h3>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Marks Obtained</th>
                            <th>Total Marks</th>
                            <th>Percentage</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($subjects) > 0): ?>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td><?= h($subject["subject_name"]) ?></td>
                                <td><?= h($subject["marks_obtained"]) ?></td>
                                <td><?= h($subject["total_marks"]) ?></td>
                                <td>
                                    <?php
                                    $percentage = ((float)$subject["marks_obtained"] /
                                        (float)$subject["total_marks"]) * 100;
                                    echo number_format($percentage, 2) . "%";
                                    ?>
                                </td>
                                <td>
                                    <form method="post"
                                          action="academic_management.php"
                                          class="inline-form"
                                          onsubmit="return confirm('Delete these subject marks?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($_SESSION["academic_csrf"]) ?>">
                                        <input type="hidden" name="action" value="delete_marks">
                                        <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                        <input type="hidden" name="semester" value="<?= $semester ?>">
                                        <input type="hidden" name="mark_id" value="<?= (int)$subject["id"] ?>">

                                        <button type="submit" class="delete-button">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">
                                No subject marks added for this semester.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

    <?php endif; ?>

    <section class="quick-notice">
        <h3>📌 Important Notice</h3>
        <p>
            Verify student details and marks before saving. Academic
            information is linked to the selected student's account.
        </p>
    </section>
</main>

<footer>
    <p><b>Student Hub</b></p>
    <p>Learn • Explore • Grow</p>
    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
    <p>Designed &amp; developed by Heni Patel</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const notification = document.getElementById("notification");
    const closeNotification = document.getElementById("closeNotification");
    const themeButton = document.getElementById("themeButton");
    const menuButton = document.getElementById("menuButton");
    const navigation = document.getElementById("navigation");

    let savedTheme = "light";

    try {
        savedTheme = localStorage.getItem("theme") || "light";
    } catch (error) {}

    function applyTheme(theme) {
        const dark = theme === "dark";

        document.body.classList.toggle("dark", dark);
        themeButton.textContent = dark ? "☀️" : "🌙";
        themeButton.setAttribute(
            "aria-label",
            dark ? "Switch to light mode" : "Switch to dark mode"
        );
        themeButton.title = dark ? "Switch to light mode" : "Switch to dark mode";
    }

    applyTheme(savedTheme);

    themeButton.addEventListener("click", function () {
        const theme = document.body.classList.contains("dark")
            ? "light"
            : "dark";

        applyTheme(theme);

        try {
            localStorage.setItem("theme", theme);
        } catch (error) {}
    });

    try {
        if (localStorage.getItem("notificationClosed") === "true") {
            notification.style.display = "none";
        }
    } catch (error) {}

    closeNotification.addEventListener("click", function () {
        notification.style.display = "none";

        try {
            localStorage.setItem("notificationClosed", "true");
        } catch (error) {}
    });

    menuButton.addEventListener("click", function () {
        const open = navigation.classList.toggle("show");

        menuButton.setAttribute("aria-expanded", String(open));
        menuButton.setAttribute(
            "aria-label",
            open ? "Close navigation menu" : "Open navigation menu"
        );
        menuButton.textContent = open ? "✕ Close Menu" : "☰ Menu";
    });

    navigation.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navigation.classList.remove("show");
            menuButton.setAttribute("aria-expanded", "false");
            menuButton.setAttribute("aria-label", "Open navigation menu");
            menuButton.textContent = "☰ Menu";
        });
    });
});
</script>

</body>
</html>