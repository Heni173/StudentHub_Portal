
<?php
require_once "db_students.php";

$sql = "SELECT student_id, name, email, course, status
        FROM students
        WHERE status = 'active'
        ORDER BY student_id ASC";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    exit("Unable to export student records.");
}

if ($result->num_rows === 0) {
    exit("No active student records available to export.");
}

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=student_records.csv");

$output = fopen("php://output", "w");

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "Student ID",
    "Student Name",
    "Email",
    "Course",
    "Status"
]);

while ($student = $result->fetch_assoc()) {
    fputcsv($output, [
        $student["student_id"],
        $student["name"],
        $student["email"],
        $student["course"],
        $student["status"]
    ]);
}

fclose($output);
$result->free();
$conn->close();
exit;
?>
