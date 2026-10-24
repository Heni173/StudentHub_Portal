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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - Admin Dashboard</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}

body{
    background:linear-gradient(135deg,#F8FAFC,#EAF4F8);
    color:#2E2E2E;
    transition:background .4s,color .4s;
    min-height:100vh;
}

#notification{
    background:#005F73;
    color:white;
    padding:12px;
    text-align:center;
    font-weight:bold;
}

#closeNotification{
    margin-left:15px;
    border:none;
    padding:5px 10px;
    border-radius:15px;
    cursor:pointer;
    transition:.3s;
}

#closeNotification:hover{transform:scale(1.1)}

header{
    position:relative;
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
    text-align:center;
    padding:35px 20px;
    box-shadow:0 8px 20px rgba(0,0,0,.18);
}

header img.logo{
    width:180px;
    height:180px;
    object-fit:cover;
    border-radius:50%;
    border:5px solid #0d0000;
    padding:6px;
    background:white;
    box-shadow:0 10px 25px rgba(0,0,0,.25);
    transition:.4s;
}

header img.logo:hover{transform:scale(1.08)}

header h1{
    margin:18px 0;
    font-size:38px;
    letter-spacing:2px;
}

#themeButton{
    position:absolute;
    top:18px;
    right:25px;
    width:42px;
    height:42px;
    border:none;
    border-radius:50%;
    background:white;
    color:#002147;
    font-size:20px;
    display:flex;
    justify-content:center;
    align-items:center;
    cursor:pointer;
    box-shadow:0 4px 10px rgba(0,0,0,.2);
    transition:.3s;
}

#themeButton:hover{transform:scale(1.1)}

#menuButton{
    display:none;
    background:white;
    color:#002147;
    border:none;
    padding:10px 20px;
    border-radius:25px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

#menuButton:hover{transform:scale(1.05)}

nav{
    display:flex;
    justify-content:center;
    align-items:center;
    flex-wrap:wrap;
    gap:14px;
    margin-top:15px;
}

nav a{
    text-decoration:none;
    color:white;
    background:rgba(255,255,255,.15);
    padding:10px 20px;
    border-radius:30px;
    font-weight:600;
    transition:.3s;
}

nav a:hover,nav a.active{
    background:white;
    color:#002147;
    transform:translateY(-3px);
}

main{
    width:90%;
    max-width:1500px;
    margin:auto;
    padding:35px 0 45px;
}

.login-success-banner{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin:15px auto;
    padding:14px 20px;
    width:90%;
    max-width:1500px;
    background:#d1fae5;
    color:#065f46;
    border:1px solid #a7f3d0;
    border-radius:8px;
    font-weight:600;
}

.close-login-banner{
    border:none;
    background:transparent;
    color:inherit;
    font-size:25px;
    line-height:1;
    cursor:pointer;
}

.page-heading{
    text-align:center;
    color:#002147;
    font-size:30px;
    margin-bottom:10px;
}

.welcome-text{
    text-align:center;
    font-size:17px;
    margin-bottom:30px;
    line-height:1.6;
}

.summary-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:20px;
    margin-bottom:35px;
}

.summary-card{
    background:white;
    border-radius:16px;
    padding:22px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    border-top:5px solid #005F73;
    transition:.3s;
}

.summary-card:hover{transform:translateY(-5px)}

.summary-card .icon{
    width:52px;
    height:52px;
    border-radius:50%;
    background:#EAF4F8;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:25px;
    margin-bottom:14px;
}

.summary-card h3{
    font-size:18px;
    color:#002147;
    margin-bottom:8px;
}

.summary-card p{line-height:1.6}

.section-title{
    text-align:center;
    color:#002147;
    font-size:28px;
    margin:35px 0 22px;
}

.feature-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:22px;
}

.feature-card{
    background:white;
    border-top:5px solid #005F73;
    border-radius:16px;
    padding:25px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
    transition:.3s;
    min-width:0;
}

.feature-card:hover{
    background:#F2FBFC;
    transform:translateY(-5px);
}

.feature-card h3{
    font-size:22px;
    color:#002147;
    margin-bottom:12px;
}

.feature-card p{
    line-height:1.7;
    margin-bottom:12px;
}

.feature-card ul{
    padding-left:22px;
    line-height:1.9;
}

.feature-card .feature-icon{
    font-size:30px;
    margin-bottom:12px;
}

