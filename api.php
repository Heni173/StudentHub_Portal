<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "api_db.php";

function sendResponse($success, $message, $data = null, $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function getInput()
{
    $input = json_decode(file_get_contents("php://input"), true);

    if (!is_array($input)) {
        sendResponse(false, "Invalid JSON request.", null, 400);
    }

    return $input;
}

function validateEvent($input)
{
    $title = trim($input["title"] ?? "");
    $description = trim($input["description"] ?? "");
    $eventDate = trim($input["event_date"] ?? "");
    $category = trim($input["category"] ?? "");
    $status = trim($input["status"] ?? "open");

    if ($title === "" || $description === "" ||
        $eventDate === "" || $category === "") {
        sendResponse(false, "Please fill in all required fields.", null, 400);
    }

    if (strlen($title) > 150 || strlen($category) > 100) {
        sendResponse(false, "Title or category is too long.", null, 400);
    }

    $date = DateTime::createFromFormat("!Y-m-d", $eventDate);

    if (!$date || $date->format("Y-m-d") !== $eventDate) {
        sendResponse(false, "Please enter a valid event date.", null, 400);
    }

    if (!in_array($status, ["open", "closed"], true)) {
        sendResponse(false, "Invalid event status.", null, 400);
    }

    return [
        $title,
        $description,
        $eventDate,
        $category,
        $status
    ];
}

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {

    $id = filter_var(
        $_GET["id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (isset($_GET["id"]) && (!$id || $id < 1)) {
        sendResponse(false, "Invalid event ID.", null, 400);
    }

    if ($id) {
        $stmt = $conn->prepare(
            "SELECT event_id, title, description, event_date,
                    category, status
             FROM events
             WHERE event_id = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        $stmt->close();

        if (!$event) {
            sendResponse(false, "Event not found.", null, 404);
        }

        sendResponse(true, "Event fetched successfully.", $event);
    }

    $result = $conn->query(
        "SELECT event_id, title, description, event_date,
                category, status
         FROM events
         ORDER BY event_id DESC"
    );

    if (!$result) {
        sendResponse(false, "Unable to fetch events.", null, 500);
    }

    $events = [];

    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    sendResponse(true, "Events fetched successfully.", $events);
}

if ($method === "POST") {

    $input = getInput();
    $action = $input["action"] ?? "";

    if ($action === "add") {

        $values = validateEvent($input);

        $stmt = $conn->prepare(
            "INSERT INTO events
             (title, description, event_date, category, status)
             VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "sssss",
            $values[0],
            $values[1],
            $values[2],
            $values[3],
            $values[4]
        );

        if (!$stmt->execute()) {
            $stmt->close();
            sendResponse(false, "Unable to add event.", null, 500);
        }

        $newId = $conn->insert_id;
        $stmt->close();

        sendResponse(true, "Event added successfully.", [
            "event_id" => $newId
        ], 201);
    }

    if ($action === "update") {

        $id = filter_var(
            $input["event_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            sendResponse(false, "Invalid event ID.", null, 400);
        }

        $values = validateEvent($input);

        $stmt = $conn->prepare(
            "UPDATE events
             SET title = ?, description = ?, event_date = ?,
                 category = ?, status = ?
             WHERE event_id = ?"
        );

        $stmt->bind_param(
            "sssssi",
            $values[0],
            $values[1],
            $values[2],
            $values[3],
            $values[4],
            $id
        );

        if (!$stmt->execute()) {
            $stmt->close();
            sendResponse(false, "Unable to update event.", null, 500);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            $check = $conn->prepare(
                "SELECT event_id FROM events WHERE event_id = ?"
            );

            $check->bind_param("i", $id);
            $check->execute();

            $exists = $check->get_result()->num_rows > 0;
            $check->close();

            if (!$exists) {
                sendResponse(false, "Event not found.", null, 404);
            }
        }

        sendResponse(true, "Event updated successfully.");
    }

    if ($action === "delete") {

        $id = filter_var(
            $input["event_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$id || $id < 1) {
            sendResponse(false, "Invalid event ID.", null, 400);
        }

        $stmt = $conn->prepare(
            "DELETE FROM events WHERE event_id = ?"
        );

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            $stmt->close();
            sendResponse(false, "Unable to delete event.", null, 500);
        }

        $deleted = $stmt->affected_rows;
        $stmt->close();

        if ($deleted === 0) {
            sendResponse(false, "Event not found.", null, 404);
        }

        sendResponse(true, "Event deleted successfully.");
    }

    sendResponse(false, "Invalid action.", null, 400);
}

header("Allow: GET, POST");

sendResponse(false, "Method not allowed.", null, 405);
?>