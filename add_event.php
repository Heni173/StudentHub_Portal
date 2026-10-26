
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    strtolower(trim($_SESSION["role"] ?? "")) !== "admin"
) {
    http_response_code(403);
    exit("Access Denied! Only administrators can add events.");
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

$message = "";
$messageType = "";

$title = "";
$description = "";
$eventDate = "";
$category = "General";
$status = "open";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = trim($_POST["event_date"] ?? "");
    $category = trim($_POST["category"] ?? "General");
    $status = $_POST["status"] ?? "open";

    if ($title === "" || $description === "" || $eventDate === "" || $category === "") {
        $message = "Please fill in all required fields.";
        $messageType = "danger";
    } elseif (!in_array($status, ["open", "closed"], true)) {
        $message = "Invalid event status.";
        $messageType = "danger";
    } else {
        $dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

        if (!$dateObject || $dateObject->format("Y-m-d") !== $eventDate) {
            $message = "Please enter a valid event date.";
            $messageType = "danger";
        }
    }

    $posterName = null;
    $uploadedPath = null;

    if ($message === "" && isset($_FILES["poster"]) &&
        $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE) {

        $file = $_FILES["poster"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            $message = "Poster upload failed. Please try again.";
            $messageType = "danger";
        } elseif ($file["size"] > 2 * 1024 * 1024) {
            $message = "Poster size must not exceed 2 MB.";
            $messageType = "danger";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file["tmp_name"]);

            $allowedTypes = [
                "image/jpeg" => "jpg",
                "image/png" => "png"
            ];

            $imageInfo = @getimagesize($file["tmp_name"]);

            if (
                !isset($allowedTypes[$mimeType]) ||
                $imageInfo === false ||
                !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)
            ) {
                $message = "Only valid JPG, JPEG and PNG images are allowed.";
                $messageType = "danger";
            } else {
                $uploadDirectory = __DIR__ . "/uploads/events/";

                if (!is_dir($uploadDirectory) &&
                    !mkdir($uploadDirectory, 0755, true)) {
                    $message = "Unable to create the poster upload folder.";
                    $messageType = "danger";
                } else {
                    $posterName = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
                    $uploadedPath = $uploadDirectory . $posterName;

                    if (!move_uploaded_file($file["tmp_name"], $uploadedPath)) {
                        $message = "Unable to save the poster. Check folder permissions.";
                        $messageType = "danger";
                    }
                }
            }
        }
    }

    if ($message === "") {
        $stmt = $conn->prepare(
            "INSERT INTO events (title, description, event_date, category, poster, status)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            $stmt->bind_param(
                "ssssss",
                $title,
                $description,
                $eventDate,
                $category,
                $posterName,
                $status
            );

            
if ($stmt->execute()) {
    $eventId = $conn->insert_id;
    $stmt->close();

    // Connect to the database containing audit_logs
    require_once "db.php";

    $userId = (int)($_SESSION["user_id"] ?? 0);
    $username = trim($_SESSION["username"] ?? $_SESSION["email"] ?? "admin");
    $action = "ADD_EVENT";
    $details = "New event added: " . $title;

    $logStmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, username, action, details, created_at)
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
            error_log("Audit log insert failed: " . $logStmt->error);
        }

        $logStmt->close();
    } else {
        error_log("Audit log prepare failed: " . $conn->error);
    }

    $conn->close();

    $_SESSION["event_message"] = "Event added successfully!";
    $_SESSION["event_message_type"] = "success";

    header("Location: admin_events.php");
    exit();
}
else {
                if ($uploadedPath !== null && is_file($uploadedPath)) {
                    unlink($uploadedPath);
                }

                $message = "Unable to add event. Please try again.";
                $messageType = "danger";
                $stmt->close();
            }
        } else {
            if ($uploadedPath !== null && is_file($uploadedPath)) {
                unlink($uploadedPath);
            }

            $message = "Database query failed.";
            $messageType = "danger";
        }
    }
}

