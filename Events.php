<?php
require_once "timeout.php";

/* ---------- SETTINGS (admin_events.php jya save kare tya mujab rakhvu) ---------- */
$eventDbName  = "studenthub_events";   // database nu naam
$posterFolder = "uploads/events/";     // poster images ni folder (admin_events.php jya upload kare)
/* ------------------------------------------------------------------------------- */

function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$events = [];
$loadError = "";

$eventConn = @new mysqli("localhost", "root", "", $eventDbName);

if ($eventConn->connect_error) {
    error_log("Events database connection failed: " . $eventConn->connect_error);
    $loadError = "Unable to load events right now. Please try again later.";
} else {
    $eventConn->set_charset("utf8mb4");

    $result = $eventConn->query(
        "SELECT event_id, title, description, event_date, category, poster, status
         FROM events
         ORDER BY event_date DESC, event_id DESC"
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        $result->free();
    } else {
        error_log("Events query failed: " . $eventConn->error);
        $loadError = "Unable to load events right now. Please try again later.";
    }

    $eventConn->close();
}

function eventIcon($category)
{
    $key = strtolower(trim((string)$category));

    $icons = [
        "cultural"  => "🎭",
        "hackathon" => "💻",
        "technical" => "🛠️",
        "sports"    => "⚽",
        "workshop"  => "🧪",
        "seminar"   => "🎤",
    ];

    return $icons[$key] ?? "🎉";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Hub - Events</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: linear-gradient(135deg, #f8fafc, #eaf4f8);
    color: #263238;
    transition: 0.3s;
    min-height: 100vh;
}

/* NOTIFICATION */

#notification {
    background: #005f73;
    color: white;
    padding: 12px;
    text-align: center;
    font-weight: 600;
}

#closeNotification {
    margin-left: 15px;
    border: none;
    background: white;
    color: #005f73;
    padding: 5px 11px;
    border-radius: 20px;
    cursor: pointer;
    transition: 0.3s;
}

#closeNotification:hover {
    transform: scale(1.1);
}


/* HEADER */

header {
    position: relative;
    background: linear-gradient(135deg, #002147, #005f73, #4dc8e0);
    color: white;
    text-align: center;
    padding: 32px 20px 38px;
    box-shadow: 0 10px 30px rgba(0, 33, 71, 0.25);
}

header::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, white, #4dc8e0, white);
}

header img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid white;
    padding: 5px;
    background: white;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
    transition: 0.4s;
}

header img:hover {
    transform: scale(1.06);
}

header h1 {
    margin: 15px 0 5px;
    font-size: 38px;
    font-weight: 800;
    letter-spacing: 2px;
}

header h2 {
    font-size: 22px;
    font-weight: 400;
}


/* THEME BUTTON */

#themeButton {
    position: absolute;
    top: 22px;
    right: 28px;
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 50%;
    background: white;
    color: #002147;
    font-size: 20px;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    transition: 0.3s;
}

#themeButton:hover {
    transform: rotate(20deg) scale(1.1);
}


/* MENU */

#menuButton {
    display: none;
    background: white;
    color: #002147;
    border: none;
    padding: 10px 22px;
    border-radius: 25px;
    font-weight: 700;
    cursor: pointer;
    margin: 18px auto 0;
}


/* NAVIGATION */

nav {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

nav a {
    text-decoration: none;
    color: white;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 9px 17px;
    border-radius: 25px;
    font-weight: 600;
    transition: 0.3s;
}

nav a:hover,
nav a.active {
    background: white;
    color: #002147;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
}


/* MAIN */

main {
    width: 92%;
    max-width: 1250px;
    margin: auto;
    padding: 50px 0;
}


/* PAGE HEADING */

.page-heading {
    text-align: center;
    margin-bottom: 30px;
}

.page-heading .small-title {
    color: #0087a3;
    font-size: 14px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 2px;
    margin-bottom: 8px;
}

.page-heading h2 {
    color: #002147;
    font-size: 38px;
    font-weight: 800;
    margin-bottom: 10px;
}

.page-heading p {
    color: #607d8b;
    font-size: 17px;
}


/* EVENTS BOX */

.events-box {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 25px;
    padding: 35px;
    box-shadow: 0 15px 40px rgba(0, 33, 71, 0.12);
    border: 1px solid rgba(77, 200, 224, 0.2);
}


/* SEARCH */

.search-filter {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 35px;
    flex-wrap: wrap;
}

#searchInput,
#dateFilter,
#sortFilter {
    width: 300px;
    padding: 13px 18px;
    border: 2px solid #d8e6eb;
    border-radius: 30px;
    font-size: 16px;
    outline: none;
    background: white;
    color: #263238;
    transition: 0.3s;
}

