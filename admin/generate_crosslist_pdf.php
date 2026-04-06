<?php
/**
 * Generate Crosslist PDF
 * Uses Dompdf to convert HTML to PDF and trigger download.
 */
ob_start(); // Prevent "headers already sent" from included files
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/script/settings.php';
require_once __DIR__ . '/crosslist_functions.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    die("Invalid Course ID.");
}

// 1. Fetch Data
$students = fetch_crosslist_data($db, $course_id);
$class_info = get_class_details($db, $course_id);

if (empty($students)) {
    die("No students found for this course.");
}

// 2. Generate HTML
$html = render_crosslist_html($students, $class_info, true);

// 3. Setup Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->setPaper('A3', 'landscape');
$dompdf->loadHtml($html);

// 4. Render and Stream
$dompdf->render();

$filename = "crosslist_" . str_replace(' ', '_', $class_info['class_description']) . "_" . date('Ymd') . ".pdf";
ob_get_clean(); // Ensure no stray output from settings.php is sent
$dompdf->stream($filename, ["Attachment" => true]);