function safeOutput($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - Student Hub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        
#notification {
    background: #005F73;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: bold;
}

#closeNotification {
    margin-left: 15px;
    border: none;
    padding: 5px 10px;
    border-radius: 15px;
    cursor: pointer;
    transition: 0.3s;
}

#closeNotification:hover {
    transform: scale(1.1);
}

header {
    position: relative;
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 35px 20px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
}

header img.logo {
    width: 180px;
    height: 180px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid rgb(13, 0, 0);
    padding: 6px;
    background: white;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25);
    transition: 0.4s;
}

header img.logo:hover {
    transform: scale(1.08);
}

header h1 {
    margin: 18px 0;
    font-size: 38px;
    letter-spacing: 2px;
}

#themeButton {
    position: absolute;
    top: 18px;
    right: 25px;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    background: white;
    color: #002147;
    font-size: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    cursor: pointer;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    transition: 0.3s;
}

#themeButton:hover {
    transform: scale(1.1);
}

#menuButton {
    display: none;
    background: white;
    color: #002147;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.3s;
}

#menuButton:hover {
    transform: scale(1.05);
}

nav {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 15px;
}

nav a {
    text-decoration: none;
    color: white;
    background: rgba(255, 255, 255, 0.15);
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    transition: 0.3s;
}

nav a:hover {
    background: white;
    color: #002147;
    transform: translateY(-3px);
}

footer {
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    text-align: center;
    padding: 22px;
    margin-top: 40px;
}

footer p {
    margin: 6px 0;
}

body.dark #themeButton {
    background: #222;
    color: #4dc8e0;
}

@media (max-width: 768px) {
    header img.logo {
        width: 140px;
        height: 140px;
    }

    header h1 {
        font-size: 30px;
    }

    #menuButton {
        display: inline-block;
    }

    nav {
        display: none;
        flex-direction: column;
    }

    nav.show {
        display: flex;
    }

    nav a {
        width: 220px;
        text-align: center;
    }

    #themeButton {
        top: 15px;
        right: 15px;
        width: 38px;
        height: 38px;
        font-size: 18px;
    }
}

        body {
            background: #F8FAFC;
            font-family: Arial, sans-serif;
        }

        .header {
            background: linear-gradient(135deg, #002147, #4dc8e0);
            color: white;
            text-align: center;
            padding: 25px;
        }

        .form-container {
            max-width: 750px;
            margin: 35px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            font-weight: bold;
            color: #002147;
        }

        .form-control, .form-select {
            padding: 11px;
            border-radius: 8px;
        }

        .btn-primary {
            background: #005F73;
            border-color: #005F73;
        }

        .btn-primary:hover {
            background: #004653;
        }

        .help-text {
            font-size: 14px;
            color: #666;
        }
        /* Dark Mode */
body.dark {
    background: #121212;
    color: #f1f1f1;
}

body.dark .main-container {
    color: #f1f1f1;
}

body.dark .event-card {
    background: #1e1e1e;
    color: #f1f1f1;
    box-shadow: 0 3px 12px rgba(255, 255, 255, 0.08);
}

body.dark .form-select {
    background-color: #2b2b2b;
    color: white;
    border-color: #555;
}

body.dark .form-label {
    color: #f1f1f1;
}

body.dark .alert-info {
    background: #203743;
    color: #eaf4f8;
    border-color: #345563;
}

body.dark #themeButton {
    background: #222;
    color: #4dc8e0;
}

/* Notification Banner */
#notification {
    display: block;
    background: #005F73;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: bold;
}

#notification.hidden {
    display: none;
}

#closeNotification {
    margin-left: 15px;
    border: none;
    padding: 5px 10px;
    border-radius: 15px;
    cursor: pointer;
    background: white;
    color: #005F73;
    transition: 0.3s;
}

#closeNotification:hover {
    transform: scale(1.1);
}
body.dark .form-container {
    background: #1e1e1e;
    color: #f1f1f1;
}

