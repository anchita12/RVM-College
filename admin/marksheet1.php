<?php
/**
 * Marksheet Management - Single & Bulk Print
 */
error_reporting(E_ALL);
ob_start();
session_start();
include("script/settings.php");
include("crosslist_functions.php");

$roll_no = isset($_GET['roll_no']) ? $_GET['roll_no'] : null;
$course_id = isset($_GET['bulk_course_id']) ? intval($_GET['bulk_course_id']) : 0;

// Individual or Bulk Print Mode (No Sidebar/Header)
if ($roll_no || $course_id > 0) {
    echo '<!DOCTYPE html><html><head><title>Print Marksheets</title>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;700&display=swap" rel="stylesheet">';
    echo get_marksheet_css();
    echo '</head><body>';
    echo '<div class="no-print" style="text-align:center; padding: 20px;"><button onclick="window.print()" style="padding:10px 40px; background:#e0441d; color:#fff; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size: 16px;">Print Now</button></div>';

    if ($roll_no) {
        $students = fetch_crosslist_data($db, null, $roll_no);
        $student = !empty($students) ? $students[0] : null;
        if ($student) {
            $student['serial_index'] = 1;
            $type = "UG";
            if (strpos(strtoupper($student['class_description']), 'M.A') !== false || strpos(strtoupper($student['class_description']), 'MA') !== false)
                $type = "MA";
            if (strpos(strtoupper($student['class_description']), 'B.ED') !== false || strpos(strtoupper($student['class_description']), 'BED') !== false) {
                echo render_bed_marksheet($student);
            } elseif ($type == "MA") {
                echo render_ma_marksheet($student);
            } else {
                echo render_ug_marksheet($student);
            }
        } else {
            echo "<div style='text-align:center; padding:50px;'>Student not found.</div>";
        }
    } else {
        // Bulk Mode
        $search_sem = isset($_GET['sem']) ? $_GET['sem'] : null;
        $students_list = fetch_crosslist_data($db, $course_id, null, $search_sem);

        echo '<div class="marksheet-print-container">';

        foreach ($students_list as $index => $student) {
            // Start a new A3 page for every even-indexed student (0, 2, 4...)
            if ($index % 2 == 0) {
                echo '<div class="marksheet-page-wrapper">';
            }

            $student['serial_index'] = $index + 1;

            // Determine marksheet type
            $type = "UG";
            if (strpos(strtoupper($student['class_description']), 'M.A') !== false || strpos(strtoupper($student['class_description']), 'MA') !== false)
                $type = "MA";

            if (strpos(strtoupper($student['class_description']), 'B.ED') !== false || strpos(strtoupper($student['class_description']), 'BED') !== false) {
                echo render_bed_marksheet($student);
            } elseif ($type == "MA") {
                echo render_ma_marksheet($student);
            } else {
                echo render_ug_marksheet($student);
            }

            // Close the A3 page after every odd-indexed student (1, 3, 5...)
            if ($index % 2 == 1) {
                echo '</div>';
            }
        }

        // If total count is odd, close the last wrapper
        if (count($students_list) % 2 != 0) {
            echo '</div>';
        }

        echo '</div>'; // Close marksheet-print-container
    }
    echo '</body></html>';
    exit;
}


function get_student_sno($student)
{
    if (!isset($student['serial_index']))
        $student['serial_index'] = 1;
    // Remove common semester/year indicators to get clean course name
    $class_base = trim(preg_replace('/\s*(year|1st|2nd|3rd|semester|sem|i|ii|iii|iv|v|vi)\s*/i', '', $student['class_description']));
    $course_prefix = strtoupper(str_replace([' ', '.', '-'], '', $class_base));
    return $course_prefix . sprintf('%03d', $student['serial_index']);
}

/**
 * Markshtee CSS
 */
