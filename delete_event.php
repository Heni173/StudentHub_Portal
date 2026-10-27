
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    strtolower(trim($_SESSION["role"] ?? "")) !== "admin"
) {
    http_response_code(403);
    exit("Access Denied! Only administrators can delete events.");
}

$timeout = 900;

if (
    isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"]) > $timeout
) {
    $_SESSION = [];
    session_destroy();

    header("Location: login.php?error=" . urlencode("Session expired. Please login again."));
    exit();
}

$_SESSION["last_activity"] = time();

require_once "db_events.php";
$eventConn = $conn;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_events.php");
    exit();
}

if (
    !isset($_POST["event_id"]) ||
    !ctype_digit((string)$_POST["event_id"]) ||
    (int)$_POST["event_id"] <= 0
) {
    $_SESSION["event_message"] = "Invalid event ID.";
    $_SESSION["event_message_type"] = "danger";

    header("Location: admin_events.php");
    exit();
}

$eventId = (int)$_POST["event_id"];

// Get event details before deleting.
$stmt = $eventConn->prepare(
    "SELECT title, poster FROM events WHERE event_id = ?"
);

if (!$stmt) {
    error_log("Event lookup prepare failed: " . $eventConn->error);

    $_SESSION["event_message"] = "Unable to find event.";
    $_SESSION["event_message_type"] = "danger";

    header("Location: admin_events.php");
    exit();
}

$stmt->bind_param("i", $eventId);

if (!$stmt->execute()) {
    error_log("Event lookup failed: " . $stmt->error);
    $stmt->close();

    $_SESSION["event_message"] = "Unable to find event.";
    $_SESSION["event_message_type"] = "danger";

    header("Location: admin_events.php");
    exit();
}

$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION["event_message"] = "Event not found.";
    $_SESSION["event_message_type"] = "danger";

    header("Location: admin_events.php");
    exit();
}

// Delete event from studenthub database.
$stmt = $eventConn->prepare(
    "DELETE FROM events WHERE event_id = ?"
);

if (!$stmt) {
    error_log("Event delete prepare failed: " . $eventConn->error);

    $_SESSION["event_message"] = "Unable to delete event.";
    $_SESSION["event_message_type"] = "danger";

    header("Location: admin_events.php");
    exit();
}

$stmt->bind_param("i", $eventId);

$deleted = $stmt->execute() && $stmt->affected_rows > 0;

if (!$deleted) {
    error_log("Event delete failed: " . $stmt->error);
}

$stmt->close();
$eventConn->close();

if ($deleted) {
    // Record deletion in user_registration.audit_logs.
    require_once "db.php";
    $userConn = $conn;

    $userId = (int)($_SESSION["user_id"] ?? 0);
    $username = trim(
        $_SESSION["username"] ??
        $_SESSION["email"] ??
        "admin"
    );

    $action = "DELETE_EVENT";
    $details = "Event deleted: " . $event["title"] .
        " (Event ID: " . $eventId . ")";

    $logStmt = $userConn->prepare(
        "INSERT INTO audit_logs
        (user_id, username, action, details, created_at)
        VALUES (?, ?, ?, ?, NOW())"
    );

    if ($logStmt) {
        $logStmt->bind_param(
            "isss",
            $userId,
            $username,
            $action,
            $details
        );

        if (!$logStmt->execute()) {
            error_log("DELETE_EVENT audit log failed: " . $logStmt->error);
        }

        $logStmt->close();
    } else {
        error_log("DELETE_EVENT audit log prepare failed: " . $userConn->error);
    }

    $userConn->close();

    // Remove the poster image if it exists.
    if (!empty($event["poster"])) {
        $posterPath = __DIR__ . "/uploads/events/" .
            basename($event["poster"]);

        if (is_file($posterPath)) {
            if (!unlink($posterPath)) {
                error_log("Unable to delete event poster: " . $posterPath);
            }
        }
    }

    $_SESSION["event_message"] = "Event deleted successfully!";
    $_SESSION["event_message_type"] = "success";
} else {
    $_SESSION["event_message"] = "Unable to delete event.";
    $_SESSION["event_message_type"] = "danger";
}

header("Location: admin_events.php");
exit();
?>
