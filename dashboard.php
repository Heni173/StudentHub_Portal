<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?error=" . urlencode("Please login first."));
    exit;
}

if (!isset($_SESSION["role"])) {
    header("Location: logout.php");
    exit;
}

$role = strtolower($_SESSION["role"]);

if ($role === "admin") {
    header("Location: admin_dashboard.php");
} elseif ($role === "student") {
    header("Location: student_dashboard.php");
} else {
    header("Location: logout.php");
}

exit;
?>
