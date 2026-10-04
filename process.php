<?php

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: index.php");

    exit();

}


/* ==============================
   GET FORM DATA
============================== */

$fullName = trim(strip_tags($_POST["fullName"] ?? ""));

$enrollment = trim(strip_tags($_POST["enrollment"] ?? ""));

$email = trim($_POST["email"] ?? "");

$phone = trim(strip_tags($_POST["phone"] ?? ""));

$password = $_POST["password"] ?? "";

$confirmPassword = $_POST["confirmPassword"] ?? "";

$gender = trim(strip_tags($_POST["gender"] ?? ""));

$course = trim(strip_tags($_POST["course"] ?? ""));

$department = trim(strip_tags($_POST["department"] ?? ""));

$year = trim(strip_tags($_POST["year"] ?? ""));

$terms = $_POST["terms"] ?? "";


$errors = [];


/* ==============================
   FULL NAME VALIDATION
============================== */

if ($fullName == "") {

    $errors[] = "Full Name is required.";

}
elseif (!preg_match("/^[a-zA-Z ]+$/", $fullName)) {

    $errors[] = "Full Name should contain only letters and spaces.";

}


/* ==============================
   ENROLLMENT VALIDATION
============================== */

if ($enrollment == "") {

    $errors[] = "Enrollment Number is required.";

}
elseif (!preg_match("/^[a-zA-Z0-9]+$/", $enrollment)) {

    $errors[] = "Enrollment Number should contain only letters and numbers.";

}


/* ==============================
   EMAIL VALIDATION
============================== */

if ($email == "") {

    $errors[] = "Email is required.";

}
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $errors[] = "Please enter a valid email address.";

}


/* ==============================
   PHONE VALIDATION
============================== */

if ($phone == "") {

    $errors[] = "Phone Number is required.";

}
elseif (!preg_match("/^[6-9][0-9]{9}$/", $phone)) {

    $errors[] = "Phone Number must contain exactly 10 digits and start with 6-9.";

}


/* ==============================
   PASSWORD VALIDATION
============================== */

if ($password == "") {

    $errors[] = "Password is required.";

}
elseif (strlen($password) < 8) {

    $errors[] = "Password must be at least 8 characters long.";

}
elseif (!preg_match("/^[A-Z]/", $password)) {

    $errors[] = "Password must start with a capital letter.";

}


/* ==============================
   CONFIRM PASSWORD VALIDATION
============================== */

if ($confirmPassword == "") {

    $errors[] = "Confirm Password is required.";

}
elseif ($password !== $confirmPassword) {

    $errors[] = "Password and Confirm Password do not match.";

}


/* ==============================
   GENDER VALIDATION
============================== */

if ($gender == "") {

    $errors[] = "Please select your gender.";

}


/* ==============================
   COURSE VALIDATION
============================== */

if ($course == "") {

    $errors[] = "Please select your course.";

}


/* ==============================
   DEPARTMENT VALIDATION
============================== */

if ($department == "") {

    $errors[] = "Please select your department.";

}


/* ==============================
   YEAR VALIDATION
============================== */

if ($year == "") {

    $errors[] = "Please select your year.";

}


/* ==============================
   TERMS VALIDATION
============================== */

if ($terms != "accepted") {

    $errors[] = "Please accept the Terms and Conditions.";

}


/* ==============================
   IF VALIDATION ERRORS
============================== */

if (!empty($errors)) {

    $errorString = implode("|", $errors);

    $url = "index.php?error=" . urlencode($errorString);

    $url .= "&fullName=" . urlencode($fullName);

    $url .= "&enrollment=" . urlencode($enrollment);

    $url .= "&email=" . urlencode($email);

    $url .= "&phone=" . urlencode($phone);

    $url .= "&gender=" . urlencode($gender);

    $url .= "&course=" . urlencode($course);

    $url .= "&department=" . urlencode($department);

    $url .= "&year=" . urlencode($year);

    $url .= "&terms=" . urlencode($terms);

    header("Location: " . $url);

    exit();

}


/* ==============================
   SAVE DATA INTO data.csv
============================== */

$csvFile = __DIR__ . "/data.csv";


$file = fopen($csvFile, "a");

if ($file === false) {

    die("Error: Unable to open data.csv file.");

}


/* ==============================
   LOCK FILE
============================== */

if (!flock($file, LOCK_EX)) {

    fclose($file);

    die("Error: Unable to lock data.csv file.");

}


/* ==============================
   ADD HEADER IF CSV IS EMPTY
============================== */

if (filesize($csvFile) == 0) {

    fputcsv($file, [

        "Full Name",
        "Enrollment No",
        "Email",
        "Phone",
        "Password",
        "Gender",
        "Course",
        "Department",
        "Year"

    ]);

}


/* ==============================
   ADD STUDENT DATA
============================== */

fputcsv($file, [

    $fullName,
    $enrollment,
    $email,
    $phone,
    $password,
    $gender,
    $course,
    $department,
    $year

]);


/* ==============================
UNLOCK AND CLOSE FILE
============================== */

flock($file, LOCK_UN);

fclose($file);


/* ==============================
SUCCESS
============================== */

header("Location: index.php?success=1");

exit();

?>