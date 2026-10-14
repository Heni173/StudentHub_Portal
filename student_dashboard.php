
<?php
require_once "timeout.php";

$role = strtolower(trim((string)($_SESSION["role"] ?? "")));

if ($role !== "student") {
    http_response_code(403);

    echo "Access Denied: Student only.";
    echo "<br>Debug role: " . htmlspecialchars(
        $role === "" ? "EMPTY" : $role,
        ENT_QUOTES,
        "UTF-8"
    );

    exit();
}
?>
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="card shadow p-4">
            <h2>Student Dashboard</h2>
            <p>Welcome, Student!</p>
            <p>You have successfully logged in with the student role.</p>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </main>
</body>
</html>
