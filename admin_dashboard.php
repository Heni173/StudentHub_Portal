
<?php
require_once "timeout.php";

if (strtolower($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <div class="card shadow p-4">
            <h2>Admin Dashboard</h2>
            <p>Welcome, Admin!</p>
            <p>You have successfully logged in with the admin role.</p>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </main>
</body>
</html>
