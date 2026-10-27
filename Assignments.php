<?php
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

if (empty($_SESSION["assignment_csrf"])) {
    $_SESSION["assignment_csrf"] = bin2hex(random_bytes(32));
}

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$statusOptions = [
    "submitted" => "✓ Submitted",
    "review"    => "⏳ Under Review",
    "graded"    => "✓ Graded",
];

$message = "";
$messageType = "success";

$studentId = (int)($_GET["student_id"] ?? $_POST["student_id"] ?? 0);
$editId = (int)($_GET["edit_id"] ?? 0);

/* ---------------- POST: save / delete ---------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["assignment_csrf"], $token)) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";
    } else {
        try {
            $action = $_POST["action"] ?? "";

            $check = $conn->prepare(
                "SELECT id FROM users
                 WHERE id = ? AND LOWER(TRIM(role)) = 'student'
                 LIMIT 1"
            );
            $check->bind_param("i", $studentId);
            $check->execute();
            $studentExists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$studentExists) {
                $message = "Please select a valid student.";
                $messageType = "error";

            } elseif ($action === "save_assignment") {

                $assignmentId = (int)($_POST["assignment_id"] ?? 0);
                $subject = trim($_POST["subject_name"] ?? "");
                $title = trim($_POST["assignment_title"] ?? "");
                $dateInput = trim($_POST["submission_date"] ?? "");
                $status = $_POST["status"] ?? "";
                $grade = trim($_POST["grade"] ?? "");
                $feedback = trim($_POST["feedback"] ?? "");

                $dateObj = DateTime::createFromFormat("Y-m-d", $dateInput);
                $dateValid = $dateObj && $dateObj->format("Y-m-d") === $dateInput;

                if (
                    $subject === "" || mb_strlen($subject) > 150 ||
                    $title === "" || mb_strlen($title) > 200 ||
                    !$dateValid ||
                    !isset($statusOptions[$status]) ||
                    mb_strlen($grade) > 10 ||
                    mb_strlen($feedback) > 1000
                ) {
                    $message = "Please enter valid subject, assignment title, date and status.";
                    $messageType = "error";
                    $editId = $assignmentId;
                } else {
                    // Grade is only kept for graded submissions
                    $gradeValue = ($status === "graded" && $grade !== "") ? $grade : null;
                    $feedbackValue = ($feedback === "") ? null : $feedback;

                    if ($assignmentId > 0) {
                        $exists = $conn->prepare(
                            "SELECT id FROM student_assignments
                             WHERE id = ? AND user_id = ? LIMIT 1"
                        );
                        $exists->bind_param("ii", $assignmentId, $studentId);
                        $exists->execute();
                        $found = $exists->get_result()->num_rows > 0;
                        $exists->close();

                        if (!$found) {
                            $message = "This submission record was not found.";
                            $messageType = "error";
                        } else {
                            $stmt = $conn->prepare(
                                "UPDATE student_assignments
                                 SET subject_name = ?, assignment_title = ?,
                                     submission_date = ?, status = ?,
                                     grade = ?, feedback = ?
                                 WHERE id = ? AND user_id = ?"
                            );
                            $stmt->bind_param(
                                "ssssssii",
                                $subject, $title, $dateInput, $status,
                                $gradeValue, $feedbackValue,
                                $assignmentId, $studentId
                            );
                            $stmt->execute();
                            $stmt->close();

                            $message = "Submission updated successfully.";
                            $messageType = "success";
                            $editId = 0;
                        }
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO student_assignments
                             (user_id, subject_name, assignment_title,
                              submission_date, status, grade, feedback)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            "issssss",
                            $studentId, $subject, $title, $dateInput,
                            $status, $gradeValue, $feedbackValue
                        );
                        $stmt->execute();
                        $stmt->close();

                        $message = "Submission added successfully.";
                        $messageType = "success";
                    }
                }

            } elseif ($action === "delete_assignment") {

                $assignmentId = (int)($_POST["assignment_id"] ?? 0);

                $stmt = $conn->prepare(
                    "DELETE FROM student_assignments
                     WHERE id = ? AND user_id = ?"
                );
                $stmt->bind_param("ii", $assignmentId, $studentId);
                $stmt->execute();
                $deleted = $stmt->affected_rows > 0;
                $stmt->close();

                if ($deleted) {
                    $message = "Submission deleted successfully.";
                    $messageType = "success";
                } else {
                    $message = "Unable to delete this submission.";
                    $messageType = "error";
                }

                $editId = 0;
            }
        } catch (Throwable $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

/* ---------------- Load data ---------------- */

$students = [];
$currentStudent = null;
$assignments = [];