#searchInput:focus,
#dateFilter:focus,
#sortFilter:focus {
    border-color: #4dc8e0;
    box-shadow: 0 0 0 4px rgba(77, 200, 224, 0.12);
}


/* LOADING */

#loading {
    text-align: center;
    padding: 35px;
    color: #005f73;
    font-size: 18px;
    font-weight: 700;
}


/* EVENT GRID */

#eventsBody {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}


/* EVENT CARD */

.event-card {
    position: relative;
    overflow: hidden;
    background: white;
    border-radius: 20px;
    border: 1px solid #e1edf1;
    padding: 26px;
    min-height: 245px;
    box-shadow: 0 8px 22px rgba(0, 33, 71, 0.08);
    transition: 0.35s ease;
}

.event-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: linear-gradient(90deg, #002147, #4dc8e0);
}

.event-card:hover {
    transform: translateY(-9px);
    border-color: #4dc8e0;
    box-shadow: 0 18px 35px rgba(0, 33, 71, 0.17);
}


/* EVENT ICON */

.event-icon {
    width: 62px;
    height: 62px;
    border-radius: 17px;
    background: linear-gradient(135deg, #002147, #4dc8e0);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 29px;
    margin-bottom: 18px;
    box-shadow: 0 7px 18px rgba(0, 95, 115, 0.2);
    transition: 0.3s;
}

.event-card:hover .event-icon {
    transform: scale(1.08);
}


/* NUMBER */

.event-number {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #002147;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 14px;
    font-weight: 700;
}


/* EVENT NAME */

.event-card h3 {
    color: #002147;
    font-size: 21px;
    font-weight: 750;
    margin-bottom: 10px;
    padding-right: 40px;
}


/* EVENT DETAILS */

.event-description {
    color: #607d8b;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 18px;
}


/* DATE */

.event-date {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #eaf8fb;
    color: #006b80;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}


/* LOCATION */

.event-location {
    color: #607d8b;
    font-size: 14px;
    margin-top: 10px;
}


/* NO RESULT */

#noResults {
    display: none;
    text-align: center;
    color: #d32f2f;
    font-size: 18px;
    font-weight: 600;
    padding: 30px;
}


/* PAGINATION */

#pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 35px;
    flex-wrap: wrap;
}

.page-button {
    border: none;
    background: #eaf4f8;
    color: #002147;
    min-width: 42px;
    height: 42px;
    padding: 0 13px;
    border-radius: 22px;
    font-weight: 700;
    cursor: pointer;
    transition: 0.3s;
}

.page-button:hover {
    background: #4dc8e0;
    color: #002147;
    transform: translateY(-2px);
}

.page-button.active {
    background: #002147;
    color: white;
}

.page-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}


/* INFO BOX */

.info-box {
    margin-top: 30px;
    background: linear-gradient(135deg, #002147, #005f73);
    color: white;
    padding: 25px;
    border-radius: 20px;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0, 33, 71, 0.18);
    transition: 0.3s;
}

.info-box:hover {
    transform: translateY(-5px);
}

.info-icon {
    font-size: 30px;
    margin-bottom: 8px;
}

.info-box p {
    font-size: 17px;
    margin: 0;
}


/* FOOTER */

footer {
    background: linear-gradient(135deg, #002147, #005f73, #4dc8e0);
    color: white;
    text-align: center;
    padding: 28px 20px;
    margin-top: 20px;
}

footer .footer-title {
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 5px;
}

footer p {
    margin: 5px 0;
}


/* ADDED: EVENT POSTER + LINE CLAMP + ERROR */

.event-poster {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 14px;
    margin-bottom: 16px;
    display: block;
}

.event-description {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

#loadError {
    text-align: center;
    color: #d32f2f;
    font-size: 18px;
    font-weight: 600;
    padding: 30px;
}



/* ADDED: EVENT DETAILS POPUP */

.event-card {
    cursor: pointer;
}

.evm-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 20, 40, 0.78);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 2000;
}

.evm-overlay.show {
    display: flex;
}

.evm-box {
    position: relative;
    background: white;
    color: #263238;
    width: 100%;
    max-width: 760px;
    max-height: 92vh;
    overflow-y: auto;
    border-radius: 22px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
}

