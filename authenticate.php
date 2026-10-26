<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit();
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: login.php?error=" . urlencode("Please enter username and password."));
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, username, password, role
     FROM users
     WHERE username = ?
     LIMIT 1"
);

if (!$stmt) {
    error_log("Login query failed: " . $conn->error);
    header("Location: login.php?error=" . urlencode("Database query failed."));
    exit();
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    error_log("Login execution failed: " . $stmt->error);
    $stmt->close();
    header("Location: login.php?error=" . urlencode("Login failed. Please try again."));
    exit();
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$passwordValid = false;

if ($user) {
    $storedPassword = (string)$user["password"];

    if (password_verify($password, $storedPassword)) {
        $passwordValid = true;
    } elseif (
        $storedPassword !== "" &&
        hash_equals($storedPassword, $password)
    ) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);

        if ($newHash !== false) {
            $update = $conn->prepare(
                "UPDATE users SET password = ? WHERE id = ?"
            );

            if ($update) {
                $update->bind_param("si", $newHash, $user["id"]);

                if ($update->execute()) {
                    $passwordValid = true;
                }

                $update->close();
            }
        }
    }
}

if (!$user || !$passwordValid) {
    header("Location: login.php?error=" . urlencode("Invalid username or password."));
    exit();
}

$role = strtolower(trim($user["role"] ?? ""));

if (!in_array($role, ["admin", "student"], true)) {
    header("Location: login.php?error=" . urlencode("Invalid account role."));
    exit();
}

$updateLogin = $conn->prepare(
    "UPDATE users SET last_login = NOW() WHERE id = ?"
);

if ($updateLogin) {
    $updateLogin->bind_param("i", $user["id"]);
    $updateLogin->execute();
    $updateLogin->close();
}

session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = $role;
$_SESSION["last_activity"] = time();
$_SESSION["login_success"] = true;

require_once "audit_log.php";

writeAuditLog(
    (int)$user["id"],
    $user["username"],
    "LOGIN",
    "User logged in successfully."
);

if ($role === "admin") {
    header("Location: Admin.php");
    exit();
}
else{
header("Location: Student_dashboard1.php");
}
exit();
?>