.action-link{
    display:inline-block;
    margin-top:15px;
    text-decoration:none;
    color:white;
    background:linear-gradient(135deg,#002147,#005F73);
    padding:10px 22px;
    border-radius:25px;
    font-weight:600;
    transition:.3s;
}

.action-link:hover{
    transform:scale(1.04);
    color:white;
}

.quick-notice{
    margin-top:30px;
    background:white;
    border-left:5px solid #005F73;
    border-radius:12px;
    padding:22px;
    box-shadow:0 5px 15px rgba(0,0,0,.08);
}

.quick-notice h3{
    color:#002147;
    margin-bottom:10px;
}

.quick-notice p{line-height:1.7}

footer{
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
    text-align:center;
    padding:22px;
    margin-top:20px;
}

footer p{margin:6px 0}

/* Dark Mode */

body.dark{
    background:linear-gradient(135deg,#121820,#202a35);
    color:#f1f5f9;
}

body.dark .summary-card,
body.dark .feature-card,
body.dark .quick-notice{
    background:#222b35;
    color:#f1f5f9;
}

body.dark .summary-card h3,
body.dark .feature-card h3,
body.dark .section-title,
body.dark .page-heading,
body.dark .quick-notice h3{
    color:#4dc8e0;
}

body.dark .summary-card .icon{
    background:#333e4b;
}

body.dark .feature-card:hover{
    background:#2b3642;
}

body.dark #themeButton{
    background:#222;
    color:#4dc8e0;
}

body.dark .login-success-banner{
    background:#123d2d;
    color:#b7f7d2;
    border-color:#226747;
}

/* Tablet */

@media(max-width:1100px){
    .summary-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    nav{gap:10px}
    nav a{padding:9px 14px}
}

/* Mobile Navigation */

@media(max-width:768px){
    header{padding:25px 15px}
    header img.logo{width:140px;height:140px}
    header h1{font-size:30px}

    #menuButton{
        display:inline-block;
        margin-top:5px;
    }

    nav{
        display:none;
        flex-direction:column;
        align-items:stretch;
        gap:10px;
        margin-top:15px;
        width:100%;
    }

    nav.show{display:flex}

    nav a{
        width:100%;
        max-width:350px;
        margin:0 auto;
        text-align:center;
    }

    main{
        width:95%;
        padding:25px 0 35px;
    }

    #themeButton{
        top:15px;
        right:15px;
        width:38px;
        height:38px;
        font-size:18px;
    }

    .feature-grid{grid-template-columns:1fr}

    .section-title{font-size:25px}
}

/* Small Mobile */

@media(max-width:520px){
    header img.logo{width:110px;height:110px}
    header h1{font-size:25px;letter-spacing:1px}

    .summary-grid{grid-template-columns:1fr}

    .page-heading{font-size:24px}
    .section-title{font-size:23px}

    .welcome-text{font-size:15px}

    .feature-card{padding:20px}
    .feature-card h3{font-size:20px}

    .summary-card{padding:20px}

    .login-success-banner{
        width:95%;
        font-size:14px;
        padding:12px;
    }

    .quick-notice{padding:17px}

    footer{font-size:14px}
}

/* Accessibility */

button:focus-visible,
a:focus-visible{
    outline:3px solid #4dc8e0;
    outline-offset:3px;
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
            title="Change theme" aria-label="Switch to dark mode">🌙</button>

    <img class="logo" src="logo.jpeg" alt="Student Hub Logo">

    <h1>STUDENT HUB</h1>

    <button id="menuButton" type="button"
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="navigation">☰ Menu</button>

    <nav id="navigation">
        <a href="Home.html">Home</a>
        <a href="register.php">Register</a>
        <a href="Student_dashboard1.php">Student Dashboard</a>
        <a href="Admin.php" class="active">Admin Dashboard</a>
        <a href="Profile.html">Profile</a>
        <a href="Events.html">Events</a>
        <a href="About.html">About Us</a>
        <a href="ContactUs.html">Contact Us</a>
        <a href="Feedback.html">Feedback</a>
        <a href="Location.html">Location</a>
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

        <!-- Event Management:-->
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
            <a class="action-link" href="index.html">API Event Management (Practical 13)</a>
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
                <li>Upload assignments</li>
                <li>Check student submissions</li>
                <li>Enter marks and provide feedback</li>
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

    // Notification banner
    const notification = document.getElementById("notification");
    const closeNotification = document.getElementById("closeNotification");

    // Theme button
    const themeButton = document.getElementById("themeButton");

    // Hamburger menu
    const menuButton = document.getElementById("menuButton");
    const navigation = document.getElementById("navigation");

    // Login success message
    const loginSuccessBanner = document.getElementById("loginSuccessBanner");
    const closeLoginSuccess = document.getElementById("closeLoginSuccess");

    // Restore saved theme from localStorage
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

    // Toggle dark and light mode
    themeButton.addEventListener("click", function () {
        const newTheme = document.body.classList.contains("dark")
            ? "light"
            : "dark";

        applyTheme(newTheme);

        try {
            localStorage.setItem("theme", newTheme);
        } catch (error) {
            // Theme still works if localStorage is unavailable
        }
    });

    // Restore notification visibility
    try {
        if (localStorage.getItem("notificationClosed") === "true") {
            notification.style.display = "none";
        }
    } catch (error) {
        // Notification remains usable without localStorage
    }

    // Close notification and remember the choice
    closeNotification.addEventListener("click", function () {
        notification.style.display = "none";

        try {
            localStorage.setItem("notificationClosed", "true");
        } catch (error) {
            // Closing still works if localStorage is unavailable
        }
    });

    // Hamburger menu open/close
    menuButton.addEventListener("click", function () {
        const isOpen = navigation.classList.toggle("show");

        menuButton.setAttribute("aria-expanded", String(isOpen));

        menuButton.textContent = isOpen ? "✕ Close Menu" : "☰ Menu";
    });

    // Close mobile menu after clicking a navigation link
    navigation.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navigation.classList.remove("show");
            menuButton.setAttribute("aria-expanded", "false");
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