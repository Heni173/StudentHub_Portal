<?php
/*
 * One navigation menu for the whole website.
 * The links change automatically according to who is logged in.
 * To add / remove a menu link later, change it only in this file.
 *
 * Use inside <header> of any PHP page:   <?php include "nav.php"; ?>
 */

$navRole = strtolower(trim((string)($_SESSION["role"] ?? "")));
$navLoggedIn = isset($_SESSION["user_id"]) && $navRole !== "";
$navCurrent = basename($_SERVER["PHP_SELF"] ?? "");

if ($navLoggedIn && $navRole === "admin") {

    // Admin: only admin related pages
    $navLinks = [
        ["Admin.php",                "Dashboard"],
        ["students.php",             "Students"],
        ["academic_management.php",  "Academics"],
        ["admin_events.php",         "Events"],
        ["assignments.php",          "Assignments"],
        ["announcements.php",        "Notices"],
        ["audit_logs.php",           "Audit Logs"],
        ["logout.php",               "Logout"],
    ];

} elseif ($navLoggedIn && $navRole === "student") {

    // Student: only student related pages
    $navLinks = [
        ["Home.php",                 "Home"],
        ["Student_dashboard1.php",   "Dashboard"],
        ["Profile.php",              "Profile"],
        ["Events.php",               "Events"],
        ["About.html",               "About Us"],
        ["ContactUs.html",           "Contact Us"],
        ["Feedback.html",            "Feedback"],
        ["Location.html",            "Location"],
        ["logout.php",               "Logout"],
    ];

} else {

    // Guest (not logged in)
    $navLinks = [
        ["Home.php",                 "Home"],
        ["register.php",             "Register"],
        ["login.php",                "Login"],
        ["About.html",               "About Us"],
        ["ContactUs.html",           "Contact Us"],
        ["Location.html",            "Location"],
    ];
}
?>
<nav id="navigation">
<?php foreach ($navLinks as $navItem): ?>
    <a href="<?= htmlspecialchars($navItem[0], ENT_QUOTES, "UTF-8") ?>"<?= $navItem[0] === $navCurrent ? ' class="active"' : '' ?>><?= htmlspecialchars($navItem[1], ENT_QUOTES, "UTF-8") ?></a>
<?php endforeach; ?>
</nav>