try {
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

    if ($studentId > 0) {
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, enrollment
             FROM users
             WHERE id = ? AND LOWER(TRIM(role)) = 'student'
             LIMIT 1"
        );
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $currentStudent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($currentStudent) {
            $stmt = $conn->prepare(
                "SELECT id, subject_name, assignment_title, submission_date,
                        status, grade, feedback
                 FROM student_assignments
                 WHERE user_id = ?
                 ORDER BY submission_date DESC, id DESC"
            );
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()) {
                $assignments[] = $row;
            }

            $stmt->close();
        } else {
            $studentId = 0;
        }
    }
} catch (Throwable $e) {
    if ($message === "") {
        $message = "Database Error: " . $e->getMessage()
            . " (Create the student_assignments table first.)";
        $messageType = "error";
    }
}

$editRow = null;

if ($editId > 0) {
    foreach ($assignments as $item) {
        if ((int)$item["id"] === $editId) {
            $editRow = $item;
            break;
        }
    }
}

$formSubject = $editRow["subject_name"] ?? "";
$formTitle = $editRow["assignment_title"] ?? "";
$formDate = $editRow["submission_date"] ?? date("Y-m-d");
$formStatus = $editRow["status"] ?? "submitted";
$formGrade = $editRow["grade"] ?? "";
$formFeedback = $editRow["feedback"] ?? "";
$formId = $editRow ? (int)$editRow["id"] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assignments - Student Hub</title>

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

