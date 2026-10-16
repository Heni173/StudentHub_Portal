
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
) {
    http_response_code(403);
    exit("Access Denied! Only administrators can edit events.");
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

function escapeOutput($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$uploadDirectory = __DIR__ . "/uploads/events/";

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

if (!isset($_GET["id"]) || !ctype_digit($_GET["id"])) {
    header("Location: admin_events.php");
    exit();
}

$eventId = (int)$_GET["id"];

$stmt = $conn->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->bind_param("i", $eventId);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    exit("Event not found!");
}

$title = $event["title"];
$description = $event["description"];
$eventDate = $event["event_date"];
$category = $event["category"];
$status = $event["status"];
$poster = $event["poster"];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $category = trim($_POST["category"] ?? "");
    $status = $_POST["status"] ?? "";

    if (
        $title === "" ||
        $description === "" ||
        $eventDate === "" ||
        $category === "" ||
        !in_array($status, ["open", "closed"], true)
    ) {
        $error = "Please fill in all fields correctly.";
    } elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $eventDate)) {
        $error = "Please enter a valid event date.";
    } else {
        $dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

        if (!$dateObject || $dateObject->format("Y-m-d") !== $eventDate) {
            $error = "Please enter a valid event date.";
        }
    }

    $newPoster = $poster;

    if ($error === "" && isset($_FILES["poster"]) &&
        $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES["poster"]["error"] !== UPLOAD_ERR_OK) {
            $error = "Poster upload failed. Please try again.";
        } elseif ($_FILES["poster"]["size"] > 2 * 1024 * 1024) {
            $error = "Poster size must be less than or equal to 2 MB.";
        } else {
            $tmpName = $_FILES["poster"]["tmp_name"];
            $imageInfo = getimagesize($tmpName);

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpName);
            finfo_close($finfo);

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png"
            ];

            if (
                $imageInfo === false ||
                !isset($allowedTypes[$mimeType]) ||
                !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)
            ) {
                $error = "Only JPG, JPEG, and PNG image files are allowed.";
            } else {
                $newPoster = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];

                if (!move_uploaded_file($tmpName, $uploadDirectory . $newPoster)) {
                    $error = "Unable to save the uploaded poster.";
                }
            }
        }
    }

    if ($error === "") {
        $stmt = $conn->prepare(
            "UPDATE events
             SET title = ?, description = ?, event_date = ?,
                 category = ?, status = ?, poster = ?
             WHERE event_id = ?"
        );

        $stmt->bind_param(
            "ssssssi",
            $title,
            $description,
            $eventDate,
            $category,
            $status,
            $newPoster,
            $eventId
        );

        if ($stmt->execute()) {
            $stmt->close();

            if ($newPoster !== $poster && !empty($poster)) {
                $oldPosterPath = $uploadDirectory . basename($poster);

                if (is_file($oldPosterPath)) {
                    unlink($oldPosterPath);
                }
            }

            $_SESSION["event_message"] = "Event updated successfully!";
            header("Location: admin_events.php");
            exit();
        } else {
            if ($newPoster !== $poster && is_file($uploadDirectory . $newPoster)) {
                unlink($uploadDirectory . $newPoster);
            }

            $error = "Unable to update event. Please try again.";
            $stmt->close();
        }
    }

    if ($newPoster !== $poster && $error !== "" &&
        isset($newPoster) && is_file($uploadDirectory . $newPoster)) {
        unlink($uploadDirectory . $newPoster);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Student Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #EAF4F8;
            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 750px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 33, 71, 0.12);
        }

        h2 {
            color: #002147;
            text-align: center;
            margin-bottom: 25px;
        }

        .btn-save {
            background: #005F73;
            color: white;
        }

        .btn-save:hover {
            background: #004653;
            color: white;
        }

        .current-poster {
            max-width: 180px;
            max-height: 180px;
            object-fit: contain;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 5px;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="form-container">
        <h2>Edit Event</h2>

        <?php if ($error !== ""): ?>
            <div class="alert alert-danger">
                <?php echo escapeOutput($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Event Title</label>
                <input type="text" name="title" class="form-control"
                       maxlength="150" required
                       value="<?php echo escapeOutput($title); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"
                          rows="4" required><?php echo escapeOutput($description); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Event Date</label>
                <input type="date" name="event_date" class="form-control"
                       required value="<?php echo escapeOutput($eventDate); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control"
                       maxlength="100" required
                       value="<?php echo escapeOutput($category); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Event Status</label>
                <select name="status" class="form-select" required>
                    <option value="open" <?php echo $status === "open" ? "selected" : ""; ?>>
                        Open
                    </option>
                    <option value="closed" <?php echo $status === "closed" ? "selected" : ""; ?>>
                        Closed
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Poster</label><br>

                <?php if (!empty($poster) && is_file($uploadDirectory . basename($poster))): ?>
                    <img
                        src="uploads/events/<?php echo rawurlencode(basename($poster)); ?>"
                        alt="Current event poster"
                        class="current-poster">
                <?php else: ?>
                    <p>No poster uploaded.</p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label">Change Poster (Optional)</label>
                <input type="file" name="poster" class="form-control"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                <small class="text-muted">JPG, JPEG or PNG. Maximum size: 2 MB.</small>
            </div>

            <button type="submit" class="btn btn-save w-100">
                Update Event
            </button>

            <a href="admin_events.php" class="btn btn-secondary w-100 mt-2">
                Cancel
            </a>
        </form>
    </div>
</div>
</body>
</html>
