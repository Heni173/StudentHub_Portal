
<?php

function writeAuditLog($userId, $username, $action, $details = "")
{
    $auditConn = new mysqli(
        "localhost",
        "root",
        "",
        "user_registration"
    );

    if ($auditConn->connect_error) {
        error_log("Audit database connection failed.");
        return false;
    }

    $auditConn->set_charset("utf8mb4");

    $stmt = $auditConn->prepare(
        "INSERT INTO audit_logs
         (user_id, username, action, details)
         VALUES (?, ?, ?, ?)"
    );

    if (!$stmt) {
        $auditConn->close();
        return false;
    }

    $stmt->bind_param(
        "isss",
        $userId,
        $username,
        $action,
        $details
    );

    $success = $stmt->execute();

    $stmt->close();
    $auditConn->close();

    return $success;
}
?>
