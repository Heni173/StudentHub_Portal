
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"]) ||
    strtolower(trim($_SESSION["role"])) !== "admin"
) {
    http_response_code(403);
    exit("Access Denied! Only administrators can manage events.");
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

if (isset($_SESSION["event_message"])) {
    $message = $_SESSION["event_message"];
    $messageType = $_SESSION["event_message_type"] ?? "success";

    unset($_SESSION["event_message"], $_SESSION["event_message_type"]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_event"])) {
    $eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);

    if ($eventId) {
        $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();

        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        $stmt->close();

        if ($event) {
            $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
            $stmt->bind_param("i", $eventId);

            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $stmt->close();

                if (!empty($event["poster"])) {
                    $posterPath = __DIR__ . "/uploads/events/" . basename($event["poster"]);

                    if (is_file($posterPath)) {
                        unlink($posterPath);
                    }
                }

                $_SESSION["event_message"] = "Event deleted successfully!";
                $_SESSION["event_message_type"] = "success";
            } else {
                $stmt->close();

                $_SESSION["event_message"] = "Unable to delete event.";
                $_SESSION["event_message_type"] = "error";
            }
        } else {
            $_SESSION["event_message"] = "Event not found.";
            $_SESSION["event_message_type"] = "error";
        }
    } else {
        $_SESSION["event_message"] = "Invalid event ID.";
        $_SESSION["event_message_type"] = "error";
    }

    header("Location: admin_events.php");
    exit();
}

$filter = $_GET["status"] ?? "all";

if ($filter === "open" || $filter === "closed") {
    $stmt = $conn->prepare(
        "SELECT * FROM events WHERE status = ? ORDER BY event_date DESC"
    );

    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $events = $stmt->get_result();
} else {
    $events = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
}

function escapeOutput($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Event Management - Student Hub</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #F8FAFC;
            color: #212529;
            font-family: Arial, sans-serif;
            transition: background 0.3s, color 0.3s;
        }

        .header {
            background: linear-gradient(90deg, #002147, #4dc8e0);
            color: white;
            padding: 25px;
        }

        .main-container {
            max-width: 1150px;
            margin: 35px auto;
        }

        .event-card {
            background: white;
            color: #212529;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            height: 100%;
            transition: background 0.3s, color 0.3s;
        }

        .poster {
            width: 100%;
            height: 190px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .no-poster {
            height: 190px;
            background: #EAF4F8;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #005F73;
        }

        .btn-primary {
            background: #005F73;
            border-color: #005F73;
        }

        .btn-primary:hover {
            background: #004653;
            border-color: #004653;
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

        /* Header */
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
            border: 5px solid #130000;
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

        /* Footer */
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

        body.dark .no-poster {
            background: #263943;
            color: #eaf4f8;
        }

        body.dark .alert-info {
            background: #203743;
            color: #eaf4f8;
            border-color: #345563;
        }

        body.dark .alert-success {
            background: #173b2b;
            color: #d1f7df;
            border-color: #286344;
        }

        body.dark .alert-danger {
            background: #482323;
            color: #ffe0e0;
            border-color: #773838;
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

    <h1>Event Management</h1>
    <h2>StudentHub Portal</h2>

    <button id="menuButton" type="button" aria-label="Open navigation menu">
        ☰ Menu
    </button>
</header>

<div class="container main-container">

    <?php if ($message !== ""): ?>
        <div class="alert <?= $messageType === "success" ? "alert-success" : "alert-danger" ?>">
            <?= escapeOutput($message) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2>Events List</h2>
        <a href="add_event.php" class="btn btn-primary">+ Add New Event</a>
    </div>

    <form method="GET" class="mb-4">
        <label for="status" class="form-label">Filter by Status:</label>

        <div class="d-flex gap-2">
            <select name="status" id="status" class="form-select" style="max-width:250px">
                <option value="all" <?= $filter === "all" ? "selected" : "" ?>>All Events</option>
                <option value="open" <?= $filter === "open" ? "selected" : "" ?>>Open Events</option>
                <option value="closed" <?= $filter === "closed" ? "selected" : "" ?>>Closed Events</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>

    <div class="row g-4">

        <?php if ($events && $events->num_rows > 0): ?>

            <?php while ($event = $events->fetch_assoc()): ?>

                <div class="col-md-6 col-lg-4">
                    <div class="event-card">

                        <?php
                        $posterName = $event["poster"] ?? "";
                        $posterFile = __DIR__ . "/uploads/events/" . basename($posterName);
                        ?>

                        <?php if ($posterName !== "" && is_file($posterFile)): ?>

                            <img
                                src="<?= "uploads/events/" . rawurlencode(basename($posterName)) ?>"
                                alt="Event Poster"
                                class="poster">

                        <?php else: ?>

                            <div class="no-poster">No Poster Available</div>

                        <?php endif; ?>

                        <h4><?= escapeOutput($event["title"]) ?></h4>

                        <p><?= nl2br(escapeOutput($event["description"])) ?></p>

                        <p>
                            <strong>Date:</strong>
                            <?= escapeOutput($event["event_date"]) ?>
                        </p>

                        <p>
                            <strong>Category:</strong>
                            <?= escapeOutput($event["category"]) ?>
                        </p>

                        <p>
                            <strong>Status:</strong>

                            <span class="badge <?= $event["status"] === "open" ? "text-bg-success" : "text-bg-secondary" ?>">
                                <?= escapeOutput(ucfirst($event["status"])) ?>
                            </span>
                        </p>

                        <div class="d-flex gap-2">

                            <a
                                href="edit_event.php?id=<?= (int)$event["event_id"] ?>"
                                class="btn btn-warning btn-sm">
                                Edit
                            </a>

                            <form
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this event?');">

                                <input
                                    type="hidden"
                                    name="event_id"
                                    value="<?= (int)$event["event_id"] ?>">

                                <button
                                    type="submit"
                                    name="delete_event"
                                    value="1"
                                    class="btn btn-danger btn-sm">
                                    Delete
                                </button>

                            </form>

                        </div>
                    </div>
                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info">
                    No events found. Click Add New Event to create one.
                </div>
            </div>

        <?php endif; ?>

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
    const menuButton = document.getElementById("menuButton");

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
        document.body.classList.toggle("dark");

        if (document.body.classList.contains("dark")) {
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

    // Mobile menu button
    if (menuButton) {
        menuButton.addEventListener("click", function () {
            const navigation = document.getElementById("navigation");

            if (navigation) {
                navigation.classList.toggle("show");
            }
        });
    }
});
</script>



</body>
</html>
