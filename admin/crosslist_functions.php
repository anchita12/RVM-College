<?php
/**
 * Crosslist Functions
 * Centralized logic for database retrieval and rendering.
 * Following DRY principles.
 */

/**
 * Fetch students and their marks for a specific course and session.
 */
function fetch_crosslist_data($db, $course_id = null, $roll_no = null, $semester = null) {
    // 1. Fetch Students
    $where = "WHERE 1=1 ";
    if ($course_id) $where .= " AND esi.course_name = '" . mysqli_real_escape_string($db, $course_id) . "'";
    if ($roll_no) $where .= " AND esi.exam_roll_no = '" . mysqli_real_escape_string($db, $roll_no) . "'";
    if ($semester) $where .= " AND cd.semester = '" . mysqli_real_escape_string($db, $semester) . "'";
    $where .= " AND esi.verify_status=1";

    $sql = "SELECT esi.sno as exam_sno, esi.student_name, esi.exam_roll_no, esi.uin_no, esi.result_srno, esi.student_type,
                   si.father_name, si.mother_name, si.gender, si.photo_id as student_image, si.enroll_no,
                   cd.class_description, cd.semester, cd.category, cd.sno as course_id, cd.result_declaration_date
            FROM exam_student_info esi
            LEFT JOIN student_info si ON esi.student_info_sno = si.sno
            LEFT JOIN class_detail cd ON esi.course_name = cd.sno
            $where
            ORDER BY esi.exam_roll_no ASC";
    
    $res = mysqli_query($db, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        // Fetch Marks for each student
        $marks_sql = "SELECT espi.*, asp.paper_code, asp.paper_title, asp.theory_practical,
                             asp.max_marks, asp.mid_sem_max_marks, asp.practical_max_marks,
                             asp.has_theory, asp.has_internal, asp.has_practical,
                             asp.academic_credit as credit, asp.paper_type,
                             asub.subject as subject_name,
                             st.type as subject_type_name
                    FROM exam_student_paper_info espi
                    JOIN add_subject_papers asp ON espi.add_subject_papers_sno = asp.sno 
                    LEFT JOIN add_subject asub ON asp.subject_id = asub.sno
                    LEFT JOIN subject_type st ON asub.subject_type = st.sno
                    WHERE espi.exam_student_info_sno = '" . $row['exam_sno'] . "'
                    ORDER BY asp.sno ASC";
        
        $marks_res = mysqli_query($db, $marks_sql);
        $marks = [];
        while ($m = mysqli_fetch_assoc($marks_res)) {
            $marks[] = $m;
        }
        $row['papers'] = $marks;
        
        // Ensure consistent sequence for UG (Major -> Minor -> Vocational -> Co-curricular)
        $is_ug = (strtoupper($row['category'] ?? '') === 'UG');
        if ($is_ug && !empty($row['papers'])) {
            usort($row['papers'], function($a, $b) {
                $order = ['major' => 1, 'minor' => 2, 'vocational' => 3, 'co-curricular' => 4];
                $getType = function($p) {
                    $t = strtolower($p['paper_type'] ?? $p['subject_type_name'] ?? 'major');
                    return $t;
                };
                $valA = $order[$getType($a)] ?? 99;
                $valB = $order[$getType($b)] ?? 99;
                if ($valA == $valB) return strcmp($a['paper_code'] ?? '', $b['paper_code'] ?? '');
                return $valA - $valB;
            });
        }
        
        $data[] = $row;
    }
    
    return $data;
}


function getSubjectName($db, $addSubjectPaperSno) {

try {
    // Step 1: Fetch subject id from add_subject_papers
    $sql1 = "SELECT subject_id 
             FROM add_subject_papers 
             WHERE sno = '$addSubjectPaperSno'";

    $res1 = mysqli_query($db, $sql1);
    $row1 = mysqli_fetch_assoc($res1);

    if (!$row1) {
        return '';
    }

    $subject_id = $row1['subject_id'];

    // Step 2: Fetch subject name from add_subject
    $sql2 = "SELECT subject 
             FROM add_subject 
             WHERE sno = '$subject_id'";

    $res2 = mysqli_query($db, $sql2);
    $row2 = mysqli_fetch_assoc($res2);

    if (!$row2) {
        return '';
    }

    return $row2['subject'];
} catch (\Exception $e) {
    return 'N/A';
}
}

function calculate_grade_letter($marks_obt, $marks_max) {
    if ($marks_obt == 'Abs' || $marks_obt == 'ABS' || $marks_obt == 'AB(Absent)') {
        $grade_letter = 'AB';
    } elseif ($marks_obt == 'Inc') {
        $grade_letter = 'F';
    } else {
        if ($marks_max == '0' || $marks_max == 0) {
            $marksPercent = 0;
        } else {
            $marksPercent = ($marks_obt * 100) / $marks_max;
        }

        if ($marksPercent >= 91 && $marksPercent <= 100) {
            $grade_letter = 'O';
        } elseif ($marksPercent >= 81 && $marksPercent <= 90) {
            $grade_letter = 'A+';
        } elseif ($marksPercent >= 71 && $marksPercent <= 80) {
            $grade_letter = 'A';
        } elseif ($marksPercent >= 61 && $marksPercent <= 70) {
            $grade_letter = 'B+';
        } elseif ($marksPercent >= 51 && $marksPercent <= 60) {
            $grade_letter = 'B';
        } elseif ($marksPercent >= 41 && $marksPercent <= 50) {
            $grade_letter = 'C';
        } elseif ($marksPercent >= 33 && $marksPercent <= 40) {
            $grade_letter = 'D';
        } elseif ($marksPercent >= 0 && $marksPercent <= 32) {
            $grade_letter = 'F';
        } else {
            $grade_letter = 'F';
        }
    }
    return $grade_letter;
}

