<?php
require_once "timeout.php";

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}

$conn = new mysqli("localhost", "root", "", "studenthub");

if ($conn->connect_error) {
    http_response_code(500);
    exit("Database connection failed.");
}

$conn->set_charset("utf8mb4");

$tableCheck = $conn->query("SHOW TABLES LIKE 'feedbacks'");

$feedbacks = null;
$errorMessage = "";

if (!$tableCheck || $tableCheck->num_rows === 0) {
    $errorMessage = "No feedback table found yet. Submit feedback from the student page first.";
} else {
    $feedbacks = $conn->query(
        "SELECT id, name, email, feedback, submitted_at
         FROM feedbacks
         ORDER BY submitted_at DESC"
    );

    if (!$feedbacks) {
        $errorMessage = "Unable to load feedbacks.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Hub - View Feedbacks</title>

<style>
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Segoe UI',Tahoma,sans-serif;
}

body{
    background:linear-gradient(135deg,#F8FAFC,#EAF4F8);
    color:#243447;
    min-height:100vh;
    transition:0.3s;
}

/* HEADER */

header{
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
    text-align:center;
    padding:30px 20px;
    box-shadow:0 5px 15px rgba(0,0,0,0.15);
    position:relative;
}

header img{
    width:180px;
    height:180px;
    object-fit:cover;
    border-radius:50%;
    border:5px solid #0e0101;
    padding:5px;
    background:white;
}

header h1{
    margin:15px 0;
    font-size:32px;
}

header h2{
    font-size:23px;
}

/* THEME BUTTON */

#themeButton{
    position:absolute;
    top:20px;
    right:25px;
    width:42px;
    height:42px;
    border:0;
    border-radius:50%;
    background:white;
    color:#002147;
    font-size:20px;
    cursor:pointer;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
    transition:0.3s;
}

#themeButton:hover{
    transform:scale(1.1);
}

/* NAVIGATION */

nav{
    display:flex;
    justify-content:center;
    gap:10px;
    flex-wrap:wrap;
    margin-top:20px;
}

nav a{
    color:white;
    background:rgba(255,255,255,0.15);
    text-decoration:none;
    padding:10px 16px;
    border-radius:25px;
    font-weight:600;
    transition:0.3s;
}

nav a:hover,
nav a.active{
    background:white;
    color:#002147;
}

/* MAIN */

main{
    width:94%;
    max-width:1200px;
    margin:35px auto;
}

.panel{
    background:white;
    padding:25px;
    border-radius:16px;
    box-shadow:0 6px 18px rgba(0,0,0,0.12);
    transition:0.3s;
}

.panel h2{
    color:#002147;
    margin-bottom:8px;
}

.subtitle{
    color:#52616b;
    margin-bottom:20px;
}

/* FEEDBACK TABLE */

.table-wrap{
    width:100%;
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:750px;
}

th,
td{
    padding:13px;
    text-align:left;
    border-bottom:1px solid #dce5eb;
    vertical-align:top;
}

th{
    background:#002147;
    color:white;
}

tbody tr:nth-child(even){
    background:#f1f7fa;
}

.feedback-text{
    white-space:pre-wrap;
    overflow-wrap:anywhere;
    min-width:200px;
    max-width:450px;
}

.message{
    padding:14px;
    border-radius:9px;
    background:#fff3cd;
    color:#664d03;
    margin:15px 0;
}

/* FOOTER */

footer{
    margin-top:40px;
    padding:20px;
    text-align:center;
    background:linear-gradient(135deg,#002147,#4dc8e0);
    color:white;
}

footer p{
    margin:5px;
}

/* DARK MODE */

body.dark{
    background:#121212;
    color:#f1f1f1;
}

body.dark .panel{
    background:#1e1e1e;
    color:#f1f1f1;
}

body.dark .panel h2{
    color:#4dc8e0;
}

body.dark .subtitle{
    color:#ccc;
}

body.dark th{
    background:#005F73;
}

body.dark td{
    border-color:#444;
}

body.dark tbody tr:nth-child(even){
    background:#292929;
}

body.dark tbody tr:nth-child(odd){
    background:#1e1e1e;
}

/* MOBILE RESPONSIVE */

@media(max-width:600px){

    header{
        padding:25px 12px;
    }

    header img{
        width:140px;
        height:140px;
    }

    header h1{
        font-size:25px;
    }

    header h2{
        font-size:20px;
    }

    nav{
        flex-direction:column;
        align-items:center;
    }

    nav a{
        width:220px;
        text-align:center;
    }

    #themeButton{
        position:static;
        margin-bottom:15px;
    }

    main{
        width:95%;
        margin:20px auto;
    }

    .panel{
        padding:15px;
    }
}
</style>
</head>

<body>

<!-- HEADER -->

<header>

    <button id="themeButton" type="button"
            aria-label="Change theme" title="Change theme">
        🌙
    </button>

    <img src="logo.jpeg" alt="Student Hub Logo">

    <h1>STUDENT HUB</h1>

    <h2>View Student Feedbacks</h2>

    <!-- NAVIGATION -->

    <nav>
        <a href="Admin.php">Dashboard</a>
        <a href="students.php">Students</a>
        <a href="academic_management.php">Academics</a>
        <a href="admin_events.php">Events</a>
        <a href="assignments.php">Assignments</a>
        <a href="announcements.php">Notices</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="view_feedbacks.php" class="active">View Feedbacks</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<!-- MAIN CONTENT -->

<main>

    <section class="panel">

        <h2>Student Feedbacks</h2>

        <p class="subtitle">
            View feedback submitted through the Student Hub feedback form.
        </p>

        <?php if ($errorMessage !== ""): ?>

            <div class="message">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php elseif ($feedbacks && $feedbacks->num_rows > 0): ?>

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Feedback</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($row = $feedbacks->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= (int)$row["id"] ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["name"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["email"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td class="feedback-text">
                                    <?= htmlspecialchars($row["feedback"], ENT_QUOTES, "UTF-8") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row["submitted_at"], ENT_QUOTES, "UTF-8") ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="message">
                No feedback has been submitted yet.
            </div>

        <?php endif; ?>

    </section>

</main>

<!-- FOOTER -->

<footer>
    <p><b>Student Hub</b></p>
    <p>Learn • Explore • Grow</p>
    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
    <p>Designed &amp; Developed by Heni Patel</p>
</footer>

<!-- DARK MODE SCRIPT -->

<script>
const themeButton = document.getElementById("themeButton");

if (localStorage.getItem("theme") === "dark") {
    document.body.classList.add("dark");
    themeButton.textContent = "☀️";
}

themeButton.addEventListener("click", function () {
    document.body.classList.toggle("dark");

    const isDark = document.body.classList.contains("dark");

    themeButton.textContent = isDark ? "☀️" : "🌙";

    localStorage.setItem("theme", isDark ? "dark" : "light");
});
</script>

</body>
</html>

<?php
$conn->close();
?>