.evm-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    background: rgba(0, 33, 71, 0.88);
    color: white;
    font-size: 18px;
    cursor: pointer;
    z-index: 3;
    transition: 0.3s;
}

.evm-close:hover {
    transform: scale(1.1);
    background: #002147;
}

.evm-poster-wrap {
    background: #eef4f7;
    text-align: center;
    border-radius: 22px 22px 0 0;
    padding: 0;
}

.evm-poster-wrap a {
    display: block;
}

.evm-poster-wrap img {
    display: block;
    margin: 0 auto;
    max-width: 100%;
    max-height: 65vh;
    object-fit: contain;
}

.evm-icon-wrap {
    display: none;
    justify-content: center;
    padding: 35px 0 5px;
}

.evm-icon-wrap .event-icon {
    margin-bottom: 0;
}

.evm-body {
    padding: 24px 28px 30px;
}

.evm-body h3 {
    color: #002147;
    font-size: 26px;
    font-weight: 800;
    margin-bottom: 14px;
}

.evm-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}

.evm-category {
    display: inline-flex;
    align-items: center;
    background: #fff4d9;
    color: #8a6100;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
}

.evm-description {
    color: #455a64;
    font-size: 16px;
    line-height: 1.75;
    white-space: pre-line;
    overflow-wrap: anywhere;
}

body.dark .evm-box {
    background: #18242b;
    color: white;
}

body.dark .evm-poster-wrap {
    background: #0f1a20;
}

body.dark .evm-body h3 {
    color: #4dc8e0;
}

body.dark .evm-category {
    background: #3b3320;
    color: #ffd978;
}

body.dark .evm-description {
    color: #b9cbd1;
}

@media (max-width: 700px) {

    .evm-overlay {
        padding: 10px;
    }

    .evm-body {
        padding: 20px 18px 24px;
    }

    .evm-body h3 {
        font-size: 22px;
    }

}

/* DARK MODE */

body.dark {
    background: #101820;
    color: white;
}

body.dark .page-heading h2 {
    color: #4dc8e0;
}

body.dark .page-heading p {
    color: #b9cbd1;
}

body.dark .events-box {
    background: #18242b;
    border-color: #30434b;
}

body.dark #searchInput,
body.dark #dateFilter,
body.dark #sortFilter {
    background: #24343c;
    color: white;
    border-color: #46616c;
}

body.dark #searchInput::placeholder {
    color: #b9cbd1;
}

body.dark .event-card {
    background: #202f37;
    border-color: #354b54;
}

body.dark .event-card h3 {
    color: white;
}

body.dark .event-description,
body.dark .event-location {
    color: #b9cbd1;
}

body.dark .event-date {
    background: #29454f;
    color: #72d8eb;
}

body.dark .event-number {
    background: #4dc8e0;
    color: #002147;
}

body.dark #loading {
    color: #72d8eb;
}

body.dark .page-button {
    background: #29454f;
    color: white;
}

body.dark .page-button:hover {
    background: #4dc8e0;
    color: #002147;
}

body.dark .page-button.active {
    background: #4dc8e0;
    color: #002147;
}


/* RESPONSIVE */

@media (max-width: 1000px) {

    #eventsBody {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media (max-width: 700px) {

    header img {
        width: 125px;
        height: 125px;
    }

    header h1 {
        font-size: 29px;
    }

    header h2 {
        font-size: 20px;
    }

    #themeButton {
        top: 15px;
        right: 15px;
    }

    #menuButton {
        display: block;
    }

    #navigation {
        display: none;
        flex-direction: column;
        align-items: center;
    }

    #navigation.show {
        display: flex;
    }

    nav a {
        width: 220px;
        text-align: center;
    }

    main {
        width: 94%;
        padding: 35px 0;
    }

    .events-box {
        padding: 22px;
    }

    .page-heading h2 {
        font-size: 30px;
    }

    #eventsBody {
        grid-template-columns: 1fr;
    }

    #searchInput,
    #dateFilter,
    #sortFilter {
        width: 100%;
    }

}

</style>

</head>

<body>


<!-- NOTIFICATION -->

<div id="notification">

    🔔 New updates are available!

    <button id="closeNotification" type="button">
        ✕
    </button>

</div>


<!-- HEADER -->

