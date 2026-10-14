<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$timeoutDuration = 300;

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"])
) {
    header("Location: login.php?error=" . urlencode("Please login first."));
    exit();
}

if (
    isset($_SESSION["last_activity"]) &&
    (time() - $_SESSION["last_activity"]) >= $timeoutDuration
) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $cookie = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $cookie["path"],
            $cookie["domain"],
            $cookie["secure"],
            $cookie["httponly"]
        );
    }

    session_destroy();

    header("Location: login.php?error=" . urlencode("Session expired. Please login again."));
    exit();
}

$_SESSION["last_activity"] = time();

?>
