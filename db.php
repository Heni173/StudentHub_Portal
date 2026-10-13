
<?php
$host = "localhost";
$dbname = "user_registration";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Database Connection Failed");
}

$conn->set_charset("utf8mb4");
?>
