<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "timeout.php";

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}

$showLoginSuccess = !empty($_SESSION["login_success"]);
unset($_SESSION["login_success"]);

// Connect to user_registration database
require_once "db.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log("User database connection is not available.");
    die("User database connection failed. Please check db.php.");
}

$userConn = $conn;

// Connect to studenthub database
$dataConn = new mysqli("localhost", "root", "", "studenthub");

if ($dataConn->connect_error) {
    error_log("StudentHub database connection failed: " . $dataConn->connect_error);
    die("StudentHub database connection failed. Please check the database.");
}

$dataConn->set_charset("utf8mb4");

// Total registered user accounts
$totalUsers = 0;

$result = $userConn->query(
    "SELECT COUNT(*) AS total FROM users"
);

if ($result) {
    $totalUsers = (int) $result->fetch_assoc()["total"];
} else {
    error_log("User count query failed: " . $userConn->error);
}

// Total active students
$totalStudents = 0;

$result = $dataConn->query(
    "SELECT COUNT(*) AS total
     FROM students
     WHERE status = 'active'"
);

if ($result) {
    $totalStudents = (int) $result->fetch_assoc()["total"];
} else {
    error_log("Student count query failed: " . $dataConn->error);
}

// Total events from studenthub database
$totalEvents = 0;

if (isset($dataConn) && $dataConn instanceof mysqli) {
    $result = $dataConn->query(
        "SELECT COUNT(*) AS total FROM events"
    );

    if ($result) {
        $row = $result->fetch_assoc();
        $totalEvents = (int) $row["total"];
        $result->free();
    } else {
        error_log("Total events query failed: " . $dataConn->error);
    }
}

// Total event registrations
$totalRegistrations = 0;

$result = $dataConn->query(
    "SELECT COUNT(*) AS total FROM registrations"
);

if ($result) {
    $totalRegistrations = (int) $result->fetch_assoc()["total"];
} else {
    error_log("Registration count query failed: " . $dataConn->error);
}

// Latest registrations
$latestRegistrations = $dataConn->query(
    "SELECT
        r.registration_id,
        r.student_id,
        r.event_id,
        s.name AS student_name,
        e.event_name
    FROM registrations r
    LEFT JOIN students s
        ON r.student_id = s.student_id
    LEFT JOIN events e
        ON r.event_id = e.event_id
    ORDER BY r.registration_id DESC
    LIMIT 5"
);

if (!$latestRegistrations) {
    error_log("Latest registrations query failed: " . $dataConn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - Admin Dashboard</title>

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
    transition: background .4s, color .4s;
    min-height: 100vh;
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
    transition: .3s;
}

#closeNotification:hover {
    transform: scale(1.1);
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
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0,0,0,.2);
    transition: .3s;
}

#themeButton:hover {
    transform: scale(1.1);
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
    transition: .3s;
}

#menuButton:hover {
    transform: scale(1.05);
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

.login-success-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin: 15px auto;
    padding: 14px 20px;
    width: 90%;
    max-width: 1500px;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
    border-radius: 8px;
    font-weight: 600;
}

.close-login-banner {
    border: none;
    background: transparent;
    color: inherit;
    font-size: 25px;
    line-height: 1;
    cursor: pointer;
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

.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 35px;
}

.summary-card {
    background: white;
    border-radius: 16px;
    padding: 22px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
    border-top: 5px solid #005F73;
    transition: .3s;
}

.summary-card:hover {
    transform: translateY(-5px);
}

.summary-card .icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #EAF4F8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 25px;
    margin-bottom: 14px;
}

.summary-card h3 {
    font-size: 18px;
    color: #002147;
    margin-bottom: 8px;
}

