<?php
require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');

$course_type = $_GET['course_type'] ?? ''; 
$courses = [];

if ($course_type != '') {

    $stmt = $mysqli->prepare("
        SELECT 
            MIN(sno) as sno,
            group_name AS course_name
        FROM class_detail
        WHERE type = ?
        GROUP BY group_name
        ORDER BY group_name
    ");

    $stmt->bind_param("s", $course_type);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $courses[] = [
            'id'   => $row['sno'],
            'name' => $row['course_name']
        ];
    }

    $stmt->close();
}

echo json_encode([
    'success' => true,
    'courses' => $courses
]);