<header>

    <button id="themeButton" type="button" title="Change theme">
        🌙
    </button>

    <img src="logo.jpeg" alt="Student Hub Logo">

    <h1>STUDENT HUB</h1>

    <h2>College Events</h2>

    <button id="menuButton" type="button">
        ☰ Menu
    </button>

    <nav id="navigation">

        <a href="Home.php">Home</a>
        <a href="register.php">Register</a>
        <a href="Student_dashboard1.php"> Dashboard</a>
        <a href="Profile.php">Profile</a>
        <a href="Events.php" class="active">Events</a>
        <a href="About.html">About Us</a>
        <a href="ContactUs.html">Contact Us</a>
        <a href="Feedback.html">Feedback</a>
        <a href="Location.html">Location</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>


<!-- MAIN -->

<main>

    <div class="page-heading">

        <div class="small-title">
            Student Activities
        </div>

        <h2>Explore College Events</h2>

        <p>
            Discover upcoming events, activities and exciting opportunities.
        </p>

    </div>


    <section class="events-box">


        <!-- SEARCH, FILTER AND SORT -->

        <div class="search-filter">

            <input
                type="text"
                id="searchInput"
                placeholder="🔍 Search event..."
            >

            <input
                type="date"
                id="dateFilter"
            >

            <select id="sortFilter">

                <option value="default">
                    Sort Events
                </option>

                <option value="nameAsc">
                    Name A - Z
                </option>

                <option value="nameDesc">
                    Name Z - A
                </option>

                <option value="dateAsc">
                    Date Oldest - Newest
                </option>

                <option value="dateDesc">
                    Date Newest - Oldest
                </option>

            </select>

        </div>


        <!-- LOADING -->

        <div id="loading">
            ⏳ Loading events...
        </div>


        <?php if ($loadError !== ""): ?>
            <p id="loadError"><?= h($loadError) ?></p>
        <?php endif; ?>


        <!-- EVENT CARDS (admin ma thi add / update / delete thayela events ahi j dekhase) -->

        <div id="eventsBody">

            <?php foreach ($events as $index => $event): ?>

                <?php
                $eventDate = $event["event_date"] ?? "";
                $dateLabel = $eventDate !== "" && strtotime($eventDate)
                    ? date("d M Y", strtotime($eventDate))
                    : "Date not announced";
                $dateValue = $eventDate !== "" && strtotime($eventDate)
                    ? date("Y-m-d", strtotime($eventDate))
                    : "";
                $poster = trim((string)($event["poster"] ?? ""));
                ?>

                <article class="event-card"
                         data-index="<?= (int)$index ?>"
                         data-title="<?= h(strtolower((string)$event["title"])) ?>"
                         data-search="<?= h(strtolower($event["title"] . " " . $event["description"] . " " . $event["category"])) ?>"
                         data-date="<?= h($dateValue) ?>"
                         data-date-label="<?= h($dateLabel) ?>"
                         data-category="<?= h($event["category"]) ?>"
                         data-description="<?= h($event["description"]) ?>"
                         data-poster="<?= $poster !== "" ? h($posterFolder . $poster) : "" ?>"
                         data-icon="<?= h(eventIcon($event["category"])) ?>"
                         tabindex="0" role="button"
                         aria-label="View details of <?= h($event["title"]) ?>">

                    <div class="event-number"><?= (int)$index + 1 ?></div>

                    <?php if ($poster !== ""): ?>
                        <img class="event-poster"
                             src="<?= h($posterFolder . $poster) ?>"
                             alt="<?= h($event["title"]) ?> poster"
                             onerror="this.style.display='none'">
                    <?php else: ?>
                        <div class="event-icon"><?= eventIcon($event["category"]) ?></div>
                    <?php endif; ?>

                    <h3><?= h($event["title"]) ?></h3>

                    <p class="event-description">
                        <?= h($event["description"]) ?>
                    </p>

                    <span class="event-date">📅 <?= h($dateLabel) ?></span>

                    <?php if (!empty($event["category"])): ?>
                        <p class="event-location">🏷️ <?= h($event["category"]) ?></p>
                    <?php endif; ?>

                </article>

            <?php endforeach; ?>

        </div>


        <p id="noResults">
            😔 No events found.
        </p>


        <!-- PAGINATION -->

        <div id="pagination"></div>


    </section>


    <!-- INFO BOX -->

    <section class="info-box">

        <div class="info-icon">
            🎓
        </div>

        <p>
            For more information, contact the Student Activity Coordinator.
        </p>

    </section>

</main>


<!-- FOOTER -->