.form-card h3,
.table-card h3 {
    font-size: 22px;
    color: #002147;
    margin-bottom: 16px;
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

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #bdcbd5;
    border-radius: 8px;
    margin-bottom: 18px;
    font-size: 15px;
    background: white;
    color: #222;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

input:focus, select:focus, textarea:focus {
    outline: 2px solid #4dc8e0;
    border-color: #005F73;
}

input:disabled {
    background: #eef2f5;
    cursor: not-allowed;
}

.hint {
    font-size: 13px;
    color: #607d8b;
    margin: -10px 0 18px;
}

button.primary-button,
a.secondary-button {
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

a.secondary-button {
    background: #607d8b;
    margin-left: 8px;
}

button.primary-button:hover,
a.secondary-button:hover {
    transform: scale(1.03);
    color: white;
}

button.delete-button,
a.edit-button {
    border: none;
    border-radius: 20px;
    padding: 8px 14px;
    cursor: pointer;
    color: white;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

button.delete-button {
    background: #b42318;
}

a.edit-button {
    background: #005F73;
    margin-right: 6px;
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
    min-width: 900px;
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
    overflow-wrap: anywhere;
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

.status {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: bold;
    white-space: nowrap;
}

.status.graded {
    background: #d9f7e5;
    color: #087443;
}

.status.submitted {
    background: #dceeff;
    color: #005F73;
}

.status.review {
    background: #fff0c9;
    color: #8a6100;
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

body.dark .form-card,
body.dark .table-card,
body.dark .quick-notice {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .form-card h3,
body.dark .table-card h3,
body.dark .page-heading,
body.dark .quick-notice h3 {
    color: #4dc8e0;
}

body.dark #themeButton {
    background: #222;
    color: #4dc8e0;
}

body.dark input,
body.dark select,
body.dark textarea {
    background: #333e4b;
    color: white;
    border-color: #465363;
}

body.dark input:disabled {
    background: #2a333d;
}

body.dark .hint {
    color: #b9cbd1;
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

    .form-grid {
        grid-template-columns: 1fr;
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

    .welcome-text {
        font-size: 15px;
    }

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
        <a href="Admin.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="academic_management.php">Academics</a>
        <a href="admin_events.php">Events</a>
        <a href="assignments.php" class="active">Assignments</a>
        <a href="announcements.php">Notices</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2 class="page-heading">📝 Assignments &amp; Submissions</h2>

    <p class="welcome-text">
        Add a student's assignment status, grade and feedback. It appears
        on that student's dashboard automatically.
    </p>

    <?php if ($message !== ""): ?>
        <div class="message <?= h($messageType) ?>" role="status">
            <?= h($message) ?>
        </div>
    <?php endif; ?>

    <section class="form-card">
        <h3>👨‍🎓 Select Student</h3>

        <form method="get" action="assignments.php">
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

            <button type="submit" class="primary-button">
                Load Student Submissions
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

            <strong>Total Submissions:</strong>
            <?= count($assignments) ?>
        </div>

        <section class="form-card" id="assignmentForm">
            <h3>
                <?= $formId > 0 ? "✏️ Update Submission" : "➕ Add Submission" ?>
            </h3>

            <form method="post" action="assignments.php?student_id=<?= $studentId ?>">
                <input type="hidden" name="csrf_token" value="<?= h($_SESSION["assignment_csrf"]) ?>">
                <input type="hidden" name="action" value="save_assignment">
                <input type="hidden" name="student_id" value="<?= $studentId ?>">
                <input type="hidden" name="assignment_id" value="<?= $formId ?>">

                <div class="form-grid">
                    <div>
                        <label for="subject_name">Subject</label>
                        <input type="text" id="subject_name" name="subject_name"
                               maxlength="150" required
                               value="<?= h($formSubject) ?>"
                               placeholder="e.g. Object Oriented Programming">
                    </div>

                    <div>
                        <label for="assignment_title">Assignment Title</label>
                        <input type="text" id="assignment_title" name="assignment_title"
                               maxlength="200" required
                               value="<?= h($formTitle) ?>"
                               placeholder="e.g. Inheritance and Polymorphism">
                    </div>

                    <div>
                        <label for="submission_date">Submission Date</label>
                        <input type="date" id="submission_date" name="submission_date"
                               required value="<?= h($formDate) ?>">
                    </div>

                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <?php foreach ($statusOptions as $value => $label): ?>
                                <option value="<?= h($value) ?>"
                                    <?= $formStatus === $value ? "selected" : "" ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="grade">Grade (only for Graded)</label>
                <input type="text" id="grade" name="grade" maxlength="10"
                       value="<?= h($formGrade) ?>" placeholder="e.g. A+">
                <p class="hint">Shown as "Graded (A+)" on the student dashboard.</p>

                <label for="feedback">Feedback for Student (optional)</label>
                <textarea id="feedback" name="feedback" maxlength="1000"
                          placeholder="Write feedback about this assignment..."><?= h($formFeedback) ?></textarea>

                <button type="submit" class="primary-button">
                    <?= $formId > 0 ? "Update Submission" : "Save Submission" ?>
                </button>

                <?php if ($formId > 0): ?>
                    <a class="secondary-button"
                       href="assignments.php?student_id=<?= $studentId ?>">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-card">
            <h3>📋 Student Submissions</h3>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Assignment Title</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Feedback</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($assignments) > 0): ?>
                        <?php foreach ($assignments as $item): ?>
                            <?php
                            $itemStatus = $item["status"];
                            $itemLabel = $statusOptions[$itemStatus] ?? $itemStatus;

                            if ($itemStatus === "graded" && !empty($item["grade"])) {
                                $itemLabel .= " (" . $item["grade"] . ")";
                            }
                            ?>
                            <tr>
                                <td><?= h($item["subject_name"]) ?></td>
                                <td><?= h($item["assignment_title"]) ?></td>
                                <td><?= h(date("d M Y", strtotime($item["submission_date"]))) ?></td>
                                <td>
                                    <span class="status <?= h($itemStatus) ?>">
                                        <?= h($itemLabel) ?>
                                    </span>
                                </td>
                                <td><?= $item["feedback"] !== null && $item["feedback"] !== ""
                                    ? h($item["feedback"]) : "—" ?></td>
                                <td>
                                    <a class="edit-button"
                                       href="assignments.php?student_id=<?= $studentId ?>&amp;edit_id=<?= (int)$item["id"] ?>#assignmentForm">Edit</a>

                                    <form method="post"
                                          action="assignments.php?student_id=<?= $studentId ?>"
                                          class="inline-form"
                                          onsubmit="return confirm('Delete this submission?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($_SESSION["assignment_csrf"]) ?>">
                                        <input type="hidden" name="action" value="delete_assignment">
                                        <input type="hidden" name="student_id" value="<?= $studentId ?>">
                                        <input type="hidden" name="assignment_id" value="<?= (int)$item["id"] ?>">

                                        <button type="submit" class="delete-button">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">
                                No submissions added for this student yet.
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
            Verify the student, status and feedback before saving. Changes
            are visible to the selected student on their dashboard
            immediately.
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
    const statusSelect = document.getElementById("status");
    const gradeInput = document.getElementById("grade");

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

    // Grade box is only used when status is "Graded"
    function toggleGrade() {
        if (!statusSelect || !gradeInput) {
            return;
        }

        const isGraded = statusSelect.value === "graded";

        gradeInput.disabled = !isGraded;

        if (!isGraded) {
            gradeInput.value = "";
        }
    }

    if (statusSelect && gradeInput) {
        statusSelect.addEventListener("change", toggleGrade);
        toggleGrade();
    }
});
</script>

</body>
</html>