function get_marksheet_css()
{
    return '
    <style>
        body { font-family: "Helvetica", "Arial", "Noto Sans Devanagari", sans-serif; margin: 0; padding: 0; background: #fff; }

        .marksheet-print-container { 
            width: 100%; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding:10px;
        }

        /* ✅ A3 LANDSCAPE BACKGROUND FIX */
        .marksheet-page-wrapper { 
            display: flex; 
            flex-direction: row; 
            justify-content: space-between; 
            width: 420mm; 
            height: 297mm; 
            margin: 0 auto;
            box-sizing: border-box;
             background: url("images/marksheet.png") no-repeat center center;
            background-size: 420mm 297mm;
            position: relative;
        }

        /* ✅ HALF PAGE CONTENT FIX */
        .marksheet-container { 
            
            height: 297mm;
            box-sizing: border-box; 
            background: transparent;
            position: relative; /* Fixed absolute child positioning */
            /* Using padding instead of position: relative */
            /* Isse dono marksheet automatically apne side center ho jayengi */
            padding-top: 16rem; /* 🔥 YAHI MAIN FIX HAI - Top offset */
            padding-left: 4.5rem; /* Left right margin */
            padding-right: 3.5rem;
        }

        .vbspu-container {
            height: 100%;
        }

        .header-section { text-align: center; padding-bottom: 5px; margin-bottom: 5px; display: flex; align-items: center; justify-content: space-between; gap: 10px; z-index: 1; position: relative; }

        .marks-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 10px; z-index:1; position:relative; }
        .marks-table th, .marks-table td { border: 1px solid #555; padding: 5px; text-align: center; }
        .marks-table th { background: #fafafa; }

        .text-left { text-align: left !important; }

        @media print {

            @page { 
                size: A3 landscape; 
                margin: 0; 
            }

            body { margin: 0; padding: 0; }

            .no-print { display: none; }

            .marksheet-page-wrapper { 
                width: 420mm; 
                height: 297mm;
                background: url("images/marksheet.png") no-repeat center center !important;
                background-size: 420mm 297mm !important;
                page-break-after: always;
            }



        }
    </style>
    ';
}

function render_student_header($student)
{
    global $db;
    $sql = "SELECT * FROM college_settings WHERE id = 1 LIMIT 1";
    $sets_res = mysqli_query($db, $sql);
    $settings = mysqli_fetch_assoc($sets_res);
    $college_name = $settings['college_name'] ?? 'Raghuveer Mahavidyalaya Thaloi, Bhikharipur Kala, Jaunpur (U.P.)';
    $tagline = $settings['tagline'] ?? '';
    $logo = $settings['p_logo'] ?? 'images/logo.png';
    $logo_path = $logo;
    if (!file_exists($logo) && file_exists('../' . $logo)) {
        $logo_path = '../' . $logo;
    }

    $student_img = !empty($student['student_image']) ? $student['student_image'] : 'images/default_stu.png';

    // Check if image exists, otherwise use fallback (relative to admin folder as this script runs there)
    if (!file_exists(__DIR__ . '/../' . $student_img) && !file_exists($student_img)) {
        $student_img = "https://via.placeholder.com/150?text=Photo";
    }

    $sno_display = get_student_sno($student);
    $html = '<div class="header-section" style="position:relative;">';
    $html .= '<div style="position:absolute; top:-15px; right:0; font-size:12px; font-family:Arial, sans-serif;"><span style="font-weight:normal;">S. No.: </span>' . $sno_display . '</div>';
    $html .= '<div class="college-logo"><img src="' . $logo_path . '" class="college-logo"></div>';
    $html .= '<div class="college-info">';
    $html .= '<h1>' . strtoupper($college_name) . '</h1>';
    $html .= '<p>' . $tagline . '</p>';
    $html .= '</div>';
    $html .= '<div class="student-photo"><img src="' . $student_img . '"></div>';
    $html .= '</div>';

    $html .= '<div class="marksheet-title">STATEMENT OF MARKS</div>';

    $html .= '<table class="info-table">';
    $html .= '<tr>';
    $html .= '<td width="15%"><b>Roll No:</b></td><td width="35%">' . $student['exam_roll_no'] . '</td>';
    $html .= '<td width="15%"><b>UIN No:</b></td><td width="35%">' . $student['uin_no'] . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td><b>Name:</b></td><td>' . strtoupper($student['student_name']) . '</td>';
    $html .= '<td><b>Father\'s Name:</b></td><td>' . strtoupper($student['father_name']) . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';

    // Simplistic formatting for "BA I Sem" or similar
    $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];
    $semStr = $romanMap[$student['semester']] ?? $student['semester'];
    $class_base = trim(preg_replace('/\s*(year|1st|2nd|3rd|semester|i|ii|iii|iv|v|vi)\s*/i', '', $student['class_description']));
    $class_display = $class_base . ' ' . $semStr . ' Sem';

    $html .= '<td><b>Class:</b></td><td>' . $class_display . '</td>';
    $html .= '<td><b>Semester:</b></td><td>' . $student['semester'] . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    return $html;
}

function render_ug_marksheet($student)
{
    global $db;
    $sql = "SELECT * FROM college_settings WHERE id = 1 LIMIT 1";
    $sets_res = mysqli_query($db, $sql);
    $settings = mysqli_fetch_assoc($sets_res);
    $college_name = $settings['college_name'] ?? 'Raghuveer Mahavidyalaya Thaloi, Bhikharipur Kala, Jaunpur (U.P.)';
    $logo = $settings['p_logo'] ?? 'images/logo.png';
    $logo_path = $logo;
    if (!file_exists($logo) && file_exists('../' . $logo))
        $logo_path = '../' . $logo;

    $student_img = !empty($student['student_image']) ? $student['student_image'] : 'images/default_stu.png';
    if (!file_exists(__DIR__ . '/../' . $student_img) && !file_exists($student_img)) {
        $student_img = "https://via.placeholder.com/150?text=Photo";
    }

    $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];
    $semStr = $romanMap[$student['semester']] ?? $student['semester'];
    $class_base = trim(preg_replace('/\s*(year|1st|2nd|3rd|semester|i|ii|iii|iv|v|vi)\s*/i', '', $student['class_description']));

    // Auto calculate year dynamically
    if ($student['semester'] == '3' || $student['semester'] == '4') {
        $year_str = 'II Year';
    } elseif ($student['semester'] == '5' || $student['semester'] == '6') {
        $year_str = 'III Year';
    } else {
        $year_str = 'I Year';
    }

    $html = '<div class="marksheet-container vbspu-container">';

    // Watermark removed as per user request

    $tagline = $settings['tagline'] ?? '';

    // S.No Top Right (Positioned directly on certificate background)
    $sno_display = get_student_sno($student);
    $html .= '<div style="position:absolute; top:5rem; right:4.1rem; font-size:12px; font-family:Arial, sans-serif; font-weight:bold; color: #000; z-index:9;">' . $sno_display . '</div>';

    // Top headers block
    $html .= '<div style="position:relative; margin-bottom: 8px; z-index:1;">';

    // Left & Right Logos removed as per user request (assumed to be in background image)
    /*
    $html .= '<div style="position:absolute; left:10px; top:45px; width: 85px; text-align: left;"><img src="' . $logo_path . '" style="width:85px; height:85px; border-radius:50%;"></div>';
    $html .= '<div style="position:absolute; right:15px; top:45px; width: 85px; text-align: right;"><img src="../images/vbspu-logo.png" style="width:85px; height:85px;"></div>';
    */

    // College Info Block Removed as per user request (already in background image)
    /*
    $html .= '<div class="univ-headers" style="width:100%; text-align:center; padding-top: 15px;">';
    $html .= '<div style="font-size: 40px; font-weight: bold; color: #203581; line-height: 1.1; font-family: \'Times New Roman\', serif; letter-spacing: 0.5px;">Raghuveer Mahavidyalaya</div>';
    $html .= '<div style="font-size: 22px; font-weight: bold; color: #203581; margin-bottom:4px; margin-top:8px; font-family: \'Times New Roman\', serif;">Thaloi, Bhikharipur Kala, Jaunpur (U.P.) - 222143</div>';
    $html .= '<div style="font-size: 18px; color: #d32f2f; margin-bottom:3px; font-family: \'Arial\', sans-serif;">' . ($tagline ? $tagline : 'An Autonomous Institute and Accredited with \'A\' Grade by NAAC') . '</div>';
    $html .= '<div style="font-size: 18px; color: #d32f2f; margin-bottom:5px; font-family: \'Arial\', sans-serif;">(Affiliated to Veer Bahadur Singh Purvanchal University, Jaunpur, U.P.)</div>';
    $html .= '<div style="font-size: 17px; font-weight: bold; margin-bottom:2px; color: #000; font-family: \'Arial\', sans-serif;">STATEMENT OF MARKS</div>';
    $html .= '<div style="font-size: 12px; font-weight: bold; color: #000; font-family: \'Arial\', sans-serif;">' . ((date('Y') - 1) . '-' . date('y')) . '</div>';
    $html .= '</div>';
    */

    $html .= '</div>'; // End Top Headers block

    // Horizontal Line Removed as per user request (assumed to be in background image)
    //$html .= '<div style="border-top:1px solid #d32f2f; margin-bottom: 6px; position:relative; z-index:1;"></div>';

    $html .= '<table style="width:100%; font-size: 12.5px; line-height: 1.6; margin-bottom: 12px; position:relative; z-index:1; border-collapse: collapse;">';
    $html .= '<tr>';
    $html .= '<td style="width:140px; font-weight:bold; padding:4px 0;">Name</td>';
    $html .= '<td style="padding:4px 0; width:45%;">: ' . strtoupper($student['student_name']) . '</td>';
    $html .= '<td style="width:100px; font-weight:bold; padding:4px 0; white-space:nowrap;">Roll No.</td>';
    $html .= '<td style="padding:4px 0; white-space:nowrap;">: ' . $student['exam_roll_no'] . '</td>';
    $html .= '<td rowspan="4" style="width:100px; text-align:right; padding:0; vertical-align:top;">';
    $html .= '<img src="' . $student_img . '" style="width:75px; height:90px; border:1px solid #000; object-fit:cover;">';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:4px 0;">Father\'s Name</td>';
    $html .= '<td style="padding:4px 0;">: ' . strtoupper($student['father_name']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:4px 0; white-space:nowrap;">Student Type</td>';
    $html .= '<td style="padding:4px 0; white-space:nowrap;">: ' . strtoupper($student['student_type'] ?? 'REGULAR') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:4px 0;">Mother\'s Name</td>';
    $html .= '<td style="padding:4px 0;">: ' . strtoupper($student['mother_name']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:4px 0; white-space:nowrap;">Enrollment No.</td>';
    $html .= '<td style="padding:4px 0; white-space:nowrap;">: ' . ($student['enroll_no'] ?? '') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:4px 0;">Class</td>';
    $html .= '<td style="padding:4px 0;">: ' . strtoupper($student['class_description']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:4px 0; white-space:nowrap;">UIN No.</td>';
    $html .= '<td style="padding:4px 0; white-space:nowrap;">: ' . strtoupper($student['uin_no'] ?? '') . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    // Papers Table
    $html .= '<table class="marks-table">';
    $html .= '<thead><tr style="background-color: #f5f5f5;">';
    $html .= '<th width="12%" style="padding:5px;">SUB TYPE</th><th width="20%">SUBJECT NAME</th><th width="32%">PAPER NAME</th>';
    $html .= '<th>TH</th><th>SESS</th><th>TOTAL</th><th>CR</th><th>GR</th><th>G.P.</th>';
    $html .= '</tr>';
    $html .= '<tr><td colspan="9" style="text-align:center; font-weight:bold; background:#fff;">SEMESTER ' . $semStr . '</td></tr>';
    $html .= '</thead><tbody>';

    $totals = ['max' => 0, 'obt' => 0, 'credit' => 0, 'earned_credit' => 0, 'cgp' => 0];
    foreach ($student['papers'] as $p) {
        $p_th_max = (float) $p['mid_sem_max_marks'];
        $p_th_obt = (float) $p['mid_sem_marks_obt'];
        $p_sess_max = (float) $p['max_marks'];
        $p_sess_obt = (float) $p['marks_obt'];
        $p_prac_max = (float) $p['practical_max_marks'];
        $p_prac_obt = (float) $p['practical_marks_obt'];

        $p_max = $p_th_max + $p_sess_max + $p_prac_max;
        $p_obt = $p_th_obt + $p_sess_obt + $p_prac_obt;

        $totals['max'] += $p_max;
        $totals['obt'] += $p_obt;
        $cr = (float) ($p['academic_credit'] ?? $p['credit'] ?? 4);
        $totals['credit'] += $cr;

        $grade = calculate_grade_letter($p_obt, $p_max);
        $grade_point = calculate_grade($p_obt, $p_max);
        $p_cgp = $cr * $grade_point;

        if ($grade_point >= 4) {
            $totals['earned_credit'] += $cr;
        }
        if ($p_max > 0) {
            $totals['cgp'] += $p_cgp;
        }

        $sub_type = strtoupper($p['paper_type'] ?? $p['subject_type_name'] ?? 'MAJOR');

        $html .= '<tr>';
        $html .= '<td>' . $sub_type . '</td>';
        $html .= '<td class="text-left">' . strtoupper($p['subject_name']) . '</td>';
        $html .= '<td class="text-left">[' . $p['paper_code'] . '] ' . strtoupper($p['paper_title']) . '</td>';
        $html .= '<td>' . ($p['has_theory'] || $p['practical_max_marks'] > 0 ? (int) $p_th_obt : '-') . '</td>';
        $html .= '<td>' . ($p['has_internal'] ? (int) $p_sess_obt : '-') . '</td>';
        $html .= '<td class="fw-bold">' . (int) $p_obt . '</td>';
        $html .= '<td>' . $cr . '</td>';
        $html .= '<td class="fw-bold">' . $grade . '</td>';
        $html .= '<td class="fw-bold">' . $grade_point . '</td>';
        $html .= '</tr>';
    }

    // Total Credits row inside marks table
    $html .= '<tr style="background:#f8f9fa; font-weight:bold;">';
    $html .= '<td colspan="6" style="text-align:center;">TOTAL CREDITS</td>';
    $html .= '<td>' . (int) $totals['credit'] . '</td>';
    $html .= '<td colspan="2"></td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';

    // SGPA formula matching NEP standard: Total(Credit * GP) / Total Credits
    $sgpa = ($totals['credit'] > 0) ? number_format($totals['cgp'] / $totals['credit'], 2) : '0.00';
    $status = ($totals['earned_credit'] >= ($totals['credit'] * 0.5)) ? "PASS" : "FAIL";

    // Summary Tables Container
    $html .= '<div style="display:flex; justify-content:space-between; gap:15px; margin-top: 15px;">';

    // Semester Record Table
    $html .= '<table class="marks-table" style="flex:1.5; margin-top:0;">';
    $html .= '<thead style="background:#e9ecef;"><tr><th colspan="5">Semester I Records</th></tr>';
    $html .= '<tr><th>SEM</th><th>Total Credits</th><th>Total Credit Earned</th><th>SGPA</th><th>RESULT</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr><td class="fw-bold">' . $semStr . '</td><td>' . (int) $totals['credit'] . '</td><td>' . (int) $totals['earned_credit'] . '</td><td class="fw-bold">' . $sgpa . '</td><td class="fw-bold">' . $status . '</td></tr>';
    $html .= '</tbody></table>';

    // Cumulative Record Table
    $html .= '<table class="marks-table" style="flex:1; margin-top:0;">';
    $html .= '<thead style="background:#e9ecef;"><tr><th colspan="3">Cumulative Record</th></tr>';
    $html .= '<tr><th>Total Credits</th><th>Total Credit Earned</th><th>CGPA</th></tr></thead>';
    $html .= '<tbody>';
    $html .= '<tr><td>' . (int) $totals['credit'] . '</td><td>' . (int) $totals['earned_credit'] . '</td><td class="fw-bold">' . $sgpa . '</td></tr>';
    $html .= '</tbody></table>';

    $html .= '</div>';

    // Footer section (Result, Date, Signatures)
    $result_date = !empty($student['result_declaration_date']) ? date('d-m-Y', strtotime($student['result_declaration_date'])) : date('d-m-Y');

    // QR Code Generation
    $total_max_marks = 0;
    $total_obt_marks = 0;
    if (isset($student['papers']) && is_array($student['papers'])) {
        foreach ($student['papers'] as $p) {
            $total_max_marks += (float) $p['mid_sem_max_marks'] + (float) $p['max_marks'] + (float) $p['practical_max_marks'];
            $total_obt_marks += (float) $p['mid_sem_marks_obt'] + (float) $p['marks_obt'] + (float) $p['practical_marks_obt'];
        }
    }
    $marks_display = (int) $total_obt_marks . " / " . (int) $total_max_marks;

    // Fetch DOB and Mobile
    $q_extra = mysqli_query($db, "SELECT dob, p_mobile, form_no FROM student_info WHERE sno = '" . $student['exam_sno'] . "' OR enroll_no = '" . ($student['enroll_no'] ?? '') . "' LIMIT 1");
    $extra = mysqli_fetch_assoc($q_extra);
    $dob_qr = !empty($extra['dob']) ? date('d-m-Y', strtotime($extra['dob'])) : 'N/A';
    $mobile_qr = !empty($extra['p_mobile']) ? $extra['p_mobile'] : 'N/A';
    $form_no_qr = !empty($extra['form_no']) ? $extra['form_no'] : ($student['exam_sno'] ?? 'N/A');

    $qr_data = "Full Name  - " . strtoupper($student['student_name']) . "\n";
    $qr_data .= "Father's name - " . strtoupper($student['father_name']) . "\n";
    $qr_data .= "Class - " . $student['class_description'] . "\n";
    $qr_data .= "Date of Birth - " . $dob_qr . "\n";
    $qr_data .= "Mobile No - " . $mobile_qr . "\n";
    $qr_data .= "Exam Form No - " . $form_no_qr . "\n";
    $qr_data .= "Roll No  - " . $student['exam_roll_no'] . "\n";
    $qr_data .= "UIN Number - " . ($student['uin_no'] ?? 'N/A') . "\n";
    $qr_data .= "Marks  - " . $marks_display . "\n";
    $qr_data .= "passing_status  - " . $status;

    $qr_image_url = "phpqrcode/qr_generate.php?data=" . urlencode($qr_data);

    $html .= '<div style="display:flex; justify-content:space-between; font-size: 11px; font-weight: bold; margin-top: auto; position:relative; z-index:1; align-items: flex-end;">';

    // Left: QR Code & Result & Date
    $html .= '<div style="display:flex; align-items:flex-end;">';
    if (isset($student['qr_status']) && $student['qr_status'] == 1) {
        $html .= '<div style="margin-right:15px;"><img src="' . $qr_image_url . '" style="width:5rem; height:5rem; border:1px solid #ddd; padding:2px;"></div>';
    } else {
        $html .= '<div style="margin-right:15px; width:110px; height:110px;"></div>';
    }
    $html .= '<div>';
    $html .= '<div style="margin-bottom:15px; font-size:14px;">RESULT &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <span>' . $status . '</span></div>';
    $html .= '<div>DATE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: ' . $result_date . '</div>';
    $html .= '</div>';
    $html .= '</div>';

    // Right block: Signatures
    $html .= '<div style="display:flex; flex:1; justify-content:space-around; align-items:flex-end;">';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 25px;">Checked By</div>';
    $html .= '<div style="color:#1976d2;"><i>........................</i></div>';
    $html .= '<div>Full Name</div>';
    $html .= '</div>';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 25px;">Signature</div>';
    $html .= '<div style="color:#1976d2;"><i>........................</i></div>';
    $html .= '<div>Full Name</div>';
    $html .= '</div>';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 5px;">';
    // Dummy signature placeholder
    $html .= '<img src="../images/principal_sign.jpg" style="height:45px;">';
    $html .= '</div>';
    $html .= '<div>(ABHISHEK MISHRA)<br>Controller of Examination</div>';
    $html .= '</div>';

    $html .= '</div>'; // End right block

    $html .= '</div>'; // End Footer section

    $html .= '</div>'; // End container
    return $html;
}

function render_ma_marksheet($student)
{
    // Simplified to use UG layout as per user request
    return render_ug_marksheet($student);
}

function render_bed_marksheet($student)
{
    global $db;
    $sql = "SELECT * FROM college_settings WHERE id = 1 LIMIT 1";
    $sets_res = mysqli_query($db, $sql);
    $settings = mysqli_fetch_assoc($sets_res);
    $college_name = $settings['college_name'] ?? 'Raghuveer Mahavidyalaya Thaloi, Bhikharipur Kala, Jaunpur (U.P.)';
    $logo = $settings['p_logo'] ?? 'images/logo.png';
    $logo_path = $logo;
    if (!file_exists($logo) && file_exists('../' . $logo))
        $logo_path = '../' . $logo;

    $student_img = !empty($student['student_image']) ? $student['student_image'] : 'images/default_stu.png';
    if (!file_exists(__DIR__ . '/../' . $student_img) && !file_exists($student_img)) {
        $student_img = "https://via.placeholder.com/150?text=Photo";
    }

    $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];
    $semStr = $romanMap[$student['semester']] ?? $student['semester'];
    $class_base = trim(preg_replace('/\s*(year|1st|2nd|3rd|semester|i|ii|iii|iv|v|vi)\s*/i', '', $student['class_description']));

    // Auto calculate year dynamically
    if ($student['semester'] == '3' || $student['semester'] == '4') {
        $year_str = 'II Year';
    } elseif ($student['semester'] == '5' || $student['semester'] == '6') {
        $year_str = 'III Year';
    } else {
        $year_str = 'I Year';
    }

    $html = '<div class="marksheet-container vbspu-container">';

    // Watermark removed as per user request

    $tagline = $settings['tagline'] ?? '';

    // S.No Top Right (Positioned directly on certificate background)
    $sno_display = get_student_sno($student);
    $html .= '<div style="position:absolute; top:135px; right:60px; font-size:15px; font-family:Arial, sans-serif; font-weight:bold; color: #000; z-index:9;">' . $sno_display . '</div>';

    // Top headers block
    $html .= '<div style="position:relative; margin-bottom: 8px; z-index:1;">';

    // Left & Right Logos removed as per user request (assumed to be in background image)
    /*
    $html .= '<div style="position:absolute; left:10px; top:45px; width: 85px; text-align: left;"><img src="' . $logo_path . '" style="width:85px; height:85px; border-radius:50%;"></div>';
    $html .= '<div style="position:absolute; right:15px; top:45px; width: 85px; text-align: right;"><img src="../images/vbspu-logo.png" style="width:85px; height:85px;"></div>';
    */

    // College Info Block Removed as per user request (already in background image)
    /*
    $html .= '<div class="univ-headers" style="width:100%; text-align:center; padding-top: 15px;">';
    $html .= '<div style="font-size: 38px; font-weight: bold; color: #203581; line-height: 1.1; font-family: \'Times New Roman\', serif; letter-spacing: 0.5px;">Raghuveer Mahavidyalaya</div>';
    $html .= '<div style="font-size: 20px; font-weight: bold; color: #203581; margin-bottom:4px; margin-top:8px; font-family: \'Times New Roman\', serif;">Thaloi, Bhikharipur Kala, Jaunpur (U.P.) - 222143</div>';
    $html .= '<div style="font-size: 11px; color: #d32f2f; margin-bottom:3px; font-family: \'Arial\', sans-serif;">' . ($tagline ? $tagline : 'An Autonomous Institute and Accredited with \'A\' Grade by NAAC') . '</div>';
    $html .= '<div style="font-size: 11px; color: #d32f2f; margin-bottom:5px; font-family: \'Arial\', sans-serif;">(Affiliated to Veer Bahadur Singh Purvanchal University, Jaunpur, U.P.)</div>';
    $html .= '<div style="font-size: 11px; font-weight: bold; margin-bottom:2px; color: #000; font-family: \'Arial\', sans-serif;">STATEMENT OF MARKS</div>';
    $html .= '<div style="font-size: 12px; font-weight: bold; color: #000; font-family: \'Arial\', sans-serif;">' . ((date('Y') - 1) . '-' . date('y')) . '</div>';
    $html .= '</div>';
    */

    $html .= '</div>'; // End Top Headers block

    // Horizontal Line Removed as per user request (assumed to be in background image)
    //$html .= '<div style="border-top:1px solid #d32f2f; margin-bottom: 6px; position:relative; z-index:1;"></div>';

    // Student Info Block unified into a single table for perfect row alignment
    $html .= '<table style="width:100%; font-size: 11.5px; line-height: 1.5; margin-bottom: 8px; position:relative; z-index:1; border-collapse: collapse;">';
    $html .= '<tr>';
    $html .= '<td style="width:130px; font-weight:bold; padding:3px 0;">Name</td>';
    $html .= '<td style="padding:3px 0; width:45%;">: ' . strtoupper($student['student_name']) . '</td>';
    $html .= '<td style="width:90px; font-weight:bold; padding:3px 0; white-space:nowrap;">Roll No.</td>';
    $html .= '<td style="padding:3px 0; white-space:nowrap;">: ' . $student['exam_roll_no'] . '</td>';
    $html .= '<td rowspan="5" style="width:13%; text-align:right; padding:0; vertical-align:top;">';
    $html .= '<img src="' . $student_img . '" style="width:60px; height:75px; border:1px solid #000; object-fit:cover;">';
    $html .= '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:3px 0;">Father\'s Name</td>';
    $html .= '<td style="padding:3px 0;">: ' . strtoupper($student['father_name']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:3px 0; white-space:nowrap;">Student Type</td>';
    $html .= '<td style="padding:3px 0; white-space:nowrap;">: ' . strtoupper($student['student_type'] ?? 'REGULAR') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:3px 0;">Mother\'s Name</td>';
    $html .= '<td style="padding:3px 0;">: ' . strtoupper($student['mother_name']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:3px 0; white-space:nowrap;">Enrollment No.</td>';
    $html .= '<td style="padding:3px 0; white-space:nowrap;">:</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:3px 0;">Class</td>';
    $html .= '<td style="padding:3px 0;">: ' . strtoupper($student['class_description']) . '</td>';
    $html .= '<td style="font-weight:bold; padding:3px 0; white-space:nowrap;">UIN No.</td>';
    $html .= '<td style="padding:3px 0; white-space:nowrap;">: ' . strtoupper($student['uin_no'] ?? '') . '</td>';
    $html .= '</tr>';
    $html .= '<tr>';
    $html .= '<td style="font-weight:bold; padding:3px 0; white-space:nowrap;">Name of Institution</td>';
    $html .= '<td colspan="3" style="padding:3px 0; white-space:nowrap;">: 0693 - ' . strtoupper($college_name) . '</td>';
    $html .= '</tr>';
    $html .= '</table>';

    // Papers Table
    $html .= '<table class="marks-table">';
    $html .= '<thead><tr style="background-color: #f5f5f5;">';
    $html .= '<th colspan="2" rowspan="2" style="padding:6px;">PAPERS</th><th colspan="2" style="padding:6px;">Maximum Marks</th><th colspan="2" style="padding:6px;">Marks Obtained</th><th rowspan="2" style="padding:6px;">TOTAL</th>';
    $html .= '</tr>';
    $html .= '<tr style="background-color: #f5f5f5;"><th>Exam</th><th>Sessional</th><th>Exam</th><th>Sessional</th></tr>';
    $html .= '</thead><tbody>';

    $theory_max = 0;
    $theory_obt = 0;
    $practical_max = 0;
    $practical_obt = 0;
    $major_count = 0;
    $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];

    $theory_papers = [];
    $practical_papers = [];

    foreach ($student['papers'] as $p) {
        if ($p['practical_max_marks'] > 0 || strpos(strtolower($p['paper_title']), 'practical') !== false) {
            $practical_papers[] = $p;
        } else {
            $theory_papers[] = $p;
        }
    }

    foreach ($theory_papers as $p) {
        $p_exam_max = (float) $p['mid_sem_max_marks'] > 0 ? (float) $p['mid_sem_max_marks'] : 90;
        $p_sess_max = (float) $p['max_marks'] > 0 ? (float) $p['max_marks'] : 10;

        $p_exam_obt = (float) $p['mid_sem_marks_obt'];
        $p_sess_obt = (float) $p['marks_obt'];

        $p_total = $p_exam_obt + $p_sess_obt;
        $theory_max += ($p_exam_max + $p_sess_max);
        $theory_obt += $p_total;

        $type_label = $p['subject_type_name'] ?? 'Major';
        if (strtolower((string) $type_label) == 'major' || trim($type_label) == '') {
            $major_count++;
            $type_label = 'PAPER - ' . ($romanMap[$major_count] ?? $major_count);
        }

        $html .= '<tr>';
        $html .= '<td width="15%" style="text-align: left; font-weight:bold; padding-left:10px;">' . $type_label . '</td>';
        $html .= '<td class="text-left" width="35%">: ' . strtoupper($p['paper_title']) . '</td>';
        $html .= '<td>' . ($p_exam_max > 0 ? (int) $p_exam_max : '-') . '</td>';
        $html .= '<td>' . ($p_sess_max > 0 ? (int) $p_sess_max : '-') . '</td>';
        $html .= '<td>' . str_pad((int) $p_exam_obt, 2, '0', STR_PAD_LEFT) . '</td>';
        $html .= '<td>' . str_pad((int) $p_sess_obt, 3, '0', STR_PAD_LEFT) . '</td>';
        $html .= '<td style="font-weight:bold;">' . (int) $p_total . '</td>';
        $html .= '</tr>';
    }

    if (!empty($practical_papers)) {
        $html .= '<tr style="background:#f9f9f9; font-weight:bold;"><td colspan="7" class="text-left" style="padding:6px 10px;">Practical/Viva-Voce</td></tr>';

        foreach ($practical_papers as $p) {
            $prac_max = (float) $p['practical_max_marks'] > 0 ? (float) $p['practical_max_marks'] : 100;
            $prac_obt = (float) $p['practical_marks_obt'] > 0 ? (float) $p['practical_marks_obt'] : (float) $p['marks_obt'];

            $practical_max += $prac_max;
            $practical_obt += $prac_obt;

            $html .= '<tr>';
            $html .= '<td style="text-align: left; font-weight:bold; padding-left:10px;">PRACTICAL</td>';
            $html .= '<td></td>';
            $html .= '<td>' . (int) $prac_max . '</td>';
            $html .= '<td></td>';
            $html .= '<td>' . str_pad((int) $prac_obt, 3, '0', STR_PAD_LEFT) . '</td>';
            $html .= '<td></td>';
            $html .= '<td style="font-weight:bold;">' . (int) $prac_obt . '</td>';
            $html .= '</tr>';
        }
    }

    $html .= '</tbody></table>';

    // Results Section inside a table to match image layout
    $html .= '<table class="marks-table" style="margin-top:-6px; border-top:none; background:#f5f5f5; font-size:11px;"><tbody>';

    $theory_status = ($theory_obt >= ($theory_max * 0.36)) ? "PASSED" : "FAILED";
    $prac_status = ($practical_max > 0 && $practical_obt >= ($practical_max * 0.36)) ? "PASSED" : ($practical_max == 0 ? "PASSED" : "FAILED");

    $html .= '<tr>';
    $html .= '<td style="text-align:left; padding:18px 20px; font-weight:bold; border-right:none;" width="50%">THEORY RESULT : ' . $theory_status . '</td>';
    $html .= '<td style="text-align:right; padding:18px 20px; font-weight:bold; border-left:none;" width="50%">Marks Obtained Theory : ' . (int) $theory_obt . ' / ' . (int) $theory_max . '</td>';
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td style="text-align:left; padding:18px 20px; font-weight:bold; border-right:none;">PRACTICAL RESULT : ' . $prac_status . '</td>';
    $html .= '<td style="text-align:right; padding:18px 20px; font-weight:bold; border-left:none;">Marks Obtained Practical : ' . (int) $practical_obt . ' / ' . (int) $practical_max . '</td>';
    $html .= '</tr>';

    $result_date = !empty($student['result_declaration_date']) ? date('d-m-Y', strtotime($student['result_declaration_date'])) : date('d-m-Y');

    $html .= '<tr>';
    $html .= '<td colspan="2" style="text-align:left; padding:15px 20px; font-weight:bold; border-top:none;">DATED : ' . $result_date . '</td>';
    $html .= '</tr>';

    $html .= '</tbody></table>';

    // Signatures (Re-using the style from UG)
    // QR Code Generation
    $marks_display = (int) ($theory_obt + $practical_obt) . " / " . (int) ($theory_max + $practical_max);

    // Fetch DOB and Mobile
    $q_extra = mysqli_query($db, "SELECT dob, p_mobile, form_no FROM student_info WHERE sno = '" . $student['exam_sno'] . "' OR enroll_no = '" . ($student['enroll_no'] ?? '') . "' LIMIT 1");
    $extra = mysqli_fetch_assoc($q_extra);
    $dob_qr = !empty($extra['dob']) ? date('d-m-Y', strtotime($extra['dob'])) : 'N/A';
    $mobile_qr = !empty($extra['p_mobile']) ? $extra['p_mobile'] : 'N/A';
    $form_no_qr = !empty($extra['form_no']) ? $extra['form_no'] : ($student['exam_sno'] ?? 'N/A');

    $status_qr = ($theory_status == "PASSED" && $prac_status == "PASSED") ? "PASSED" : "FAILED";

    $qr_data = "Full Name  - " . strtoupper($student['student_name']) . "\n";
    $qr_data .= "Father's name - " . strtoupper($student['father_name']) . "\n";
    $qr_data .= "Class - " . $student['class_description'] . "\n";
    $qr_data .= "Date of Birth - " . $dob_qr . "\n";
    $qr_data .= "Mobile No - " . $mobile_qr . "\n";
    $qr_data .= "Exam Form No - " . $form_no_qr . "\n";
    $qr_data .= "Roll No  - " . $student['exam_roll_no'] . "\n";
    $qr_data .= "UIN Number - " . ($student['uin_no'] ?? 'N/A') . "\n";
    $qr_data .= "Marks  - " . $marks_display . "\n";
    $qr_data .= "passing_status  - " . $status_qr;

    $qr_image_url = "phpqrcode/qr_generate.php?data=" . urlencode($qr_data);

    $html .= '<div style="display:flex; justify-content:space-between; font-size: 10px; font-weight: bold; margin-top: auto; position:relative; z-index:1; align-items: flex-end;">';

    $html .= '<div style="padding-top:10px; padding-left:15px; text-align:left; display:flex; align-items:flex-end;">';
    if (isset($student['qr_status']) && $student['qr_status'] == 1) {
        $html .= '<div style="margin-right:15px;"><img src="' . $qr_image_url . '" style="width:5rem; height:5rem; border:1px solid #ddd; padding:2px;"></div>';
    } else {
        $html .= '<div style="margin-right:15px; width:110px; height:110px;"></div>';
    }
    $html .= '<div>';
    $html .= '<div>Principal\'s Signature :</div>';
    $html .= '<div style="margin-bottom: 2px; min-width:120px; border-bottom:1px dotted #555; height:20px;"></div>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= '<div style="display:flex; flex:1; justify-content:space-around; align-items:flex-end;">';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 25px;">1 Signature :</div>';
    $html .= '<div style="color:#1976d2;"><i>........................</i></div>';
    $html .= '<div>Full Name</div>';
    $html .= '</div>';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 25px;">Checked By :</div>';

    $html .= '</div>';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 25px;">2 Signature :</div>';
    $html .= '<div style="color:#1976d2;"><i>........................</i></div>';
    $html .= '<div>Full Name</div>';
    $html .= '</div>';

    $html .= '<div style="text-align:center;">';
    $html .= '<div style="margin-bottom: 5px;">';
    $html .= '<img src="" style="height:25px; visibility:hidden;" alt="sign">';
    $html .= '</div>';
    $html .= '<div>(ABHISHEK MISHRA)<br>Controller of Examination</div>';
    $html .= '</div>';

    $html .= '</div>'; // End right block
    $html .= '</div>'; // End signature row

    $html .= '</div>'; // End container
    return $html;
}

// Normal Interface Mode
if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Marksheet Management</title>
    <style>
        .card-box {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }

        .card-heading {
            font-size: 20px;
            font-weight: 700;
            color: #e0441d;
            margin-bottom: 20px;
            border-left: 5px solid #e0441d;
            padding-left: 15px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #e0441d;
        }

        .btn-action {
            background: #e0441d;
            color: #fff;
            border: none;
            padding: 11px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-action:hover {
            background: #c03615;
            color: #fff;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
        }

        .student-table th,
        .student-table td {
            border-bottom: 1px solid #eee;
            padding: 12px;
            text-align: left;
        }

        .student-table th {
            background: #fafafa;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
        }

        .btn-view {
            background: #eee;
            color: #333;
            text-decoration: none;
            padding: 5px 12px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body style="background: #f8f9fa;">

    <div style="padding: 25px;">
        <div class="card-box">
            <div class="card-heading">Generate Course-Wise Marksheets</div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group" style="max-width: 500px;">
                        <label class="form-label">Select Course (BA / MA / B.Ed)</label>
                        <select name="search_course_id" class="form-control" required>
                            <option value="">-- Choose Class --</option>
                            <?php
                            $classQ = mysqli_query($db, "SELECT sno, class_description FROM class_detail WHERE 
                                                    (class_description LIKE '%B.A%' OR class_description LIKE '%BA%' OR 
                                                     class_description LIKE '%M.A%' OR class_description LIKE '%MA%' OR 
                                                     class_description LIKE '%B.ED%' OR class_description LIKE '%BED%') 
                                                     ORDER BY class_description ASC");
                            while ($c = mysqli_fetch_assoc($classQ)) {
                                $sel = (isset($_POST['search_course_id']) && $_POST['search_course_id'] == $c['sno']) ? 'selected' : '';
                                echo '<option value="' . $c['sno'] . '" ' . $sel . '>' . $c['class_description'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group" style="max-width: 200px;">
                        <label class="form-label">Semester</label>
                        <select name="search_sem" class="form-control">
                            <option value="">-- All Sem --</option>
                            <option value="1">Semester I</option>
                            <option value="2">Semester II</option>
                            <option value="3">Semester III</option>
                            <option value="4">Semester IV</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="btn_view_all" class="btn-action">Search & View All
                            Marksheets</button>
                    </div>
                </div>
            </form>
        </div>

        <?php
        if (isset($_POST['btn_view_all']) && isset($_POST['search_course_id'])) {
            $sem = isset($_POST['search_sem']) ? $_POST['search_sem'] : '';
            header("Location: marksheet1.php?bulk_course_id=" . $_POST['search_course_id'] . "&sem=" . $sem);
            exit;
        }
        ?>
    </div>

</body>

</html>
<?php if (function_exists('page_footer'))
    page_footer(); ?>