function calculate_grade($marks_obt, $marks_max) {
    if ($marks_obt == 'Abs' || $marks_obt == 'ABS' || $marks_obt == 'AB(Absent)') {
        $grade = 0;
    } else {
        if ($marks_max == '0' || $marks_max == 0) {
            $marksPercent = 0;
        } else {
            $marksPercent = ($marks_obt * 100) / $marks_max;
        }

        if ($marksPercent >= 91 && $marksPercent <= 100) {
            $grade = 10;
        } elseif ($marksPercent >= 81 && $marksPercent < 91) {
            $grade = 9;
        } elseif ($marksPercent >= 71 && $marksPercent < 81) {
            $grade = 8;
        } elseif ($marksPercent >= 61 && $marksPercent < 71) {
            $grade = 7;
        } elseif ($marksPercent >= 51 && $marksPercent < 61) {
            $grade = 6;
        } elseif ($marksPercent >= 41 && $marksPercent < 51) {
            $grade = 5;
        } elseif ($marksPercent >= 33 && $marksPercent < 41) {
            $grade = 4;
        } else {
            $grade = 0;
        }
    }
    return $grade;
}



/**
 * Fetch class details for header.
 */
function get_class_details($db, $course_id) {
    $sql = "SELECT * FROM class_detail WHERE sno = '$course_id'";
    $res = mysqli_query($db, $sql);
    return mysqli_fetch_assoc($res);
}

/**999999
 * Render the Crosslist HTML.
 * Used for both Preview and PDF generation.
 */
