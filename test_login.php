<?php
require_once "db.php";

$u = "heni_173";
$p = "Test@1234";

$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $u);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<pre>";
if (!$user) {
    echo "1) User DB માં મળ્યો નથી\n";
} else {
    $stored = (string)$user["password"];
    echo "1) User મળ્યો: " . $user["username"] . " (role: " . $user["role"] . ")\n";
    echo "2) Password column ની શરૂઆત: " . substr($stored, 0, 10) . "\n";
    echo "3) password_verify: " . (password_verify($p, $stored) ? "TRUE" : "FALSE") . "\n";
    echo "4) Plain-text match: " . (hash_equals($stored, $p) ? "TRUE" : "FALSE") . "\n";
}
echo "</pre>";