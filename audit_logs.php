
<?php
require_once "timeout.php";

if (strtolower(trim($_SESSION["role"] ?? "")) !== "admin") {
    http_response_code(403);
    exit("Access Denied: Admin only.");
}

require_once "db.php";

$result = $conn->query(
    "SELECT log_id, user_id, username, action, details, created_at
     FROM audit_logs
     ORDER BY log_id DESC
     LIMIT 100"
);

if (!$result) {
    die("Unable to load audit logs.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <h2 class="mb-4">Admin Audit Logs</h2>

        <div class="table-responsive">
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Log ID</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Date &amp; Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= (int)$row["log_id"] ?></td>
                        <td><?= htmlspecialchars((string)($row["user_id"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["username"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["action"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["details"] ?? "", ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($row["created_at"], ENT_QUOTES, "UTF-8") ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <a href="Admin.php" class="btn btn-primary">Back to Dashboard</a>
    </main>
</body>
</html>
