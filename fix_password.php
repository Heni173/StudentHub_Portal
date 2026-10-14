
<?php
require_once "db.php";

$username = "admin";
$password = "Admin@123";

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "UPDATE users SET password = ? WHERE username = ?"
);

$stmt->bind_param("ss", $hashedPassword, $username);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo "Password successfully updated with hash!";
} else {
    echo "Password update failed. Check the username.";
}

$stmt->close();
$conn->close();
?>
