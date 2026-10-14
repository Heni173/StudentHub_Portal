```php
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Prevent browser from caching protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Clear all session data
$_SESSION = [];

// Remove session cookie
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

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php?message=" . urlencode("You have logged out successfully."));
exit();
?>
```