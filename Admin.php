<?php
require_once "timeout.php";

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}

$showLoginSuccess = !empty($_SESSION["login_success"]);
unset($_SESSION["login_success"]);
?>

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - Admin Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
body{background:linear-gradient(135deg,#F8FAFC,#EAF4F8);color:#2E2E2E;transition:.4s}
#notification{background:#005F73;color:white;padding:12px;text-align:center;font-weight:bold}
#closeNotification{margin-left:15px;border:none;padding:5px 10px;border-radius:15px;cursor:pointer;transition:.3s}
#closeNotification:hover{transform:scale(1.1)}
header{position:relative;background:linear-gradient(135deg,#002147,#4dc8e0);color:white;text-align:center;padding:35px 20px;box-shadow:0 8px 20px rgba(0,0,0,.18)}
header img.logo{width:180px;height:180px;object-fit:cover;border-radius:50%;border:5px solid #0d0000;padding:6px;background:white;box-shadow:0 10px 25px rgba(0,0,0,.25);transition:.4s}
header img.logo:hover{transform:scale(1.08)}
header h1{margin:18px 0;font-size:38px;letter-spacing:2px}
#themeButton{position:absolute;top:18px;right:25px;width:42px;height:42px;border:none;border-radius:50%;background:white;color:#002147;font-size:20px;display:flex;justify-content:center;align-items:center;cursor:pointer;box-shadow:0 4px 10px rgba(0,0,0,.2);transition:.3s}
#themeButton:hover{transform:scale(1.1)}
#menuButton{display:none;background:white;color:#002147;border:none;padding:10px 20px;border-radius:25px;font-weight:bold;cursor:pointer;transition:.3s}
#menuButton:hover{transform:scale(1.05)}
nav{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:14px;margin-top:15px}
nav a{text-decoration:none;color:white;background:rgba(255,255,255,.15);padding:10px 20px;border-radius:30px;font-weight:600;transition:.3s}
nav a:hover,nav a.active{background:white;color:#002147;transform:translateY(-3px)}
main{width:90%;margin:auto;padding:35px 0 45px}
.login-success-banner{display:flex;align-items:center;justify-content:space-between;gap:15px;margin:15px auto;padding:14px 20px;width:90%;background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;border-radius:8px;font-weight:600}
.close-login-banner{border:none;background:transparent;color:inherit;font-size:25px;line-height:1;cursor:pointer}
.page-heading{text-align:center;color:#002147;font-size:30px;margin-bottom:10px}
.welcome-text{text-align:center;font-size:17px;margin-bottom:30px}
.summary-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:35px}
.summary-card{background:white;border-radius:16px;padding:22px;box-shadow:0 5px 15px rgba(0,0,0,.08);border-top:5px solid #005F73;transition:.3s}
.summary-card:hover{transform:translateY(-5px)}
.summary-card .icon{width:52px;height:52px;border-radius:50%;background:#EAF4F8;display:flex;align-items:center;justify-content:center;font-size:25px;margin-bottom:14px}
.summary-card h3{font-size:18px;color:#002147;margin-bottom:8px}
.summary-card p{line-height:1.6}
.section-title{text-align:center;color:#002147;font-size:28px;margin:35px 0 22px}
.feature-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px}
.feature-card{background:white;border-top:5px solid #005F73;border-radius:16px;padding:25px;box-shadow:0 5px 15px rgba(0,0,0,.08);transition:.3s}
.feature-card:hover{background:#F2FBFC;transform:translateY(-5px)}
.feature-card h3{font-size:22px;color:#002147;margin-bottom:12px}
.feature-card p{line-height:1.7;margin-bottom:12px}
.feature-card ul{padding-left:22px;line-height:1.9}
.feature-card .feature-icon{font-size:30px;margin-bottom:12px}
.action-link{display:inline-block;margin-top:15px;text-decoration:none;color:white;background:linear-gradient(135deg,#002147,#005F73);padding:10px 22px;border-radius:25px;font-weight:600;transition:.3s}
.action-link:hover{transform:scale(1.04);color:white}
.quick-notice{margin-top:30px;background:white;border-left:5px solid #005F73;border-radius:12px;padding:22px;box-shadow:0 5px 15px rgba(0,0,0,.08)}
.quick-notice h3{color:#002147;margin-bottom:10px}
footer{background:linear-gradient(135deg,#002147,#4dc8e0);color:white;text-align:center;padding:22px;margin-top:20px}
footer p{margin:6px 0}
body.dark{background:#121212;color:white}
body.dark .summary-card,body.dark .feature-card,body.dark .quick-notice{background:#222;color:white}
body.dark .summary-card h3,body.dark .feature-card h3,body.dark .section-title,body.dark .page-heading,body.dark .quick-notice h3{color:#4dc8e0}
body.dark .summary-card .icon{background:#333}
body.dark .feature-card:hover{background:#2b2b2b}
body.dark #themeButton{background:#222;color:#4dc8e0}
body.dark .login-success-banner{background:#123d2d;color:#b7f7d2;border-color:#226747}
@media(max-width:992px){.summary-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){header img.logo{width:140px;height:140px}header h1{font-size:30px}#menuButton{display:inline-block}nav{display:none;flex-direction:column}nav.show{display:flex}nav a{width:220px;text-align:center}main{width:95%}#themeButton{top:15px;right:15px;width:38px;height:38px;font-size:18px}.feature-grid{grid-template-columns:1fr}}
@media(max-width:520px){.summary-grid{grid-template-columns:1fr}.page-heading{font-size:25px}.login-success-banner{width:95%;font-size:14px}}
</style>
</head>
<body>
<div id="notification">
    🔔 New updates are available!
    <button id="closeNotification" type="button" aria-label="Close notification">✕</button>
</div>

<header>
    <button id="themeButton" type="button" title="Change theme" aria-label="Change theme">🌙</button>
    <img class="logo" src="logo.jpeg" alt="Student Hub Logo">
    <h1>STUDENT HUB</h1>
    <button id="menuButton" type="button" aria-label="Open navigation menu">☰ Menu</button>
    <nav id="navigation">
        <a href="Home.html">Home</a>
        <a href="admin_dashboard.php" class="active">Admin Dashboard</a>
        <a href="Student_dashboard.php">Student Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<?php if ($showLoginSuccess): ?>
<div id="loginSuccessBanner" class="login-success-banner" role="status">
    <span>✓ Congratulations! You are logged in successfully.</span>
    <button type="button" id="closeLoginSuccess" class="close-login-banner" aria-label="Close notification">&times;</button>
</div>
<?php endif; ?>

<main>
    <h2 class="page-heading">👋 Welcome to Admin Dashboard</h2>
    <p class="welcome-text">Manage college activities, student information, academic work and important notices from one place.</p>

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
            <div class="feature-icon">👤</div>
            <h3>Professor Profile</h3>
            <p>Manage professor information and teaching details.</p>
            <ul>
                <li>Professor name, ID and department</li>
                <li>Designation and assigned subjects</li>
                <li>Profile update and password change</li>
            </ul>
            <a class="action-link" href="professor_profile.php">Manage Profile</a>
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
            <h3>Assignments & Marks</h3>
            <p>Organize academic submissions and grading.</p>
            <ul>
                <li>Upload assignments</li>
                <li>Check student submissions</li>
                <li>Enter marks and provide feedback</li>
            </ul>
            <a class="action-link" href="assignments.php">Manage Assignments</a>
        </article>

        <article class="feature-card">
            <div class="feature-icon">📅</div>
            <h3>Timetable & Announcements</h3>
            <p>Keep schedules and important updates organized.</p>
            <ul>
                <li>Daily lecture schedule</li>
                <li>Exam and practical dates</li>
                <li>Student announcements and important notices</li>
            </ul>
            <a class="action-link" href="announcements.php">Manage Notices</a>
        </article>
    </section>

    <section class="quick-notice">
        <h3>📌 Important Notice</h3>
        <p>Check student records and academic information carefully before making updates. Publish announcements and timetable changes clearly so students can stay informed.</p>
    </section>
</main>

<footer>
    <p><b>Student Hub</b></p>
    <p>Learn • Explore • Grow</p>
    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
    <p>Designed & developed by Heni Patel</p>
</footer>

<script>
const closeNotification = document.getElementById("closeNotification");
const notification = document.getElementById("notification");