<footer>

    <p class="footer-title">
        Student Hub
    </p>

    <p>
        Learn • Explore • Grow
    </p>

    <p>
        © 2026 Student Hub. All Rights Reserved.
    </p>

    <p>
        Designed & Developed by Heni Patel
    </p>

</footer>



<!-- EVENT DETAILS POPUP -->

<div id="eventModal" class="evm-overlay" aria-hidden="true">

    <div class="evm-box" role="dialog" aria-modal="true" aria-labelledby="evmTitle">

        <button type="button" id="evmClose" class="evm-close" aria-label="Close details">
            ✕
        </button>

        <div id="evmPosterWrap" class="evm-poster-wrap">
            <a id="evmPosterLink" href="#" target="_blank" rel="noopener" title="Open full size poster">
                <img id="evmPoster" src="" alt="">
            </a>
        </div>

        <div id="evmIconWrap" class="evm-icon-wrap">
            <div id="evmIcon" class="event-icon">🎉</div>
        </div>

        <div class="evm-body">

            <h3 id="evmTitle"></h3>

            <div class="evm-meta">
                <span id="evmDate" class="event-date"></span>
                <span id="evmCategory" class="evm-category"></span>
            </div>

            <p id="evmDescription" class="evm-description"></p>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const notification = document.getElementById("notification");
    const closeNotification = document.getElementById("closeNotification");
    const themeButton = document.getElementById("themeButton");
    const menuButton = document.getElementById("menuButton");
    const navigation = document.getElementById("navigation");

    const searchInput = document.getElementById("searchInput");
    const dateFilter = document.getElementById("dateFilter");
    const sortFilter = document.getElementById("sortFilter");
    const eventsBody = document.getElementById("eventsBody");
    const noResults = document.getElementById("noResults");
    const pagination = document.getElementById("pagination");
    const loading = document.getElementById("loading");

    const cards = Array.from(document.querySelectorAll(".event-card"));
    const perPage = 6;
    let currentPage = 1;

    /* ---------- Theme ---------- */

    let savedTheme = "light";

    try {
        savedTheme = localStorage.getItem("theme") || "light";
    } catch (error) {}

    function applyTheme(theme) {
        const dark = theme === "dark";
        document.body.classList.toggle("dark", dark);
        themeButton.textContent = dark ? "☀️" : "🌙";
    }

    applyTheme(savedTheme);

    themeButton.addEventListener("click", function () {
        const theme = document.body.classList.contains("dark") ? "light" : "dark";
        applyTheme(theme);

        try {
            localStorage.setItem("theme", theme);
        } catch (error) {}
    });

    /* ---------- Notification ---------- */

    try {
        if (localStorage.getItem("notificationClosed") === "true") {
            notification.style.display = "none";
        }
    } catch (error) {}

    closeNotification.addEventListener("click", function () {
        notification.style.display = "none";

        try {
            localStorage.setItem("notificationClosed", "true");
        } catch (error) {}
    });

    /* ---------- Menu ---------- */

    menuButton.addEventListener("click", function () {
        const open = navigation.classList.toggle("show");
        menuButton.textContent = open ? "✕ Close Menu" : "☰ Menu";
    });

    navigation.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navigation.classList.remove("show");
            menuButton.textContent = "☰ Menu";
        });
    });

    /* ---------- Search / Filter / Sort / Pagination ---------- */

    function getFilteredCards() {
        const text = searchInput.value.trim().toLowerCase();
        const date = dateFilter.value;

        let list = cards.filter(function (card) {
            const matchText = text === "" || card.dataset.search.includes(text);
            const matchDate = date === "" || card.dataset.date === date;
            return matchText && matchDate;
        });

        const sort = sortFilter.value;

        list.sort(function (a, b) {
            if (sort === "nameAsc") {
                return a.dataset.title.localeCompare(b.dataset.title);
            }
            if (sort === "nameDesc") {
                return b.dataset.title.localeCompare(a.dataset.title);
            }
            if (sort === "dateAsc") {
                return (a.dataset.date || "9999").localeCompare(b.dataset.date || "9999");
            }
            if (sort === "dateDesc") {
                return (b.dataset.date || "").localeCompare(a.dataset.date || "");
            }
            return Number(a.dataset.index) - Number(b.dataset.index);
        });

        return list;
    }

    function render() {
        const list = getFilteredCards();
        const totalPages = Math.max(1, Math.ceil(list.length / perPage));

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        cards.forEach(function (card) {
            card.style.display = "none";
        });

        const start = (currentPage - 1) * perPage;
        const pageItems = list.slice(start, start + perPage);

        pageItems.forEach(function (card, i) {
            card.style.display = "";
            card.querySelector(".event-number").textContent = start + i + 1;
            eventsBody.appendChild(card);
        });

        noResults.style.display = list.length === 0 && cards.length > 0 ? "block" : "none";

        if (cards.length === 0 && !document.getElementById("loadError")) {
            noResults.textContent = "😔 No events available right now.";
            noResults.style.display = "block";
        }

        pagination.innerHTML = "";

        if (list.length > perPage) {
            const prev = document.createElement("button");
            prev.type = "button";
            prev.className = "page-button";
            prev.textContent = "‹ Prev";
            prev.disabled = currentPage === 1;
            prev.addEventListener("click", function () {
                currentPage--;
                render();
            });
            pagination.appendChild(prev);

            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "page-button" + (p === currentPage ? " active" : "");
                btn.textContent = p;
                btn.addEventListener("click", function () {
                    currentPage = p;
                    render();
                });
                pagination.appendChild(btn);
            }

            const next = document.createElement("button");
            next.type = "button";
            next.className = "page-button";
            next.textContent = "Next ›";
            next.disabled = currentPage === totalPages;
            next.addEventListener("click", function () {
                currentPage++;
                render();
            });
            pagination.appendChild(next);
        }
    }

    [searchInput, dateFilter, sortFilter].forEach(function (el) {
        el.addEventListener("input", function () {
            currentPage = 1;
            render();
        });
    });

    /* ---------- Event details popup ---------- */

    const evm = document.getElementById("eventModal");
    const evmClose = document.getElementById("evmClose");
    const evmTitle = document.getElementById("evmTitle");
    const evmDate = document.getElementById("evmDate");
    const evmCategory = document.getElementById("evmCategory");
    const evmDescription = document.getElementById("evmDescription");
    const evmPoster = document.getElementById("evmPoster");
    const evmPosterLink = document.getElementById("evmPosterLink");
    const evmPosterWrap = document.getElementById("evmPosterWrap");
    const evmIconWrap = document.getElementById("evmIconWrap");
    const evmIcon = document.getElementById("evmIcon");

    let lastFocusedCard = null;

    function showIconInstead(icon) {
        evmPosterWrap.style.display = "none";
        evmIcon.textContent = icon || "🎉";
        evmIconWrap.style.display = "flex";
    }

    function openEventModal(card) {
        const title = card.querySelector("h3").textContent;
        const poster = card.dataset.poster || "";

        evmTitle.textContent = title;
        evmDate.textContent = "📅 " + (card.dataset.dateLabel || "");
        evmDescription.textContent = card.dataset.description || "No description available.";

        if (card.dataset.category) {
            evmCategory.textContent = "🏷️ " + card.dataset.category;
            evmCategory.style.display = "";
        } else {
            evmCategory.style.display = "none";
        }

        evmIconWrap.style.display = "none";

        if (poster !== "") {
            evmPosterWrap.style.display = "block";
            evmPoster.onerror = function () {
                showIconInstead(card.dataset.icon);
            };
            evmPoster.src = poster;
            evmPoster.alt = title + " poster";
            evmPosterLink.href = poster;
        } else {
            showIconInstead(card.dataset.icon);
        }

        lastFocusedCard = card;
        evm.classList.add("show");
        evm.setAttribute("aria-hidden", "false");
        document.body.style.overflow = "hidden";
        evmClose.focus();
    }

    function closeEventModal() {
        evm.classList.remove("show");
        evm.setAttribute("aria-hidden", "true");
        document.body.style.overflow = "";
        evmPoster.removeAttribute("src");

        if (lastFocusedCard) {
            lastFocusedCard.focus();
        }
    }

    eventsBody.addEventListener("click", function (e) {
        const card = e.target.closest(".event-card");

        if (card) {
            openEventModal(card);
        }
    });

    eventsBody.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
            const card = e.target.closest(".event-card");

            if (card) {
                e.preventDefault();
                openEventModal(card);
            }
        }
    });

    evmClose.addEventListener("click", closeEventModal);

    evm.addEventListener("click", function (e) {
        if (e.target === evm) {
            closeEventModal();
        }
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && evm.classList.contains("show")) {
            closeEventModal();
        }
    });

    loading.style.display = "none";
    render();
});
</script>

</body>

</html>