body.dark .form-control {
    background-color: #2b2b2b;
    color: white;
    border-color: #555;
}

body.dark .form-control::placeholder {
    color: #ccc;
}

body.dark .help-text {
    color: #ccc;
}

body.dark .form-control:focus,
body.dark .form-select:focus {
    background-color: #2b2b2b;
    color: white;
}
    </style>

    
</head>

<body>


<div id="notification">
    🔔 New updates are available!
    <button id="closeNotification" type="button" aria-label="Close notification">✕</button>
</div>

<header>
    <button id="themeButton" type="button" title="Change theme" aria-label="Change theme">🌙</button>

    <img class="logo" src="logo.jpeg" alt="Student Hub Logo">

    <h1>STUDENT HUB</h1>
    <h2>Create your events here</h2>

    <button id="menuButton" type="button" aria-label="Open navigation menu">
        ☰ Menu
    </button>
</header>


<div class="container">
    <div class="form-container">

        <h2 class="mb-4">Event Details</h2>

        <?php if ($message !== ""): ?>
            <div class="alert alert-<?= safeOutput($messageType) ?>" role="alert">
                <?= safeOutput($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label for="title" class="form-label">Event Title *</label>
                <input type="text" id="title" name="title"
                       class="form-control" maxlength="150"
                       value="<?= safeOutput($title) ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Event Description *</label>
                <textarea id="description" name="description"
                          class="form-control" rows="4" required><?= safeOutput($description) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="event_date" class="form-label">Event Date *</label>
                <input type="date" id="event_date" name="event_date"
                       class="form-control"
                       value="<?= safeOutput($eventDate) ?>" required>
            </div>

            <div class="mb-3">
                <label for="category" class="form-label">Category *</label>
                <select id="category" name="category" class="form-select" required>
                    <?php
                    $categories = ["General", "Workshop", "Seminar", "Hackathon", "Cultural", "Sports", "Technical"];
                    foreach ($categories as $item):
                    ?>
                        <option value="<?= safeOutput($item) ?>"
                            <?= $category === $item ? "selected" : "" ?>>
                            <?= safeOutput($item) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Event Status *</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="open" <?= $status === "open" ? "selected" : "" ?>>Open</option>
                    <option value="closed" <?= $status === "closed" ? "selected" : "" ?>>Closed</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="poster" class="form-label">Event Poster</label>
                <input type="file" id="poster" name="poster"
                       class="form-control">
                <p class="help-text mt-2">Allowed formats: JPG, JPEG, PNG. Maximum size: 2 MB.</p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary">Add Event</button>
                <button type="reset" class="btn btn-danger">Clear Form</button>
                <a href="admin_events.php" class="btn btn-secondary">Back to Events</a>
            </div>

        </form>
    </div>
</div>


<footer>
    <p><b>Student Hub</b></p>
    <p>Learn • Explore • Grow</p>
    <p>&copy; 2026 Student Hub. All Rights Reserved.</p>
    <p>Designed & developed by Heni Patel</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const themeButton = document.getElementById("themeButton");
    const notification = document.getElementById("notification");
    const closeNotification = document.getElementById("closeNotification");

    // Load saved theme
    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        document.body.classList.add("dark");
        themeButton.textContent = "☀️";
    } else {
        document.body.classList.remove("dark");
        themeButton.textContent = "🌙";
    }

    // Dark Mode toggle
    themeButton.addEventListener("click", function () {
        const isDark = document.body.classList.toggle("dark");

        if (isDark) {
            localStorage.setItem("theme", "dark");
            themeButton.textContent = "☀️";
        } else {
            localStorage.setItem("theme", "light");
            themeButton.textContent = "🌙";
        }
    });

    // Close notification banner
    closeNotification.addEventListener("click", function () {
        notification.classList.add("hidden");
    });
});
</script>


</body>
</html>
