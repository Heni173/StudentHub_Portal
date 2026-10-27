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

if (empty($_SESSION["notice_csrf"])) {
    $_SESSION["notice_csrf"] = bin2hex(random_bytes(32));
}

/* ---------------- Helpers ---------------- */

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function validTime($value)
{
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string)$value) === 1;
}

function validDate($value)
{
    $d = DateTime::createFromFormat("Y-m-d", (string)$value);
    return $d && $d->format("Y-m-d") === $value;
}

function fmtTime($value)
{
    return $value ? date("h:i A", strtotime((string)$value)) : "";
}

function fv(array $old, $row, $key, $default = "")
{
    if (isset($old[$key])) {
        return $old[$key];
    }

    if (is_array($row) && isset($row[$key])) {
        return $row[$key];
    }

    return $default;
}

function rowExists(mysqli $conn, $table, $id)
{
    $allowed = ["announcements", "timetable", "exam_schedule"];

    if (!in_array($table, $allowed, true)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM `$table` WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $found = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $found;
}

/* ---------------- Settings ---------------- */

$tabs = [
    "announcements" => "📢 Announcements",
    "timetable"     => "🗓️ Timetable",
    "exams"         => "📝 Exams & Practicals",
];

$categories = [
    "general"  => "General",
    "academic" => "Academic",
    "exam"     => "Exam",
    "event"    => "Event",
    "urgent"   => "Urgent",
];

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

$examTypes = [
    "exam"      => "Exam",
    "practical" => "Practical",
];

$deleteTables = [
    "delete_announcement" => "announcements",
    "delete_timetable"    => "timetable",
    "delete_exam"         => "exam_schedule",
];

$tab = $_GET["tab"] ?? $_POST["tab"] ?? "announcements";

if (!isset($tabs[$tab])) {
    $tab = "announcements";
}

$editId = (int)($_GET["edit_id"] ?? 0);

$message = "";
$messageType = "success";

if (!empty($_SESSION["notice_flash"])) {
    $message = $_SESSION["notice_flash"]["text"];
    $messageType = $_SESSION["notice_flash"]["type"];
    unset($_SESSION["notice_flash"]);
}

/* ---------------- POST: save / delete ---------------- */

$old = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";
    $itemId = (int)($_POST["item_id"] ?? 0);

    if (!hash_equals($_SESSION["notice_csrf"], $token)) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";
    } else {
        try {
            $action = $_POST["action"] ?? "";
            $error = "";
            $success = "";

            if ($action === "save_announcement") {

                $title = trim($_POST["title"] ?? "");
                $text = trim($_POST["message"] ?? "");
                $category = $_POST["category"] ?? "";

                if (
                    $title === "" || mb_strlen($title) > 150 ||
                    $text === "" || mb_strlen($text) > 2000 ||
                    !isset($categories[$category])
                ) {
                    $error = "Please enter a valid title, category and message.";
                } elseif ($itemId > 0 && !rowExists($conn, "announcements", $itemId)) {
                    $error = "This announcement was not found.";
                } elseif ($itemId > 0) {
                    $stmt = $conn->prepare(
                        "UPDATE announcements
                         SET title = ?, message = ?, category = ?
                         WHERE id = ?"
                    );
                    $stmt->bind_param("sssi", $title, $text, $category, $itemId);
                    $stmt->execute();
                    $stmt->close();
                    $success = "Announcement updated successfully.";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO announcements (title, message, category)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param("sss", $title, $text, $category);
                    $stmt->execute();
                    $stmt->close();
                    $success = "Announcement published successfully.";
                }

            } elseif ($action === "save_timetable") {

                $semester = (int)($_POST["semester"] ?? 0);
                $day = $_POST["day_of_week"] ?? "";
                $start = trim($_POST["start_time"] ?? "");
                $end = trim($_POST["end_time"] ?? "");
                $subject = trim($_POST["subject"] ?? "");
                $faculty = trim($_POST["faculty"] ?? "");
                $room = trim($_POST["room"] ?? "");

                if (
                    $semester < 1 || $semester > 12 ||
                    !in_array($day, $days, true) ||
                    !validTime($start) || !validTime($end) ||
                    $subject === "" || mb_strlen($subject) > 150 ||
                    mb_strlen($faculty) > 100 || mb_strlen($room) > 50
                ) {
                    $error = "Please enter valid semester, day, times and subject.";
                } elseif ($end <= $start) {
                    $error = "End time must be after the start time.";
                } elseif ($itemId > 0 && !rowExists($conn, "timetable", $itemId)) {
                    $error = "This timetable entry was not found.";
                } else {
                    $facultyValue = $faculty === "" ? null : $faculty;
                    $roomValue = $room === "" ? null : $room;

                    if ($itemId > 0) {
                        $stmt = $conn->prepare(
                            "UPDATE timetable
                             SET semester = ?, day_of_week = ?, start_time = ?,
                                 end_time = ?, subject = ?, faculty = ?, room = ?
                             WHERE id = ?"
                        );
                        $stmt->bind_param(
                            "issssssi",
                            $semester, $day, $start, $end,
                            $subject, $facultyValue, $roomValue, $itemId
                        );
                        $stmt->execute();
                        $stmt->close();
                        $success = "Lecture updated successfully.";
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO timetable
                             (semester, day_of_week, start_time, end_time,
                              subject, faculty, room)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            "issssss",
                            $semester, $day, $start, $end,
                            $subject, $facultyValue, $roomValue
                        );
                        $stmt->execute();
                        $stmt->close();
                        $success = "Lecture added to the timetable.";
                    }
                }

            } elseif ($action === "save_exam") {

                $semester = (int)($_POST["semester"] ?? 0);
                $type = $_POST["exam_type"] ?? "";
                $subject = trim($_POST["subject"] ?? "");
                $date = trim($_POST["exam_date"] ?? "");
                $start = trim($_POST["start_time"] ?? "");
                $end = trim($_POST["end_time"] ?? "");
                $venue = trim($_POST["venue"] ?? "");

                if (
                    $semester < 1 || $semester > 12 ||
                    !isset($examTypes[$type]) ||
                    $subject === "" || mb_strlen($subject) > 150 ||
                    !validDate($date) ||
                    !validTime($start) ||
                    ($end !== "" && !validTime($end)) ||
                    mb_strlen($venue) > 100
                ) {
                    $error = "Please enter valid semester, type, subject, date and time.";
                } elseif ($end !== "" && $end <= $start) {
                    $error = "End time must be after the start time.";
                } elseif ($itemId > 0 && !rowExists($conn, "exam_schedule", $itemId)) {
                    $error = "This exam entry was not found.";
                } else {
                    $endValue = $end === "" ? null : $end;
                    $venueValue = $venue === "" ? null : $venue;

                    if ($itemId > 0) {
                        $stmt = $conn->prepare(
                            "UPDATE exam_schedule
                             SET semester = ?, exam_type = ?, subject = ?,
                                 exam_date = ?, start_time = ?, end_time = ?,
                                 venue = ?
                             WHERE id = ?"
                        );
                        $stmt->bind_param(
                            "issssssi",
                            $semester, $type, $subject, $date,
                            $start, $endValue, $venueValue, $itemId
                        );
                        $stmt->execute();
                        $stmt->close();
                        $success = "Schedule updated successfully.";
                    } else {
                        $stmt = $conn->prepare(
                            "INSERT INTO exam_schedule
                             (semester, exam_type, subject, exam_date,
                              start_time, end_time, venue)
                             VALUES (?, ?, ?, ?, ?, ?, ?)"
                        );
                        $stmt->bind_param(
                            "issssss",
                            $semester, $type, $subject, $date,
                            $start, $endValue, $venueValue
                        );
                        $stmt->execute();
                        $stmt->close();
                        $success = "Schedule added successfully.";
                    }
                }

            } elseif (isset($deleteTables[$action])) {

                $table = $deleteTables[$action];

                $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
                $stmt->bind_param("i", $itemId);
                $stmt->execute();
                $deleted = $stmt->affected_rows > 0;
                $stmt->close();

                if ($deleted) {
                    $success = "Deleted successfully.";
                } else {
                    $error = "Unable to delete this record.";
                }

            } else {
                $error = "Unknown action.";
            }

            if ($error !== "") {
                $message = $error;
                $messageType = "error";
                $old = $_POST;
                $editId = $itemId;
            } else {
                $_SESSION["notice_flash"] = [
                    "type" => "success",
                    "text" => $success,
                ];

                header("Location: announcements.php?tab=" . urlencode($tab));
                exit();
            }
        } catch (Throwable $e) {
            $message = "Database Error: " . $e->getMessage();
            $messageType = "error";
            $old = $_POST;
            $editId = $itemId;
        }
    }
}