function render_crosslist_html($students, $class_info, $is_pdf = false) {
    global $db;
    $is_bed = ($class_info['sno'] == 7 || strpos(strtoupper($class_info['class_description'] ?? ''), 'B.ED') !== false || strpos(strtoupper($class_info['class_description'] ?? ''), 'BED') !== false);
    ob_start();
    $exam_session = (date('Y') - 1) . '-' . date('Y');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap" rel="stylesheet">
        <title>Exam Crosslist - RvM</title>
        <style>
            <?php if ($is_pdf): ?>
            @page {
                size: A3 landscape;
                margin: 40mm 10mm 20mm 10mm;
            }
            .header-info { 
                position: fixed; 
                top: -35mm; 
                left: 0; 
                right: 0; 
                height: 30mm; 
                text-align: center;
                background: white;
                font-family: 'HindiFont', 'DejaVu Sans', sans-serif;
            }
            body { padding-top: 0; }
            * { -webkit-print-color-adjust: exact !important; color-adjust: exact !important; }
            <?php else: ?>
            @media print {
                @page {
                    size: A3 landscape;
                    margin: 5mm;
                }
                body { font-size: 10px; }
                .noprint { display: none; }
                * { -webkit-print-color-adjust: exact !important; }
            }
            <?php endif; ?>
            @font-face {
                font-family: 'HindiFont';
                src: url('https://fonts.gstatic.com/s/notosansdevanagari/v14/Op6Adf20_o0bL7u560O11lX2F26z6f90.ttf') format('truetype');
            }
            body { font-family: 'HindiFont', 'DejaVu Sans', sans-serif; margin: 0; padding: 10px; font-size: 11px; }
            .header-info h1 { font-size: 18px; margin: 5px 0; border-bottom: 2px solid #000; display: inline-block; padding-bottom: 5px; }
            .header-info h2 { font-size: 14px; margin: 5px 0; }
            
            .student-record { 
                width: 100%; 
                border: 1px solid black; 
                margin-bottom: 15px; 
                page-break-inside: avoid;
                border-collapse: collapse;
            }
            
            .student-record td { border: 1px solid black; vertical-align: top; padding: 2px; }
            
            .info-table { width: 100%; border: none !important; }
            .info-table td { border: none !important; text-align: left; font-size: 11px; padding: 1px 5px; }
            .info-table b { display: inline-block; width: 110px; }
            
            .marks-grid { width: 100%; border-collapse: collapse; }
            .marks-grid th, .marks-grid td { border: 1px solid black; font-size: 10px; padding: 2px; text-align: center; font-family: 'HindiFont', 'DejaVu Sans', sans-serif; }
            .marks-grid th { font-weight: bold; background-color: #dddddd; color: #000; }
            .marks-grid tr.total-row { background-color: #f5f5f5; font-weight: bold; }
            .marks-grid .subject-name { text-align: left; width: 180px; }
            
            .result-table { width: 100%; border: none !important; }
            .result-table td { border: none !important; text-align: center; font-size: 11px; font-weight: bold; padding: 5px 0; }
            
            .footer { margin-top: 30px; width: 100%; }
            .footer-col { width: 50%; float: left; text-align: center; font-weight: bold; }
        </style>
    </head>
    <body>
        <?php 
        // --- START OF PRE-CALCULATIONS FOR FRONT PAGE ---
        $front_stats = [
            'REGULAR' => ['PASSED' => 0, 'FAILED' => 0, 'CARRY' => 0, 'ABSENT' => 0, 'RW' => 0, 'UFM' => 0, 'TOTAL' => 0],
            'EX STUDENT' => ['PASSED' => 0, 'FAILED' => 0, 'CARRY' => 0, 'ABSENT' => 0, 'RW' => 0, 'UFM' => 0, 'TOTAL' => 0],
            'PRIVATE' => ['PASSED' => 0, 'FAILED' => 0, 'CARRY' => 0, 'ABSENT' => 0, 'RW' => 0, 'UFM' => 0, 'TOTAL' => 0]
        ];
        
        foreach ($students as $stu) {
            $stype = strtoupper($stu['student_type'] ?? 'REGULAR');
            if ($stype === 'EX-STUDENT' || $stype === 'EX STUDENT') $stype = 'EX STUDENT';
            else if ($stype !== 'PRIVATE') $stype = 'REGULAR'; // Default to REGULAR
            
            $is_absent_all = true;
            $has_ufm = false;
            $has_rw = false;
            $failed_papers_for_front = [];
            
            foreach ($stu['papers'] as $paper) {
                $obt_int = strtoupper($paper['marks_obt'] ?? '');
                $obt_ext = strtoupper($paper['mid_sem_marks_obt'] ?? '');
                $obt_prc = strtoupper($paper['practical_marks_obt'] ?? '');
                
                if (
                    ($paper['has_internal'] && $obt_int !== 'ABS') ||
                    ($paper['has_theory'] && $obt_ext !== 'ABS') ||
                    ($paper['has_practical'] && $obt_prc !== 'ABS')
                ) {
                    $is_absent_all = false;
                }
                
                if ($obt_int === 'UFM' || $obt_ext === 'UFM' || $obt_prc === 'UFM') $has_ufm = true;
                if ($obt_int === 'RW' || $obt_ext === 'RW' || $obt_prc === 'RW') $has_rw = true;
                
                $p_max = (float)$paper['max_marks'] + (float)$paper['mid_sem_max_marks'] + (float)$paper['practical_max_marks'];
                
                if ($p_max == 0 && $obt_int !== 'ABS' && $obt_ext !== 'ABS' && $obt_prc !== 'ABS') {
                     $failed_papers_for_front[] = $paper['paper_code'];
                }
            }
            
            $front_stats[$stype]['TOTAL']++;
            
            if ($has_ufm) {
                $front_stats[$stype]['UFM']++;
            } elseif ($has_rw) {
                $front_stats[$stype]['RW']++;
            } elseif ($is_absent_all) {
                $front_stats[$stype]['ABSENT']++;
            } elseif (empty($failed_papers_for_front)) {
                $front_stats[$stype]['PASSED']++;
            } else {
                if (count($failed_papers_for_front) <= 2) {
                    $front_stats[$stype]['CARRY']++;
                } else {
                    $front_stats[$stype]['FAILED']++;
                }
            }
        }
        
        $front_total = ['PASSED' => 0, 'FAILED' => 0, 'CARRY' => 0, 'ABSENT' => 0, 'RW' => 0, 'UFM' => 0, 'TOTAL' => 0];
        foreach (['REGULAR', 'EX STUDENT', 'PRIVATE'] as $t) {
            $front_total['PASSED'] += $front_stats[$t]['PASSED'];
            $front_total['FAILED'] += $front_stats[$t]['FAILED'];
            $front_total['CARRY'] += $front_stats[$t]['CARRY'];
            $front_total['ABSENT'] += $front_stats[$t]['ABSENT'];
            $front_total['RW'] += $front_stats[$t]['RW'];
            $front_total['UFM'] += $front_stats[$t]['UFM'];
            $front_total['TOTAL'] += $front_stats[$t]['TOTAL'];
        }
        
        $sql = "SELECT * FROM college_settings WHERE id = 1 LIMIT 1";
        $sets_res = mysqli_query($db, $sql);
        $settings = mysqli_fetch_assoc($sets_res);
        $college_name = $settings['college_name'] ?? 'Raghuveer Mahavidyalaya Thaloi, Bhikharipur Kala, Jaunpur (U.P.)';
        // --- END PRE-CALCULATIONS ---
        ?>

        <!-- REPEATING HEADER FOR STUDENTS & FRONT PAGE -->
        <div class="header-info">
            <?php 
            $tagline = $settings['tagline'] ?? '';
            $logo = $settings['p_logo'] ?? 'images/logo.png';
            $logo_path = $logo;
            
            // For PDF, we need absolute path for local files or base64
            $show_logo = false;
            if ($is_pdf) {
                $base_dir = realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR;
                
                $clean_logo = ltrim($logo, './');
                if (file_exists($base_dir . $clean_logo)) {
                    $logo_path = $base_dir . $clean_logo;
                } elseif (file_exists($base_dir . $logo)) {
                    $logo_path = $base_dir . $logo;
                } elseif (file_exists($base_dir . 'images/logo.png')) {
                    $logo_path = $base_dir . 'images/logo.png';
                }

                if (file_exists($logo_path)) {
                    $type = pathinfo($logo_path, PATHINFO_EXTENSION);
                    $data = file_get_contents($logo_path);
                    $logo_path = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    $show_logo = true;
                }
            } else {
                if (!file_exists($logo) && file_exists('../' . $logo)) {
                    $logo_path = '../' . $logo;
                }
                if (file_exists($logo_path)) {
                    $show_logo = true;
                }
            }
            ?>
            <table width="100%">
                <tr>
                    <td width="15%" style="border:none;">
                        <?php if ($show_logo): ?>
                        <img src="<?= $logo_path ?>" style="height:70px;" alt="">
                        <?php endif; ?>
                    </td>
                    <td width="70%" style="border:none; text-align:center;">
                        <h1 style="border:none; margin:0; padding:0;"><?= strtoupper($college_name) ?></h1>
                        <p style="margin:2px 0; font-weight:bold;"><?= $tagline ?></p>
                        <p style="margin:2px 0; font-weight:bold;">TABULATION CHART</p>
                        <h3 style="margin:5px 0;"> <?= strtoupper($class_info['class_description']) ?> MAIN EXAMINATION SESSION : <?= $exam_session ?></h3>
                    </td>
                    <td width="15%" style="border:none;"></td>
                </tr>
            </table>
        </div>

        <!-- FRONT PAGE (TABULATION CHART COVER) -->
        <div class="front-page" style="page-break-after: always; width: 100%; text-align: center; padding-top: 30px;">
          <style>
.table-chart {
    width: 100%;
    margin: 0 auto;
    border-collapse: collapse;
    font-size: 12px; /* SAME as rest of document */
    text-align: center;
    font-family: 'HindiFont', 'DejaVu Sans', sans-serif; /* MATCHED */
}

.table-chart th,
.table-chart td {
    border: 1.5px solid #000;
    padding: 6px; /* thoda compact like marks-grid */
    height: 35px;
}

.table-chart th {
    background: #dddddd; /* same as marks-grid */
    font-weight: bold;
}

.result-col {
    font-weight: bold;
    text-align: left;
    padding-left: 8px;
}

.vertical-text {
    writing-mode: vertical-rl;
    text-orientation: upright;
    letter-spacing: 3px;
    font-weight: bold;
    font-size: 13px;
}

.total-row {
    background: #f5f5f5; /* same as marks-grid total */
    font-weight: bold;
}
.front-page {
    page-break-after: always;
    margin-bottom: 30px; /* 👈 ye add karo */
}
</style>


<table class="table-chart">
    <tr>
        <th rowspan="8">
            <div class="vertical-text">STATISTICS</div>
        </th>
        <th>RESULT</th>
        <th>REGULAR</th>
        <th>EX STUDENT</th>
        <th>PRIVATE</th>
        <th>TOTAL</th>
    </tr>

    <tr>
        <td class="result-col">PASSED</td>
        <td><?= $front_stats['REGULAR']['PASSED'] ?></td>
        <td><?= $front_stats['EX STUDENT']['PASSED'] ?></td>
        <td><?= $front_stats['PRIVATE']['PASSED'] ?></td>
        <td><?= $front_total['PASSED'] ?></td>
    </tr>

    <tr>
        <td class="result-col">FAILED</td>
        <td><?= $front_stats['REGULAR']['FAILED'] ?></td>
        <td><?= $front_stats['EX STUDENT']['FAILED'] ?></td>
        <td><?= $front_stats['PRIVATE']['FAILED'] ?></td>
        <td><?= $front_total['FAILED'] ?></td>
    </tr>

    <tr>
        <td class="result-col">CARRY OVER /<br>FAIL PROMOTED</td>
        <td><?= $front_stats['REGULAR']['CARRY'] ?></td>
        <td><?= $front_stats['EX STUDENT']['CARRY'] ?></td>
        <td><?= $front_stats['PRIVATE']['CARRY'] ?></td>
        <td><?= $front_total['CARRY'] ?></td>
    </tr>

    <tr>
        <td class="result-col">ABSENT</td>
        <td><?= $front_stats['REGULAR']['ABSENT'] ?></td>
        <td><?= $front_stats['EX STUDENT']['ABSENT'] ?></td>
        <td><?= $front_stats['PRIVATE']['ABSENT'] ?></td>
        <td><?= $front_total['ABSENT'] ?></td>
    </tr>

    <tr>
        <td class="result-col">R. W.</td>
        <td><?= $front_stats['REGULAR']['RW'] ?></td>
        <td><?= $front_stats['EX STUDENT']['RW'] ?></td>
        <td><?= $front_stats['PRIVATE']['RW'] ?></td>
        <td><?= $front_total['RW'] ?></td>
    </tr>

    <tr>
        <td class="result-col">U. F. M.</td>
        <td><?= $front_stats['REGULAR']['UFM'] ?></td>
        <td><?= $front_stats['EX STUDENT']['UFM'] ?></td>
        <td><?= $front_stats['PRIVATE']['UFM'] ?></td>
        <td><?= $front_total['UFM'] ?></td>
    </tr>

    <tr class="total-row">
        <td class="result-col">TOTAL</td>
        <td><?= $front_stats['REGULAR']['TOTAL'] ?></td>
        <td><?= $front_stats['EX STUDENT']['TOTAL'] ?></td>
        <td><?= $front_stats['PRIVATE']['TOTAL'] ?></td>
        <td><?= $front_total['TOTAL'] ?></td>
    </tr>

</table>
        </div>

        <?php 
        $registered = count($students);
        $absent_count = 0;
        $pass_count = 0;
        $atkt_count = 0;
        $fail_count = 0;
        $paper_stats = []; // code => [title, appeared, pass]

        foreach ($students as $index => $stu): 
                $is_absent_all = true;
                $failed_papers = [];
                $total_credits = 0;
                $total_cgp = 0;
                $total_max = 0;
                $total_obt = 0;
                $any_atkt = false;
                // Check if student is absent in all papers
                foreach ($stu['papers'] as $paper) {
                    $abs_int = ($paper['has_internal'] && strtoupper($paper['marks_obt'] ?? '') === 'ABS');
                    $abs_ext = ($paper['has_theory'] && strtoupper($paper['mid_sem_marks_obt'] ?? '') === 'ABS');
                    $abs_prc = ($paper['has_practical'] && strtoupper($paper['practical_marks_obt'] ?? '') === 'ABS');
                    // If any paper has marks (not ABS), student is not absent in all
                    if (
                        ($paper['has_internal'] && strtoupper($paper['marks_obt'] ?? '') !== 'ABS') ||
                        ($paper['has_theory'] && strtoupper($paper['mid_sem_marks_obt'] ?? '') !== 'ABS') ||
                        ($paper['has_practical'] && strtoupper($paper['practical_marks_obt'] ?? '') !== 'ABS')
                    ) {
                        $is_absent_all = false;
                        break;
                    }
                }
        ?>
            <table class="student-record">
                <tr>
                    <!-- LEFT COLUMN: Student Details -->
                    <td width="25%">
                        <table class="info-table">
                            <tr><td style="font-size:11px;"><b>S.No:</b> <?= $index + 1 ?></td></tr>
                            <tr><td>&nbsp;</td></tr>
                            <tr><td><b>ROLL NO.</b> <?= $stu['exam_roll_no'] ?></td></tr>
                            <tr><td><b>UIN No.</b> <?= $stu['uin_no'] ?></td></tr>
                            <tr><td><b>STUDENT NAME</b> <?= strtoupper($stu['student_name']) ?></td></tr>
                            <tr><td><b>FATHER NAME</b> <?= strtoupper($stu['father_name']) ?></td></tr>
                            <tr><td><b>MOTHER NAME</b> <?= strtoupper($stu['mother_name']) ?></td></tr>
                            <tr>
    <td><b>ENROLLMENT NO.</b> <?= !empty($stu['enroll_no']) ? strtoupper($stu['enroll_no']) : '' ?></td>
</tr>
                            <!-- <tr><td><b>SL. NO.</b> <?= $stu['result_srno'] ?></td></tr> -->
                        </table>
                    </td>

                    <!-- CENTER COLUMN: Marks Grid -->
                    <td width="63%">
                        <?php
                        // Pre-scan this student's papers to decide which columns to show
                        $show_int = false; $show_ext = false; $show_prc = false;
                        foreach ($stu['papers'] as $_p) {
                            if ($_p['has_internal'] == 1) $show_int = true;
                            if ($_p['has_theory']   == 1) $show_ext = true;
                            if ($_p['has_practical']== 1) $show_prc = true;
                        }
                        ?>
                        <table class="marks-grid">
                            <thead>
                                <tr>
                                    <th><?= $is_bed ? 'TYPE' : 'TYPE' ?></th>
                                    <?php if (!$is_bed): ?><th class="subject-name">SUBJECT NAME</th><?php endif; ?>
                                    <th>CODE</th>
                                    <th class="subject-name">PAPER NAME</th>
                                    <?php if (!$is_bed): ?><th>CREDIT</th><?php endif; ?>
                                    <?php if ($show_int): ?><th>INT MAX</th><th>OBT INT</th><?php endif; ?>
                                    <?php if ($show_ext): ?><th>EXT MAX</th><th>OBT EXT</th><?php endif; ?>
                                    <?php if ($show_prc): ?><th>PRC MAX</th><th>OBT PRC</th><?php endif; ?>
                                    <th>TOTAL</th>
                                    <?php if (!$is_bed): ?><th>GP</th><th>LG</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_credits = 0;
                                $earned_credits = 0;
                                $total_cgp = 0;
                                 $total_max = 0;
                                 $total_obt = 0;
                                 $total_int = 0;
                                 $total_ext = 0;
                                 $total_prc = 0;
                                 $total_int_max = 0;
                                 $total_ext_max = 0;
                                 $total_prc_max = 0;
                                 $total_gp = 0;
                                 $failed_papers = [];

                                 // Calculate rowspan for TYPE contiguous blocks and roman numerals for SUBJECT NAME
                                 $type_spans = [];
                                 $current_type = null;
                                 $last_t_idx = 0;
                                 $is_ug = (strtoupper($class_info['category'] ?? '') === 'UG' || strtoupper($class_info['class_type'] ?? '') === 'UG');
                                 
                                 $is_ma_hindi_1 = (strpos(strtoupper($class_info['class_description'] ?? ''), 'MA HINDI') !== false && ($class_info['semester'] == '1' || strpos(strtoupper($class_info['class_description'] ?? ''), 'SEMESTER-I') !== false));
                                 
                                 // Sort UG papers properly (Major -> Minor -> Vocational -> Co-curricular)
                                 if ($is_ug) {
                                     usort($stu['papers'], function($a, $b) {
                                         $order = ['major' => 1, 'minor' => 2, 'vocational' => 3, 'co-curricular' => 4];
                                         
                                         // Helper to determine type
                                         $getType = function($paper) {
                                             $t = strtolower($paper['paper_type'] ?? '');
                                             if (empty($t)) $t = strtolower($paper['subject_type_name'] ?? '');
                                             if (empty($t)) return 'major'; // default if missing
                                             return $t;
                                         };
                                         
                                         $valA = $order[$getType($a)] ?? 99;
                                         $valB = $order[$getType($b)] ?? 99;
                                         
                                         if ($valA == $valB) return strcmp($a['paper_code'] ?? '', $b['paper_code'] ?? '');
                                         return $valA - $valB;
                                     });
                                 }
                                 
                                 $major_paper_counter = 0;
                                 $romanMap = [1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI', 7=>'VII', 8=>'VIII', 9=>'IX', 10=>'X', 11=>'XI', 12=>'XII'];

                                 foreach ($stu['papers'] as $idx => $p) {
                                     if (!$is_ug && !empty($p['paper_type'])) {
                                         $t = ucfirst($p['paper_type']);
                                     } else {
                                         $t = ucfirst($p['paper_type'] ?? '');
                                         if (empty($t)) $t = ucfirst($p['subject_type_name'] ?? 'Major');
                                         if ($is_ma_hindi_1 && $p['paper_code'] === 'A070702T') {
                                             $t = 'Minor';
                                         }
                                     }
                                     $s = strtoupper($p['subject_name'] ?? '');
                                     
                                     if (strtolower($t) === 'major') {
                                         $major_paper_counter++;
                                         $numeral = $romanMap[$major_paper_counter] ?? $major_paper_counter;
                                         $stu['papers'][$idx]['paper_numeral'] = $numeral;
                                         if ($is_bed) { $t = 'PAPER ' . $numeral; }
                                     }

                                     if ($t !== $current_type) {
                                         $type_spans[$idx] = 1;
                                         $current_type = $t;
                                         $last_t_idx = $idx;
                                     } else {
                                         $type_spans[$last_t_idx]++;
                                     }
                                     
                                     
                                 }

                                foreach ($stu['papers'] as $idx => $paper): 
                                    $p_max = (float)$paper['max_marks'] + (float)$paper['mid_sem_max_marks'] + (float)$paper['practical_max_marks'];
                                    $p_obt = (float)$paper['marks_obt'] + (float)$paper['mid_sem_marks_obt'] + (float)$paper['practical_marks_obt'];
                                    
                                    // Helper for dash display
                                    $show_mark = function($val, $flag) {
                                        if ($flag == 0) return '-';
                                        return ($val === null || $val === '') ? '-' : $val;
                                    };

                                    // DB field mapping (verified from marks_entry_form.php):
                                    // INT (has_internal): max_marks       <-> marks_obt
                                    // EXT (has_theory):  mid_sem_max_marks <-> mid_sem_marks_obt
                                    // PRC (has_practical): practical_max_marks <-> practical_marks_obt
                                    $obt_int = $show_mark($paper['marks_obt'],         $paper['has_internal']);
                                    $obt_ext = $show_mark($paper['mid_sem_marks_obt'],  $paper['has_theory']);
                                    $obt_prc = $show_mark($paper['practical_marks_obt'],$paper['has_practical']);

                                     // Grade and Grade Point using new NEP functions
                                     $letter_grade = calculate_grade_letter($p_obt, $p_max);
                                     $grade_point = calculate_grade($p_obt, $p_max);
                                    
                                    $p_credit = (float)$paper['credit'] ?: 4; // Default to 4 if missing
                                    $p_cgp = $p_credit * $grade_point;
                                    
                                     if ($p_max > 0) {
                                        $total_credits += $p_credit;
                                        $total_max     += $p_max;
                                        $total_obt     += $p_obt;
                                        $total_int     += (float)$paper['marks_obt'];          // INT = marks_obt
                                        $total_ext     += (float)$paper['mid_sem_marks_obt'];   // EXT = mid_sem_marks_obt
                                        $total_prc     += (float)$paper['practical_marks_obt'];
                                        $total_int_max += $paper['has_internal'] ? (float)$paper['max_marks']           : 0;
                                        $total_ext_max += $paper['has_theory']   ? (float)$paper['mid_sem_max_marks']   : 0;
                                        $total_prc_max += $paper['has_practical'] ? (float)$paper['practical_max_marks'] : 0;
                                        $total_gp += $grade_point;
                                        $total_cgp += $p_cgp;
                                        if ($grade_point >= 4) { $earned_credits += $p_credit; }
                                     }
                                    elseif (strtoupper($paper['marks_obt'] ?? '') !== 'ABS' && strtoupper($paper['mid_sem_marks_obt'] ?? '') !== 'ABS' && strtoupper($paper['practical_marks_obt'] ?? '') !== 'ABS') {
                                        $failed_papers[] = $paper['paper_code'];
                                    }
                                ?>
                                    <?php
                                        if (!$is_ug && !empty($paper['paper_type'])) {
                                            $type_label = ucfirst($paper['paper_type']);
                                        } else {
                                            $type_label = ucfirst($paper['paper_type'] ?? '');
                                            if (empty($type_label)) $type_label = ucfirst($paper['subject_type_name'] ?? 'Major');
                                        }

                                        if ($is_bed && strtolower($type_label) === 'major') {
                                            $type_label = 'PAPER ' . ($paper['paper_numeral'] ?? '');
                                        }

                                        $orig_subject_label = strtoupper($paper['subject_name'] ?? '');
                                        $subject_label = $orig_subject_label;
                                        
                                        // Specific override for MA Hindi Semester I
                                        if ($is_ma_hindi_1 && $paper['paper_code'] === 'A070702T') {
                                            $type_label = 'Minor';
                                            $subject_label = 'SOCIOLOGY';
                                        }

                                        $lower_type = strtolower($type_label);
                                        $paper_title_upper = strtoupper($paper['paper_title'] ?? '');
                                        
                                        // PG (non-B.Ed): Paper I, II... for Major papers
                                        if (!$is_ug && !$is_bed && strtolower($type_label) === 'major' && !empty($orig_subject_label)) {
                                            $subject_label = 'PAPER ' . ($paper['paper_numeral'] ?? '');
                                        }
                                        
                                        // Automatically override subject label if it's vocational/co-curricular but subject is effectively empty
                                        if ($lower_type === 'vocational' && empty($orig_subject_label)) {
                                            $subject_label = 'VOCATIONAL';
                                        } elseif ($lower_type === 'co-curricular' && empty($orig_subject_label)) {
                                            $subject_label = 'CO-CURRICULAR';
                                        }
                                    ?>
                                    <tr>
                                        <?php if (isset($type_spans[$idx])): ?>
                                        <td rowspan="<?= $type_spans[$idx] ?>"><?= $type_label ?></td>
                                        <?php endif; ?>
                                        
                                        <?php if (!$is_bed): ?>
                                            <td class="subject-name">
                                                <?= $subject_label ?>
                                            </td>
                                        <?php endif; ?>
                                        
                                        <td><?= $paper['paper_code'] ?></td>
                                        
                                        <td class="subject-name"><?= $paper['paper_title'] ?? '' ?></td>
                                        <?php if (!$is_bed): ?><td><?= $p_credit ?></td><?php endif; ?>
                                        <?php if ($show_int): ?>
                                            <td><?= ($paper['has_internal']  ? (int)$paper['max_marks']           : '-') ?></td>
                                            <td><?= $obt_int ?></td>
                                        <?php endif; ?>
                                        <?php if ($show_ext): ?>
                                            <td><?= ($paper['has_theory']    ? (int)$paper['mid_sem_max_marks']   : '-') ?></td>
                                            <td><?= $obt_ext ?></td>
                                        <?php endif; ?>
                                        <?php if ($show_prc): ?>
                                            <td><?= ($paper['has_practical'] ? (int)$paper['practical_max_marks'] : '-') ?></td>
                                            <td><?= $obt_prc ?></td>
                                        <?php endif; ?>
                                        <td><?= (int)$p_obt ?></td>
                                        <?php if (!$is_bed): ?><td><?= $grade_point ?></td><td><?= $letter_grade ?></td><?php endif; ?>
                                    </tr>
                                    <?php 
                                        // Track paper stats
                                        if (!isset($paper_stats[$paper['paper_code']])) {
                                            $paper_stats[$paper['paper_code']] = [
                                                'title'   => $paper['paper_title'],
                                                'int_max' => (float)$paper['max_marks'],           // INT = max_marks
                                                'ext_max' => (float)$paper['mid_sem_max_marks'],    // EXT = mid_sem_max_marks
                                                'prc_max' => (float)$paper['practical_max_marks'],
                                                'obt_int' => 0,
                                                'obt_ext' => 0,
                                                'obt_prc' => 0,
                                            ];
                                        }
                                        $paper_stats[$paper['paper_code']]['obt_int'] += (float)$paper['marks_obt'];
                                        $paper_stats[$paper['paper_code']]['obt_ext'] += (float)$paper['mid_sem_marks_obt'];
                                        $paper_stats[$paper['paper_code']]['obt_prc'] += (float)$paper['practical_marks_obt'];
                                    ?>
                                <?php endforeach; ?>
                                <tr style="font-weight:bold; background:#f9f9f9;">
                                    <td colspan="<?= !$is_bed ? 4 : 3 ?>" style="text-align:right;">TOTAL</td>
                                    <?php if (!$is_bed): ?><td><?= $total_credits ?></td><?php endif; ?>
                                    <?php if ($show_int): ?>
                                        <td><?= (int)$total_int_max ?></td>
                                        <td><?= (int)$total_int ?></td>
                                    <?php endif; ?>
                                    <?php if ($show_ext): ?>
                                        <td><?= (int)$total_ext_max ?></td>
                                        <td><?= (int)$total_ext ?></td>
                                    <?php endif; ?>
                                    <?php if ($show_prc): ?>
                                        <td><?= (int)$total_prc_max ?></td>
                                        <td><?= (int)$total_prc ?></td>
                                    <?php endif; ?>
                                    <td><?= (int)$total_obt ?></td>
                                    <?php if (!$is_bed): ?><td><?= $total_gp ?></td><td></td><?php endif; ?>
                                </tr>
                            </tbody>
                        </table>
                    </td>

                    <!-- RIGHT COLUMN: Totals & Result -->
                    <td width="12%">
                         <?php $marks_sgpa = ($total_credits > 0) ? number_format($total_cgp / $total_credits, 2) : '0.00'; ?>
                        <table class="result-table">
                            <tr><td>GRAND TOTAL</td></tr>
                            <tr><td style="font-size:12px; border-bottom:1px solid #000 !important;"><?= (int)$total_obt ?> / <?= (int)$total_max ?></td></tr>
                            <?php if ($is_bed): ?>
                            <tr><td style="font-size:9px;">TH TOTAL</td></tr>
                            <tr><td style="font-size:11px; border-bottom:1px solid #000 !important;"><?= (int)($total_ext + $total_int) ?> / <?= (int)($total_ext_max + $total_int_max) ?></td></tr>
                            <tr><td style="font-size:9px;">PR TOTAL</td></tr>
                            <tr><td style="font-size:11px; border-bottom:1px solid #000 !important;"><?= (int)$total_prc ?> / <?= (int)$total_prc_max ?></td></tr>
                            <?php endif; ?>
                            <?php if (!$is_bed): ?>
                            <tr><td>SGPA</td></tr>
                            <tr><td style="font-size:12px;"><?= $marks_sgpa ?></td></tr>
                            <tr><td>CGPA</td></tr>
                            <tr><td style="font-size:12px;"><?= $marks_sgpa ?></td></tr>
                            <?php endif; ?>
                            <tr><td>RESULT</td></tr>
                            <tr><td style="color: <?php if ($is_absent_all) echo 'black'; elseif (empty($failed_papers)) echo 'green'; else echo 'red'; ?>;">
                                <?php if ($is_absent_all) echo 'ABSENT'; elseif (empty($failed_papers)) echo 'PASSED'; else echo 'ATKT/FAIL'; ?>
                            </td></tr>
                        </table>
                    </td>
                </tr>
            </table>
            
            <?php 
            // Finalize student stats
            if ($is_absent_all) $absent_count++;
            if (empty($failed_papers) && !$is_absent_all) {
                $pass_count++;
            } elseif (!$is_absent_all) {
                // Determine if ATKT (failed in some papers but not all)
                if (count($failed_papers) <= 2) { $atkt_count++; }
                else { $fail_count++; }
            }
            ?>
            
            <?php if (($index + 1) % 3 == 0 && $index < count($students) - 1): ?>
                <div style="page-break-after: always;"></div>
            <?php endif; ?>
        <?php endforeach; ?>

        <div class="footer-summaries" style="margin-top:20px; page-break-inside:avoid;">
            <div style="display:flex; gap:20px;">
                <!-- Paper Stats -->
                <div style="flex:2;">
                    <?php
                    // Check which max columns have data in summary
                    $sum_has_int = false; $sum_has_ext = false; $sum_has_prc = false;
                    foreach ($paper_stats as $s) {
                        if ($s['int_max'] > 0) $sum_has_int = true;
                        if ($s['ext_max'] > 0) $sum_has_ext = true;
                        if ($s['prc_max'] > 0) $sum_has_prc = true;
                    }
                    ?>
                    <table class="marks-grid" style="width:100%;">
                        <thead>
                            <tr>
                                <th>PAPER CODE</th>
                                <th>PAPER NAME</th>
                                <?php if ($sum_has_int): ?><th>INT MAX</th><?php endif; ?>
                                <?php if ($sum_has_ext): ?><th>EXT MAX</th><?php endif; ?>
                                <?php if ($sum_has_prc): ?><th>PRC MAX</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paper_stats as $code => $s): ?>
                                <tr>
                                    <td><?= $code ?></td>
                                    <td style="text-align:left;"><?= $s['title'] ?></td>
                                    <?php if ($sum_has_int): ?><td><?= ($s['int_max'] > 0 ? (int)$s['int_max'] : '-') ?></td><?php endif; ?>
                                    <?php if ($sum_has_ext): ?><td><?= ($s['ext_max'] > 0 ? (int)$s['ext_max'] : '-') ?></td><?php endif; ?>
                                    <?php if ($sum_has_prc): ?><td><?= ($s['prc_max'] > 0 ? (int)$s['prc_max'] : '-') ?></td><?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Result Stats -->
                <div style="flex:1;">
                    <h4 style="margin:5px 0;">RESULT SUMMARY</h4>
                    <table class="marks-grid" style="width:100%;">
                        <thead>
                            <tr>
                                <th>REGISTERED</th>
                                <th>ABSENT</th>
                                <th>APPEARED</th>
                                <th>PASS</th>
                                <th>ATKT</th>
                                <th>FAIL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= $registered ?></td>
                                <td><?= $absent_count ?></td>
                                <td><?= $registered - $absent_count ?></td>
                                <td><?= $pass_count ?></td>
                                <td><?= $atkt_count ?></td>
                                <td><?= $fail_count ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top:15px; font-size:9px; border:1px solid #000; padding:10px;">
                <b>NOTE:</b><br>
                1. EQUIVALENT PERCENTAGE OF MARKS = CGPA * 10.<br>
                2. THE MINIMUM PASSING STANDARD FOR A SUBJECT SHALL BE 33% i.e. 33 MARKS OUT OF 100 MARKS (WHEREVER APPLICABLE).<br>
                3. FOR A COMPLETED COURSE, CGPA SHALL BE CALCULATED AS THE WEIGHTED AVERAGE OF ALL SEMESTERS.
            </div>
        </div>

        <div class="footer-container" style="page-break-inside: avoid;">
            <div class="footer" style="margin-top:30px; width:100%;">
                <div class="footer-col" style="text-align:left; font-weight:bold;">
                    DATE OF RESULT: <?= date('d-m-Y') ?><br><br>
                    Prepared by: ____________________
                </div>
                <div class="footer-col" style="text-align:center; font-weight:bold;">
                    CONTROLLER OF EXAMINATIONS<br><br>
                    Checked by: ____________________ ____________________
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}