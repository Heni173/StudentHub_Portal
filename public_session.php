<?php
/*
 * For PUBLIC pages (Home, About, Contact, ...).
 *
 * Unlike timeout.php this file does NOT force login.
 * - Visitor not logged in  -> page opens normally (guest menu)
 * - Visitor logged in      -> session timeout still applies
 *   (when it expires the visitor simply becomes a guest)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Menu depends on who is logged in, so never cache these pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$timeoutDuration = 300; // same as timeout.php (5 minutes)

$isLoggedIn = isset($_SESSION["user_id"]) && isset($_SESSION["role"]);

if ($isLoggedIn) {
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

        $isLoggedIn = false;
    } else {
        $_SESSION["last_activity"] = time();
    }
}

$currentRole = $isLoggedIn
    ? strtolower(trim((string)($_SESSION["role"] ?? "")))
    : "";