/* ---------------- Load data for the open tab ---------------- */

$items = [];

try {
    if ($tab === "announcements") {
        $sql = "SELECT id, title, message, category, created_at
                FROM announcements
                ORDER BY created_at DESC, id DESC";
    } elseif ($tab === "timetable") {
        $sql = "SELECT id, semester, day_of_week, start_time, end_time,
                       subject, faculty, room
                FROM timetable
                ORDER BY semester,
                         FIELD(day_of_week, 'Monday','Tuesday','Wednesday',
                               'Thursday','Friday','Saturday'),
                         start_time";
    } else {
        $sql = "SELECT id, semester, exam_type, subject, exam_date,
                       start_time, end_time, venue
                FROM exam_schedule
                ORDER BY exam_date, start_time";
    }

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
    }
} catch (Throwable $e) {
    if ($message === "") {
        $message = "Database Error: " . $e->getMessage()
            . " (Create the required table first.)";
        $messageType = "error";
    }
}

$editRow = null;

if ($editId > 0) {
    foreach ($items as $item) {
        if ((int)$item["id"] === $editId) {
            $editRow = $item;
            break;
        }
    }
}

$formId = $editRow ? (int)$editRow["id"] : 0;
$csrf = $_SESSION["notice_csrf"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timetable &amp; Announcements - Student Hub</title>

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
    margin-bottom: 25px;
    line-height: 1.6;
}

