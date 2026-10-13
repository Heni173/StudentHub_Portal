
<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.php");
    exit();
}

$errors = [];

$fullName = trim($_POST["fullName"] ?? "");
$enrollment = trim($_POST["enrollment"] ?? "");
$username = trim($_POST["username"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$gender = $_POST["gender"] ?? "";
$course = $_POST["course"] ?? "";
$department = $_POST["department"] ?? "";
$year = $_POST["year"] ?? "";
$terms = $_POST["terms"] ?? "";

/* Keep form values if validation fails */
$old = [
    "fullName" => $fullName,
    "enrollment" => $enrollment,
    "username" => $username,
    "email" => $email,
    "phone" => $phone,
    "gender" => $gender,
    "course" => $course,
    "department" => $department,
    "year" => $year
];

/* Full Name Validation */
if ($fullName === "") {
    $errors[] = "Please enter your full name.";
} elseif (!preg_match("/^[A-Za-z ]+$/", $fullName)) {
    $errors[] = "Full name must contain only letters and spaces.";
} elseif (strlen($fullName) > 100) {
    $errors[] = "Full name is too long.";
}

/* Enrollment Validation */
if ($enrollment === "") {
    $errors[] = "Please enter your enrollment number.";
} elseif (!preg_match("/^[A-Za-z0-9]+$/", $enrollment)) {
    $errors[] = "Enrollment number can contain only letters and numbers.";
} elseif (strlen($enrollment) > 50) {
    $errors[] = "Enrollment number is too long.";
}

/* Username Validation */
if ($username === "") {
    $errors[] = "Please enter your username.";
} elseif (!preg_match("/^[A-Za-z0-9_]{3,50}$/", $username)) {
    $errors[] = "Username must contain 3-50 letters, numbers or underscores.";
}

/* Email Validation */
if ($email === "") {
    $errors[] = "Please enter your email address.";
} elseif (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

/* Phone Validation */
if (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {
    $errors[] = "Enter a valid 10-digit phone number.";
}

/* Password Validation */
if ($password === "") {
    $errors[] = "Please enter your password.";
} elseif (!preg_match("/^[A-Z]/", $password)) {
    $errors[] = "Password must start with a capital letter.";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must contain at least 8 characters.";
} elseif (
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
) {
    $errors[] = "Password must contain a lowercase letter, a number and a special character.";
}

/* Confirm Password Validation */
if ($confirmPassword === "") {
    $errors[] = "Please confirm your password.";
} elseif ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

/* Gender Validation */
if (!in_array($gender, ["Male", "Female"], true)) {
    $errors[] = "Please select your gender.";
}

/* Course Validation */
if (!in_array($course, ["B.Tech/BE", "M.Tech/ME"], true)) {
    $errors[] = "Please select a valid course.";
}

/* Department Validation */
$validDepartments = [
    "Computer Engineering",
    "Information Technology",
    "Mechanical Engineering",
    "Civil Engineering",
    "Electrical Engineering"
];

if (!in_array($department, $validDepartments, true)) {
    $errors[] = "Please select a valid department.";
}

/* Year Validation */
$validYears = ["1st Year", "2nd Year", "3rd Year", "4th Year"];

if (!in_array($year, $validYears, true)) {
    $errors[] = "Please select a valid year.";
}

/* Terms Validation */
if ($terms !== "accepted") {
    $errors[] = "Please accept the Terms and Conditions.";
}

/* Return to form if validation fails */
if (!empty($errors)) {
    $_SESSION["old"] = $old;
    $_SESSION["errors"] = $errors;

    header("Location: register.php?status=error");
    exit();
}

/* Check duplicate username, email or enrollment */
$checkSql = "SELECT id FROM users
             WHERE username = ? OR email = ? OR enrollment = ?
             LIMIT 1";

$check = $conn->prepare($checkSql);

if (!$check) {
    error_log("Duplicate check prepare failed: " . $conn->error);

    $_SESSION["old"] = $old;
    $_SESSION["errors"] = ["A server error occurred. Please try again."];

    header("Location: register.php?status=error");
    exit();
}

$check->bind_param("sss", $username, $email, $enrollment);

if (!$check->execute()) {
    error_log("Duplicate check execute failed: " . $check->error);
    $check->close();

    $_SESSION["old"] = $old;
    $_SESSION["errors"] = ["A server error occurred. Please try again."];

    header("Location: register.php?status=error");
    exit();
}

$check->store_result();

if ($check->num_rows > 0) {
    $check->close();

    $_SESSION["old"] = $old;
    $_SESSION["errors"] = [
        "Username, email or enrollment number is already registered."
    ];

    header("Location: register.php?status=error");
    exit();
}

$check->close();

/* Hash password before storing it */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

if ($hashedPassword === false) {
    $_SESSION["old"] = $old;
    $_SESSION["errors"] = ["Password processing failed. Please try again."];

    header("Location: register.php?status=error");
    exit();
}

/* Insert registration data
   Note: database column is full_name, not fullName */
$insertSql = "INSERT INTO users
    (full_name, enrollment, username, email, phone, password,
     gender, course, department, year)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insert = $conn->prepare($insertSql);

if (!$insert) {
    error_log("Insert prepare failed: " . $conn->error);

    $_SESSION["old"] = $old;
    $_SESSION["errors"] = ["A server error occurred. Please try again."];

    header("Location: register.php?status=error");
    exit();
}

$insert->bind_param(
    "ssssssssss",
    $fullName,
    $enrollment,
    $username,
    $email,
    $phone,
    $hashedPassword,
    $gender,
    $course,
    $department,
    $year
);

/* Execute insert */
if ($insert->execute()) {
    $insert->close();
    $conn->close();

    unset($_SESSION["old"], $_SESSION["errors"]);

    header("Location: register.php?status=success");
    exit();
}

/* Handle duplicate or other database errors */
$errorNumber = $insert->errno;
error_log("Registration insert failed: " . $insert->error);

$insert->close();
$conn->close();

$_SESSION["old"] = $old;

if ($errorNumber === 1062) {
    $_SESSION["errors"] = [
        "Username, email or enrollment number is already registered."
    ];
} else {
    $_SESSION["errors"] = [
        "Registration could not be completed. Please check the database settings."
    ];
}

header("Location: register.php?status=error");
exit();
?>
