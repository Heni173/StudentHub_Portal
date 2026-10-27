<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || strtolower($_SESSION["role"] ?? "") !== "student") {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION["user_id"];

$stmt = $conn->prepare(
    "SELECT full_name, username, enrollment, email, phone,
            gender, course, department, year, role
     FROM users
     WHERE id = ? AND role = 'student'
     LIMIT 1"
);

if (!$stmt) {
    die("Unable to load profile. Please check the database connection.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: logout.php");
    exit();
}

function profileValue($value) {
    if ($value === null || trim((string)$value) === "") {
        return "Not Available";
    }

    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

/* ---- Academic details (CGPA & Attendance) ---- */
$cgpaDisplay = "Not Available";
$attendanceDisplay = "Not Available";

$academicStmt = $conn->prepare(
    "SELECT cgpa, attendance
     FROM academic_details
     WHERE user_id = ?
     LIMIT 1"
);

if ($academicStmt) {
    $academicStmt->bind_param("i", $userId);
    $academicStmt->execute();
    $academicRow = $academicStmt->get_result()->fetch_assoc();
    $academicStmt->close();

    if ($academicRow) {
        if ($academicRow["cgpa"] !== null && $academicRow["cgpa"] !== "") {
            $cgpaDisplay = number_format((float)$academicRow["cgpa"], 2);
        }

        if ($academicRow["attendance"] !== null && $academicRow["attendance"] !== "") {
            $attendanceDisplay = number_format((float)$academicRow["attendance"], 2) . "%";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Hub - Profile</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

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
            transition: 0.3s;
        }

        header {
            position: relative;
            background: linear-gradient(135deg, #002147, #4dc8e0);
            color: white;
            text-align: center;
            padding: 30px 20px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        }

        header img {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid rgb(11, 0, 0);
            padding: 5px;
            background: white;
            transition: 0.3s;
        }

        header img:hover {
            transform: scale(1.05);
        }

        header h2 {
            margin: 15px 0;
            font-size: 36px;
        }

        header h3 {
            font-size: 28px;
        }

        #themeButton {
            position: absolute;
            top: 20px;
            right: 25px;
            width: 45px;
            height: 45px;
            border: none;
            border-radius: 50%;
            background: white;
            color: #002147;
            font-size: 21px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
            transition: 0.3s;
        }

        #themeButton:hover {
            transform: scale(1.1);
        }

        #menuButton {
            display: none;
            margin: 15px auto 0;
            border: none;
            background: white;
            color: #002147;
            padding: 10px 22px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        nav {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        nav a {
            text-decoration: none;
            color: white;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 18px;
            border-radius: 25px;
            font-weight: 600;
            transition: 0.3s;
        }

        nav a:hover,
        nav a.active {
            background: white;
            color: #002147;
            transform: translateY(-2px);
        }

        main {
            width: 90%;
            margin: auto;
            padding: 45px 0;
        }

        .profile-box {
            background: white;
            width: 900px;
            max-width: 95%;
            margin: auto;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            text-align: center;
            transition: 0.3s;
        }

        .profile-box:hover {
            transform: translateY(-5px);
        }

        .profile-box h3 {
            color: #002147;
            font-size: 32px;
            margin-bottom: 25px;
        }

        .profile-table {
            width: 100%;
            border-collapse: collapse;
        }

        .profile-table th {
            background: #002147;
            color: white;
            padding: 18px;
            font-size: 22px;
        }

        .profile-table td {
            border: 1px solid #ddd;
            padding: 16px;
            font-size: 20px;
            overflow-wrap: anywhere;
        }

        .profile-table td:first-child {
            font-weight: bold;
            color: #002147;
            width: 40%;
        }

        .profile-table tr {
            transition: 0.3s;
        }

        .profile-table tr:hover {
            background: #EAF4F8;
        }

        .info-box {
            background: white;
            width: 900px;
            max-width: 95%;
            margin: 30px auto;
            padding: 30px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            transition: 0.3s;
        }

        .info-box:hover {
            transform: translateY(-5px);
        }

        .info-box h3 {
            color: #002147;
            font-size: 30px;
            margin-bottom: 20px;
        }

        .academic-details {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .academic-card {
            background: #F2FBFC;
            border-radius: 15px;
            padding: 20px;
            min-width: 180px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .academic-card h4 {
            color: #005F73;
            margin-bottom: 8px;
        }

        .academic-card p {
            font-size: 20px;
            font-weight: bold;
            overflow-wrap: anywhere;
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

        body.dark {
            background: #121212;
            color: white;
        }

        body.dark .profile-box,
        body.dark .info-box {
            background: #1e1e1e;
            color: white;
        }

        body.dark .profile-box h3,
        body.dark .info-box h3 {
            color: #4dc8e0;
        }

        body.dark .profile-table td {
            border-color: #444;
            color: white;
        }

        body.dark .profile-table td:first-child {
            color: #4dc8e0;
        }

        body.dark .profile-table tr:hover {
            background: #292929;
        }

        body.dark .academic-card {
            background: #292929;
            color: white;
        }

        body.dark .academic-card h4 {
            color: #4dc8e0;
        }

        body.dark #themeButton {
            background: #292929;
            color: #4dc8e0;
        }

        @media(max-width: 700px) {
            #themeButton {
                top: 15px;
                right: 15px;
            }

            #menuButton {
                display: block;
            }

            #navigation {
                display: none;
                flex-direction: column;
                align-items: center;
            }

            #navigation.show {
                display: flex;
            }

            nav a {
                width: 220px;
                text-align: center;
            }

            header img {
                width: 140px;
                height: 140px;
            }

            header h2 {
                font-size: 30px;
            }

            header h3 {
                font-size: 23px;
            }

            .profile-box,
            .info-box {
                width: 95%;
                padding: 20px;
            }

            .profile-table th,
            .profile-table td {
                font-size: 15px;
                padding: 10px;
            }

            .academic-card {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <header>
        <button id="themeButton" type="button" title="Change Theme">🌙</button>

        <img src="logo.jpeg" alt="Student Hub Logo">

        <h2>STUDENT HUB</h2>
        <h3>My Profile - <?= profileValue($student["full_name"]) ?></h3>

        <button id="menuButton" type="button">☰ Menu</button>

        <nav id="navigation">
            <a href="Home.php">Home</a>
            <a href="register.php">Register</a>
            <a href="Student_dashboard1.php">Dashboard</a>
            <a href="Profile.php" class="active">Profile</a>
            <a href="Events.php">Events</a>
            <a href="About.html">About Us</a>
            <a href="ContactUs.html">Contact Us</a>
            <a href="Feedback.html">Feedback</a>
            <a href="Location.html">Location</a>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main>
        <section class="profile-box">
            <h3>👤 Student Information</h3>

            <table class="profile-table">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Details</th>
                    </tr>
                </thead>

                <tbody id="profileBody">
                    <tr>
                        <td>Full Name</td>
                        <td><?= profileValue($student["full_name"]) ?></td>
                    </tr>
                    <tr>
                        <td>Username</td>
                        <td><?= profileValue($student["username"]) ?></td>
                    </tr>
                    <tr>
                        <td>Enrollment Number</td>
                        <td><?= profileValue($student["enrollment"]) ?></td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><?= profileValue($student["email"]) ?></td>
                    </tr>
                    <tr>
                        <td>Phone</td>
                        <td><?= profileValue($student["phone"]) ?></td>
                    </tr>
                    <tr>
                        <td>Gender</td>
                        <td><?= profileValue($student["gender"]) ?></td>
                    </tr>
                    <tr>
                        <td>Course</td>
                        <td><?= profileValue($student["course"]) ?></td>
                    </tr>
                    <tr>
                        <td>Department</td>
                        <td><?= profileValue($student["department"]) ?></td>
                    </tr>
                    <tr>
                        <td>Year</td>
                        <td><?= profileValue($student["year"]) ?></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="info-box">
            <h3>📊 Academic Details</h3>

            <div class="academic-details">
                <div class="academic-card">
                    <h4>📈 CGPA</h4>
                    <p><?= htmlspecialchars($cgpaDisplay, ENT_QUOTES, "UTF-8") ?></p>
                </div>

                <div class="academic-card">
                    <h4>📅 Attendance</h4>
                    <p><?= htmlspecialchars($attendanceDisplay, ENT_QUOTES, "UTF-8") ?></p>
                </div>

                <div class="academic-card">
                    <h4>✓ Account Role</h4>
                    <p><?= profileValue(ucfirst($student["role"])) ?></p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p><b>Student Hub</b></p>
        <p>Learn • Explore • Grow</p>
        <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
        <p>Designed &amp; Developed by Heni Patel</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="Profile.js"></script>

</body>
</html>