/* Tabs */

.tabs {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 28px;
}

.tabs a {
    text-decoration: none;
    color: #002147;
    background: white;
    border: 2px solid #d6e3eb;
    padding: 11px 22px;
    border-radius: 30px;
    font-weight: 700;
    transition: .3s;
}

.tabs a:hover {
    border-color: #005F73;
    transform: translateY(-2px);
}

.tabs a.active {
    background: linear-gradient(135deg, #002147, #005F73);
    color: white;
    border-color: transparent;
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
    min-height: 130px;
}

input:focus, select:focus, textarea:focus {
    outline: 2px solid #4dc8e0;
    border-color: #005F73;
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

.table-responsive {
    width: 100%;
    overflow-x: auto;
    background: white;
    border-radius: 12px;
}

table {
    width: 100%;
    min-width: 850px;
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

.badge {
    display: inline-block;
    padding: 6px 13px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: bold;
    white-space: nowrap;
}

.badge.general  { background: #dceeff; color: #005F73; }
.badge.academic { background: #d9f7e5; color: #087443; }
.badge.exam     { background: #fff0c9; color: #8a6100; }
.badge.event    { background: #ece3ff; color: #5b34b0; }
.badge.urgent   { background: #fee2e2; color: #b42318; }
.badge.practical{ background: #e0f4f8; color: #006b80; }

.count-note {
    color: #607d8b;
    font-size: 14px;
    margin-bottom: 14px;
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

body.dark .tabs a {
    background: #222b35;
    color: #f1f5f9;
    border-color: #465363;
}

body.dark .tabs a.active {
    background: linear-gradient(135deg, #002147, #005F73);
    color: white;
}

body.dark input,
body.dark select,
body.dark textarea {
    background: #333e4b;
    color: white;
    border-color: #465363;
}

body.dark .count-note {
    color: #b9cbd1;
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

    .tabs a {
        padding: 10px 16px;
        font-size: 14px;
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
        <a href="assignments.php">Assignments</a>
        <a href="announcements.php" class="active">Notices</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h2 class="page-heading">📅 Timetable &amp; Announcements</h2>

    <p class="welcome-text">
        Keep schedules and important updates organized from one place.
    </p>

    <div class="tabs" role="navigation" aria-label="Sections">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="announcements.php?tab=<?= h($key) ?>"
               class="<?= $tab === $key ? "active" : "" ?>"><?= h($label) ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($message !== ""): ?>
        <div class="message <?= h($messageType) ?>" role="status">
            <?= h($message) ?>
        </div>
    <?php endif; ?>


    <?php if ($tab === "announcements"): ?>

        <!-- ================= ANNOUNCEMENTS ================= -->

        <section class="form-card" id="itemForm">
            <h3><?= $formId > 0 ? "✏️ Update Announcement" : "➕ New Announcement" ?></h3>

            <form method="post" action="announcements.php?tab=announcements">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="save_announcement">
                <input type="hidden" name="tab" value="announcements">
                <input type="hidden" name="item_id" value="<?= $formId ?>">

                <div class="form-grid">
                    <div>
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" maxlength="150" required
                               value="<?= h(fv($old, $editRow, "title")) ?>"
                               placeholder="e.g. Mid-Sem exam notice">
                    </div>

                    <div>
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <?php $selCat = fv($old, $editRow, "category", "general"); ?>
                            <?php foreach ($categories as $value => $label): ?>
                                <option value="<?= h($value) ?>" <?= $selCat === $value ? "selected" : "" ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="message">Message</label>
                <textarea id="message" name="message" maxlength="2000" required
                          placeholder="Write the announcement for students..."><?= h(fv($old, $editRow, "message")) ?></textarea>

                <button type="submit" class="primary-button">
                    <?= $formId > 0 ? "Update Announcement" : "Publish Announcement" ?>
                </button>

                <?php if ($formId > 0): ?>
                    <a class="secondary-button" href="announcements.php?tab=announcements">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-card">
            <h3>📢 All Announcements</h3>
            <p class="count-note">Total: <?= count($items) ?></p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Message</th>
                            <th>Posted On</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= h($item["title"]) ?></td>
                                <td>
                                    <span class="badge <?= h($item["category"]) ?>">
                                        <?= h($categories[$item["category"]] ?? $item["category"]) ?>
                                    </span>
                                </td>
                                <td><?= h(mb_strimwidth((string)$item["message"], 0, 120, "...")) ?></td>
                                <td><?= h(date("d M Y", strtotime($item["created_at"]))) ?></td>
                                <td>
                                    <a class="edit-button"
                                       href="announcements.php?tab=announcements&amp;edit_id=<?= (int)$item["id"] ?>#itemForm">Edit</a>

                                    <form method="post" action="announcements.php?tab=announcements"
                                          class="inline-form"
                                          onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete_announcement">
                                        <input type="hidden" name="tab" value="announcements">
                                        <input type="hidden" name="item_id" value="<?= (int)$item["id"] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No announcements added yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>


    <?php elseif ($tab === "timetable"): ?>

        <!-- ================= TIMETABLE ================= -->

        <section class="form-card" id="itemForm">
            <h3><?= $formId > 0 ? "✏️ Update Lecture" : "➕ Add Lecture" ?></h3>

            <form method="post" action="announcements.php?tab=timetable">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="save_timetable">
                <input type="hidden" name="tab" value="timetable">
                <input type="hidden" name="item_id" value="<?= $formId ?>">

                <div class="form-grid">
                    <div>
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <?php $selSem = (int)fv($old, $editRow, "semester", 1); ?>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $selSem === $i ? "selected" : "" ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label for="day_of_week">Day</label>
                        <select id="day_of_week" name="day_of_week" required>
                            <?php $selDay = fv($old, $editRow, "day_of_week", "Monday"); ?>
                            <?php foreach ($days as $day): ?>
                                <option value="<?= h($day) ?>" <?= $selDay === $day ? "selected" : "" ?>>
                                    <?= h($day) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required
                               value="<?= h(substr((string)fv($old, $editRow, "start_time", "09:00"), 0, 5)) ?>">
                    </div>

                    <div>
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required
                               value="<?= h(substr((string)fv($old, $editRow, "end_time", "10:00"), 0, 5)) ?>">
                    </div>
                </div>

                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" maxlength="150" required
                       value="<?= h(fv($old, $editRow, "subject")) ?>"
                       placeholder="e.g. Object Oriented Programming">

                <div class="form-grid">
                    <div>
                        <label for="faculty">Faculty (optional)</label>
                        <input type="text" id="faculty" name="faculty" maxlength="100"
                               value="<?= h(fv($old, $editRow, "faculty")) ?>"
                               placeholder="e.g. Prof. A. Shah">
                    </div>

                    <div>
                        <label for="room">Room / Lab (optional)</label>
                        <input type="text" id="room" name="room" maxlength="50"
                               value="<?= h(fv($old, $editRow, "room")) ?>"
                               placeholder="e.g. Room 204">
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    <?= $formId > 0 ? "Update Lecture" : "Add Lecture" ?>
                </button>

                <?php if ($formId > 0): ?>
                    <a class="secondary-button" href="announcements.php?tab=timetable">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-card">
            <h3>🗓️ Daily Lecture Schedule</h3>
            <p class="count-note">Total lectures: <?= count($items) ?></p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Subject</th>
                            <th>Faculty</th>
                            <th>Room</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>Sem <?= (int)$item["semester"] ?></td>
                                <td><?= h($item["day_of_week"]) ?></td>
                                <td><?= h(fmtTime($item["start_time"])) ?> - <?= h(fmtTime($item["end_time"])) ?></td>
                                <td><?= h($item["subject"]) ?></td>
                                <td><?= $item["faculty"] ? h($item["faculty"]) : "—" ?></td>
                                <td><?= $item["room"] ? h($item["room"]) : "—" ?></td>
                                <td>
                                    <a class="edit-button"
                                       href="announcements.php?tab=timetable&amp;edit_id=<?= (int)$item["id"] ?>#itemForm">Edit</a>

                                    <form method="post" action="announcements.php?tab=timetable"
                                          class="inline-form"
                                          onsubmit="return confirm('Delete this lecture?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete_timetable">
                                        <input type="hidden" name="tab" value="timetable">
                                        <input type="hidden" name="item_id" value="<?= (int)$item["id"] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">No lectures added yet.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>


    <?php else: ?>

        <!-- ================= EXAMS & PRACTICALS ================= -->

        <section class="form-card" id="itemForm">
            <h3><?= $formId > 0 ? "✏️ Update Schedule" : "➕ Add Exam / Practical" ?></h3>

            <form method="post" action="announcements.php?tab=exams">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="save_exam">
                <input type="hidden" name="tab" value="exams">
                <input type="hidden" name="item_id" value="<?= $formId ?>">

                <div class="form-grid">
                    <div>
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <?php $selSem = (int)fv($old, $editRow, "semester", 1); ?>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?= $i ?>" <?= $selSem === $i ? "selected" : "" ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div>
                        <label for="exam_type">Type</label>
                        <select id="exam_type" name="exam_type" required>
                            <?php $selType = fv($old, $editRow, "exam_type", "exam"); ?>
                            <?php foreach ($examTypes as $value => $label): ?>
                                <option value="<?= h($value) ?>" <?= $selType === $value ? "selected" : "" ?>>
                                    <?= h($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" maxlength="150" required
                       value="<?= h(fv($old, $editRow, "subject")) ?>"
                       placeholder="e.g. Data Structure and Algorithm">

                <div class="form-grid">
                    <div>
                        <label for="exam_date">Date</label>
                        <input type="date" id="exam_date" name="exam_date" required
                               value="<?= h(fv($old, $editRow, "exam_date", date("Y-m-d"))) ?>">
                    </div>

                    <div>
                        <label for="venue">Venue (optional)</label>
                        <input type="text" id="venue" name="venue" maxlength="100"
                               value="<?= h(fv($old, $editRow, "venue")) ?>"
                               placeholder="e.g. Main Hall / Computer Lab 2">
                    </div>

                    <div>
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required
                               value="<?= h(substr((string)fv($old, $editRow, "start_time", "10:00"), 0, 5)) ?>">
                    </div>

                    <div>
                        <label for="end_time">End Time (optional)</label>
                        <input type="time" id="end_time" name="end_time"
                               value="<?= h(substr((string)fv($old, $editRow, "end_time", ""), 0, 5)) ?>">
                    </div>
                </div>

                <button type="submit" class="primary-button">
                    <?= $formId > 0 ? "Update Schedule" : "Add to Schedule" ?>
                </button>

                <?php if ($formId > 0): ?>
                    <a class="secondary-button" href="announcements.php?tab=exams">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-card">
            <h3>📝 Exam &amp; Practical Dates</h3>
            <p class="count-note">Total: <?= count($items) ?></p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Venue</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>Sem <?= (int)$item["semester"] ?></td>
                                <td>
                                    <span class="badge <?= $item["exam_type"] === "practical" ? "practical" : "exam" ?>">
                                        <?= h($examTypes[$item["exam_type"]] ?? $item["exam_type"]) ?>
                                    </span>
                                </td>
                                <td><?= h($item["subject"]) ?></td>
                                <td><?= h(date("d M Y", strtotime($item["exam_date"]))) ?></td>
                                <td>
                                    <?= h(fmtTime($item["start_time"])) ?>
                                    <?= $item["end_time"] ? " - " . h(fmtTime($item["end_time"])) : "" ?>
                                </td>
                                <td><?= $item["venue"] ? h($item["venue"]) : "—" ?></td>
                                <td>
                                    <a class="edit-button"
                                       href="announcements.php?tab=exams&amp;edit_id=<?= (int)$item["id"] ?>#itemForm">Edit</a>

                                    <form method="post" action="announcements.php?tab=exams"
                                          class="inline-form"
                                          onsubmit="return confirm('Delete this schedule?');">
                                        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                                        <input type="hidden" name="action" value="delete_exam">
                                        <input type="hidden" name="tab" value="exams">
                                        <input type="hidden" name="item_id" value="<?= (int)$item["id"] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">No exam or practical dates added yet.</td>
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
            Check dates, times and semester carefully before saving.
            Remove outdated announcements and schedules regularly.
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