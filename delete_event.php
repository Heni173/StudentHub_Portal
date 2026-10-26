
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_events.php");
    exit();
}

if (!isset($_POST["event_id"]) || !ctype_digit($_POST["event_id"])) {
    $_SESSION["event_message"] = "Invalid event ID.";
    header("Location: admin_events.php");
    exit();
}

$eventId = (int)$_POST["event_id"];

// Find the event title and poster before deleting
$stmt = $conn->prepare(
    "SELECT title, poster FROM events WHERE event_id = ?"
);
$stmt->bind_param("i", $eventId);
$stmt->execute();

$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    $_SESSION["event_message"] = "Event not found.";
    header("Location: admin_events.php");
    exit();
}

// Delete the event from the events database
$stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
$stmt->bind_param("i", $eventId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $stmt->close();

    // Record the successful deletion in user_registration.audit_logs
    require_once "db.php";

    $userId = (int)($_SESSION["user_id"] ?? 0);
    $username = trim(
        $_SESSION["username"] ??
        $_SESSION["email"] ??
        "admin"
    );
    $action = "DELETE_EVENT";
    $details = "Event deleted: " . $event["title"] .
               " (Event ID: " . $eventId . ")";

    $logStmt = $conn->prepare(
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
        error_log("DELETE_EVENT audit log prepare failed: " . $conn->error);
    }

    $conn->close();

    // Delete the poster image, if one exists
    if (!empty($event["poster"])) {
        $posterPath = __DIR__ . "/uploads/events/" . basename($event["poster"]);

        if (is_file($posterPath)) {
            unlink($posterPath);
        }
    }

    $_SESSION["event_message"] = "Event deleted successfully!";
} else {
    $_SESSION["event_message"] = "Unable to delete event.";
    $stmt->close();
}

header("Location: admin_events.php");
exit();
?>