.summary-card p {
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

.feature-card {
    background: white;
    border-top: 5px solid #005F73;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
    transition: .3s;
    min-width: 0;
}

.feature-card:hover {
    background: #F2FBFC;
    transform: translateY(-5px);
}

.feature-card h3 {
    font-size: 22px;
    color: #002147;
    margin-bottom: 12px;
}

.feature-card p {
    line-height: 1.7;
    margin-bottom: 12px;
}

.feature-card ul {
    padding-left: 22px;
    line-height: 1.9;
}

.feature-card .feature-icon {
    font-size: 30px;
    margin-bottom: 12px;
}

.action-link {
    display: inline-block;
    margin-top: 15px;
    text-decoration: none;
    color: white;
    background: linear-gradient(135deg, #002147, #005F73);
    padding: 10px 22px;
    border-radius: 25px;
    font-weight: 600;
    transition: .3s;
}

.action-link:hover {
    transform: scale(1.04);
    color: white;
}

.quick-notice {
    margin-top: 30px;
    background: white;
    border-left: 5px solid #005F73;
    border-radius: 12px;
    padding: 22px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
}

.quick-notice h3 {
    color: #002147;
    margin-bottom: 10px;
}

.quick-notice p {
    line-height: 1.7;
}

footer {
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 22px;
    margin-top: 20px;
}

footer p {
    margin: 6px 0;
}

/* Dark Mode */

body.dark {
    background: linear-gradient(135deg, #121820, #202a35);
    color: #f1f5f9;
}

body.dark .summary-card,
body.dark .feature-card,
body.dark .quick-notice {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .summary-card h3,
body.dark .feature-card h3,
body.dark .section-title,
body.dark .page-heading,
body.dark .quick-notice h3 {
    color: #4dc8e0;
}

body.dark .summary-card .icon {
    background: #333e4b;
}

body.dark .feature-card:hover {
    background: #2b3642;
}

body.dark #themeButton {
    background: #222;
    color: #4dc8e0;
}

body.dark .login-success-banner {
    background: #123d2d;
    color: #b7f7d2;
    border-color: #226747;
}

/* Latest Registrations Table */

.table-responsive {
    width: 100%;
    overflow-x: auto;
    margin: 0 auto 35px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,.08);
}

.table-responsive table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
    margin: 0;
    background: white;
    color: #2E2E2E;
}

.table-responsive table thead th {
    background: linear-gradient(135deg, #002147, #005F73);
    color: white;
    padding: 15px 18px;
    text-align: left;
    font-size: 15px;
    border: 1px solid #d6e3eb;
    white-space: nowrap;
}

.table-responsive table tbody td {
    padding: 13px 18px;
    border: 1px solid #d6e3eb;
    font-size: 15px;
    vertical-align: middle;
}

.table-responsive table tbody tr:nth-child(even) {
    background: #EAF4F8;
}

.table-responsive table tbody tr:nth-child(odd) {
    background: #FFFFFF;
}

.table-responsive table tbody tr:hover {
    background: #d7f0f5;
}

body.dark .table-responsive,
body.dark .table-responsive table {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .table-responsive table tbody tr:nth-child(even),
body.dark .table-responsive table tbody tr:nth-child(odd) {
    background: #222b35;
    color: #f1f5f9;
}

body.dark .table-responsive table tbody tr:hover {
    background: #333e4b;
}

body.dark .table-responsive table thead th {
    background: linear-gradient(135deg, #002147, #005F73);
    color: white;
}

body.dark .table-responsive table th,
body.dark .table-responsive table td {
    border-color: #465363;
}

/* Tablet */

@media(max-width: 1100px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    nav {
        gap: 10px;
    }

    nav a {
        padding: 9px 14px;
    }
}

/* Mobile Navigation */

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
        font-size: 18px;
    }

    .feature-grid {
        grid-template-columns: 1fr;
    }

    .section-title {
        font-size: 25px;
    }
}

/* Small Mobile */

@media(max-width: 520px) {
    header img.logo {
        width: 110px;
        height: 110px;
    }

    header h1 {
        font-size: 25px;
        letter-spacing: 1px;
    }

    .summary-grid {
        grid-template-columns: 1fr;
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

    .feature-card {
        padding: 20px;
    }

    .feature-card h3 {
        font-size: 20px;
    }

    .summary-card {
        padding: 20px;
    }

    .login-success-banner {
        width: 95%;
        font-size: 14px;
        padding: 12px;
    }

    .quick-notice {
        padding: 17px;
    }

    footer {
        font-size: 14px;
    }

    .table-responsive table thead th,
    .table-responsive table tbody td {
        padding: 10px;
        font-size: 13px;
    }
}

/* Accessibility */

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

    <!-- Admin navigation: only admin-related pages -->
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

<?php if ($showLoginSuccess): ?>
<div id="loginSuccessBanner" class="login-success-banner" role="status">
    <span>✓ Congratulations! You are logged in successfully.</span>
    <button type="button" id="closeLoginSuccess"
            class="close-login-banner"
            aria-label="Close success message">&times;</button>
</div>
<?php endif; ?>

<main>
    <h2 class="page-heading">👋 Welcome to Admin Dashboard</h2>

    <p class="welcome-text">
        Manage college activities, student information, academic work
        and important notices from one place.
    </p>

    <h2 class="section-title">Latest Registrations</h2>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Registration ID</th>
                    <th>Student Name</th>
                    <th>Event Name</th>
                </tr>
            </thead>

            <tbody>
            <?php if ($latestRegistrations && $latestRegistrations->num_rows > 0): ?>
                <?php while ($row = $latestRegistrations->fetch_assoc()): ?>
                <tr>
                    <td><?= (int) $row["registration_id"] ?></td>
                    <td>
                        <?= htmlspecialchars(
                            $row["student_name"] ?? "Unknown",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars(
                            $row["event_name"] ?? "Unknown",
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center;">
                        No registrations found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2 class="section-title">Dashboard Statistics</h2>

    <section class="summary-grid">
        <article class="summary-card">
            <div class="icon">👥</div>
            <h3>Total Users</h3>
            <p><strong><?= $totalUsers ?></strong> registered accounts</p>
        </article>

        <article class="summary-card">
            <div class="icon">🎓</div>
            <h3>Total Students</h3>
            <p><strong><?= $totalStudents ?></strong> student records</p>
        </article>

        <article class="summary-card">
            <div class="icon">🎉</div>
            <h3>Total Events</h3>
            <p><strong><?= $totalEvents ?></strong> college events</p>
        </article>

        <article class="summary-card">
            <div class="icon">📝</div>
            <h3>Total Registrations</h3>
            <p><strong><?= $totalRegistrations ?></strong> event registrations</p>
        </article>
    </section>

    <section class="summary-grid">
        <article class="summary-card">
            <div class="icon">👨‍🎓</div>
            <h3>Student Management</h3>
            <p>View student records, enrollment details and class information.</p>
        </article>

        <article class="summary-card">
            <div class="icon">📚</div>
            <h3>Academic Activities</h3>
            <p>Coordinate subjects, assignments, marks and academic updates.</p>
        </article>

        <article class="summary-card">
            <div class="icon">🗓️</div>
            <h3>Timetable</h3>
            <p>Review lecture schedules, examinations and practical dates.</p>
        </article>

        <article class="summary-card">
            <div class="icon">📢</div>
            <h3>Announcements</h3>
            <p>Keep students informed about college notices and updates.</p>
        </article>
    </section>

    <h2 class="section-title">Dashboard Management</h2>

    <section class="feature-grid">

        <article class="feature-card">
            <div class="feature-icon">🎓</div>
            <h3>Academic Management</h3>
            <p>Manage student academic performance and semester marks.</p>

            <ul>
                <li>Add or update student CGPA</li>
                <li>Update attendance percentage</li>
                <li>Enter subject-wise semester marks</li>
            </ul>

            <a class="action-link" href="academic_management.php">Manage Academics</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">🎉</div>
            <h3>Event Management</h3>
            <p>Manage college events and upload event posters.</p>

            <ul>
                <li>Add new college events</li>
                <li>View and update event details</li>
                <li>Delete events and manage posters</li>
            </ul>

            <a class="action-link" href="admin_events.php">Manage Events</a>
            <a class="action-link" href="index.html">API Event Management</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">🎓</div>
            <h3>Student Management</h3>
            <p>Access student records and class-wise information.</p>

            <ul>
                <li>Student list and enrollment numbers</li>
                <li>Class-wise student details</li>
                <li>Attendance and student performance</li>
            </ul>

            <a class="action-link" href="students.php">View Students</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">📝</div>
            <h3>Assignments &amp; Marks</h3>
            <p>Organize academic submissions and grading.</p>

            <ul>
                <li>Add student submission status</li>
                <li>Update grade and result</li>
                <li>Send feedback to students</li>
            </ul>

            <a class="action-link" href="assignments.php">Manage Assignments</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">📅</div>
            <h3>Timetable &amp; Announcements</h3>
            <p>Keep schedules and important updates organized.</p>

            <ul>
                <li>Daily lecture schedule</li>
                <li>Exam and practical dates</li>
                <li>Student announcements and important notices</li>
            </ul>

            <a class="action-link" href="announcements.php">Manage Notices</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">📋</div>
            <h3>Audit Logs</h3>
            <p>View records of important admin activities and actions.</p>

            <ul>
                <li>Track admin actions</li>
                <li>Review activity history</li>
                <li>Monitor important changes</li>
            </ul>

            <a class="action-link" href="audit_logs.php">View Audit Logs</a>
        </article>

    </section>

    <section class="quick-notice">
        <h3>📌 Important Notice</h3>
        <p>
            Check student records and academic information carefully before
            making updates. Publish announcements and timetable changes
            clearly so students can stay informed.
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

    const loginSuccessBanner = document.getElementById("loginSuccessBanner");
    const closeLoginSuccess = document.getElementById("closeLoginSuccess");

    // Read saved theme from Local Storage
    let savedTheme = "light";

    try {
        savedTheme = localStorage.getItem("theme") || "light";
    } catch (error) {
        savedTheme = "light";
    }

    function applyTheme(theme) {
        const isDark = theme === "dark";

        document.body.classList.toggle("dark", isDark);

        themeButton.textContent = isDark ? "☀️" : "🌙";

        themeButton.setAttribute(
            "aria-label",
            isDark ? "Switch to light mode" : "Switch to dark mode"
        );

        themeButton.title = isDark ? "Switch to light mode" : "Switch to dark mode";
    }

    applyTheme(savedTheme);

    // Toggle Light/Dark Mode and save preference
    themeButton.addEventListener("click", function () {
        const newTheme = document.body.classList.contains("dark")
            ? "light"
            : "dark";

        applyTheme(newTheme);

        try {
            localStorage.setItem("theme", newTheme);
        } catch (error) {
            // Theme still works if Local Storage is unavailable
        }
    });

    // Restore notification state
    try {
        if (localStorage.getItem("notificationClosed") === "true") {
            notification.style.display = "none";
        }
    } catch (error) {
        // Notification remains visible if Local Storage is unavailable
    }

    // Close notification and save preference
    closeNotification.addEventListener("click", function () {
        notification.style.display = "none";

        try {
            localStorage.setItem("notificationClosed", "true");
        } catch (error) {
            // Closing still works if Local Storage is unavailable
        }
    });

    // Hamburger menu
    menuButton.addEventListener("click", function () {
        const isOpen = navigation.classList.toggle("show");

        menuButton.setAttribute("aria-expanded", String(isOpen));
        menuButton.setAttribute(
            "aria-label",
            isOpen ? "Close navigation menu" : "Open navigation menu"
        );

        menuButton.textContent = isOpen ? "✕ Close Menu" : "☰ Menu";
    });

    // Close mobile menu after selecting a navigation link
    navigation.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navigation.classList.remove("show");
            menuButton.setAttribute("aria-expanded", "false");
            menuButton.setAttribute("aria-label", "Open navigation menu");
            menuButton.textContent = "☰ Menu";
        });
    });

    // Close login success message
    if (closeLoginSuccess && loginSuccessBanner) {
        closeLoginSuccess.addEventListener("click", function () {
            loginSuccessBanner.style.display = "none";
        });
    }

});
</script>

</body>
</html>