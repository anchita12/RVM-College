<?php
require_once __DIR__ . '/scripts/settings.php';
require_once __DIR__ . '/admin/crosslist_functions.php';

$college = get_college_settings(1);
$error = '';
$student_data = null;
$show_result = false;

// Course list for dropdown (fetching all specific classes/semesters)
$courses_sql = "SELECT sno, class_description FROM class_detail WHERE show_regular = 1 ORDER BY class_description ASC";
$courses_res = $mysqli->query($courses_sql);
$course_options = [];
while($c = $courses_res->fetch_assoc()) {
    $course_options[] = [
        'sno' => $c['sno'],
        'description' => $c['class_description']
    ];
}

if (isset($_POST['search_result'])) {
    $course_sno = trim($_POST['course_sno']);
    $roll_no = trim($_POST['roll_no']);
    $dob = trim($_POST['dob']);

    // Step 1: Find the student in exam_student_info linked with student_info (for DOB)
    $sql = "SELECT esi.sno as exam_sno, cd.sno as course_sno
            FROM exam_student_info esi
            JOIN student_info si ON esi.student_info_sno = si.sno
            JOIN class_detail cd ON esi.course_name = cd.sno
            WHERE esi.exam_roll_no = ? 
            AND si.dob = ? 
            AND cd.sno = ?
            AND esi.verify_status = 1
            LIMIT 1";
            
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sss", $roll_no, $dob, $course_sno);
    $stmt->execute();
    $res = $stmt->get_result();
    $found = $res->fetch_assoc();
    $stmt->close();

    if ($found) {
        // Step 2: Fetch full data using existing crosslist function
        $full_data = fetch_crosslist_data($mysqli, $found['course_sno'], $roll_no);
        if (!empty($full_data)) {
            $student_data = $full_data[0];
            $show_result = true;
        } else {
            $error = "Result data not found for this student.";
        }
    } else {
        $error = "Invalid Roll Number, Date of Birth or Course selection.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provisional Result - <?= htmlspecialchars($college['college_name'] ?? 'College') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .search-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); margin-top: 50px; }
        .search-header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; border-radius: 15px 15px 0 0; padding: 20px; text-align: center; }
        .btn-primary { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border: none; padding: 10px 30px; }
        
        /* Result Layout Styling */
        .result-container { 
            background: #fff; 
            padding: 30px; 
            margin-top: 30px; 
            border: 1px solid #ccc; 
            position: relative; 
            max-width: 100 vw; 
            margin-left: auto; 
            margin-right: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px; color: rgba(200,200,200,0.4); font-weight: bold; pointer-events: none; z-index: 0; white-space: nowrap;
        }
        .header-table td { border: none !important; }
        .info-label { font-weight: bold; width: 150px; display: inline-block; }
        .marks-table { width: 100%; border-collapse: collapse; margin-top: 20px; position: relative; z-index: 1; }
        .marks-table th, .marks-table td { border: 1px solid #000; padding: 6px 10px; font-size: 13px; text-align: center; }
        .marks-table th { background: #f8f9fa; }
        .text-start { text-align: left !important; }
        .footer-note { font-size: 11px; margin-top: 20px; color: #555; }
        
        @media print {
            .no-print { display: none; }
            body { background: #fff; }
            .result-container { border: none; box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
        }
        .portal-box{
            max-width:1050px;
            margin:10px auto !important;
            background:#ffffff;
            border-radius:10px;
            box-shadow:0 5px 20px rgba(0,0,0,0.15);
            overflow:hidden;
            }
           

            .portal-header{
            padding:25px;
            text-align:center;
            background:white;
            border-bottom:1px solid #ddd;
            }

            .portal-header h1{
            font-size:30px;
            font-weight:700;
            color:#1e3a8a;
            margin-bottom:5px;
            }

            .portal-header p{
            font-size:14px;
            color:#444;
            }

            .result-title{
            font-weight:700;
            color:#1e3a8a;
            border-bottom:3px solid #1e3a8a;
            display:inline-block;
            padding-bottom:5px;
            }

            .search-panel{
            background:#eef2f7;
            padding:30px;
            border-radius:8px;
            }

            .search-panel label{
            font-weight:600;
            margin-bottom:5px;
            }

            .form-control,
            .form-select{
            height:45px;
            border-radius:6px;
            }

            .search-btn{
            background:#1e3a8a;
            border:none;
            padding:10px 35px;
            font-size:16px;
            border-radius:6px;
            color:white;
            }

            .search-btn:hover{
            background:#152c6a;
            }

            .portal-footer{
            background:#1e73be;
            color:white;
            text-align:center;
            padding:12px;
            font-size:14px;
            }
            .college-logo{
            height:60px;
            width:auto;
            }

            .portal-header{
            padding:20px;
            background:white;
            border-bottom:1px solid #ddd;

            background-image: url("images/bg3.png"); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            }

            .full-page{
                background:url('images/hii.jpeg') no-repeat center center/cover;
                min-height:100vh;
                display:flex;
                justify-content:center;
                align-items:flex-start;  
                padding-top:40px;      
                overflow-x:hidden;
            }

            .full-page::before{
                content: "";
                position: absolute;
                inset: 0;
                background:rgba(0,0,0,0.55);
                z-index: 1;
            }

            .full-page > *{
                position: relative;
                z-index: 5;
            }
    </style>
</head>
<body>

<?php if (!$show_result): ?>
<div class="full-page">
                    <div class="portal-box no-print">

                    <div class="portal-header">

                    <div class="d-flex align-items-center">

                    <img src="<?= htmlspecialchars($college['p_logo'] ?? 'images/logo.png') ?>" 
                    class="college-logo">

                    <div class="ms-3">

                    <h1 class="m-0">
                    <?php echo htmlspecialchars($college['college_name'] ?? 'Raghuveer Mahavidyalaya'); ?>
                    </h1>

                    <p class="m-0">
                    <?php echo htmlspecialchars($college['tagline'] ?? 'Autonomous Post Graduate College'); ?>
                    ||
                    <?php echo htmlspecialchars($college['naac_text'] ?? 'NAAC Accredited'); ?>
                    </p>

                    </div>

                    </div>

                    </div>

                    <div class="p-4">

                    <h3 class="result-title">Result 2025-26</h3>

                    <br>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong></strong> <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="search-panel">

                    <form method="POST">

                    <div class="row g-4">

                    <div class="col-md-4">

                    <label>Course</label>

                    <select name="course_sno" class="form-select" required>

                    <option value="">Select Course</option>

                    <?php foreach($course_options as $opt): ?>

                    <option value="<?= $opt['sno'] ?>">
                    <?= htmlspecialchars($opt['description']) ?>
                    </option>

                    <?php endforeach; ?>

                    </select>

                    </div>


                    <div class="col-md-4">

                    <label>Exam Roll Number</label>

                    <input type="text"
                    name="roll_no"
                    class="form-control"
                    placeholder="Enter Roll Number"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    required>

                    </div>


                    <div class="col-md-4">

                    <label>Date of Birth</label>

                    <input type="date"
                    name="dob"
                    class="form-control"
                    required>

                    </div>

                    </div>

                    <br>

                    <div class="text-center">

                    <button type="submit"
                    name="search_result"
                    class="search-btn">

                    Search Result

                    </button>

                    </div>

                    </form>

                    </div>

                    </div>

                    <div class="portal-footer">
                    Copyright © <?= date('Y') ?>
                    </div>

                    </div>
                </div>
<?php else: ?>

<div class="container mt-4 no-print text-center">
    <button onclick="window.print()" class="btn btn-danger btn-lg"><i class="fa-solid fa-print"></i> Print Result</button>
    <a href="result.php" class="btn btn-secondary btn-lg ms-2">Back to Search</a>
</div>

<div class="result-container">
    <div class="watermark">Internet Copy</div>
    
    <!-- Header -->
   <div class="text-center mb-4">
        <div class="row align-items-center">
            <div class="col-2">
                <img src="<?= htmlspecialchars($college['p_logo'] ?? 'images/logo.png') ?>" style="height: 80px;">
            </div>
            <div class="col-8">
                <h4 class="mb-1" style="color:#1e3a8a; font-weight:bold;">
                    <span style="font-size:1.5rem; font-weight:bold;">
                        RAGHUVEER MAHAVIDYALAYA
                    </span><br>

                    <span style="font-size:1.07rem; font-weight:bold;">
                        THALOI, BHIKHARIPUR KALA,
                        JAUNPUR (U.P.)
                    </span>
                </h4>
                <p class="mb-0 fw-bold"><?= $college['tagline'] ?></p>
                <p class="mb-1 fw-bold"><?= $college['naac_text'] ?></p>
                <p class="mb-0">( <?= $college['affiliated_text'] ?>)</p>
            </div>
             <div class="col-2">
                <img src="<?= htmlspecialchars($college['p_logo'] ?? 'images/vbspu-logo.png') ?>" style="height: 80px;">
            </div>
            
        </div>
        <hr style="border: 2px solid #000; margin: 10px 0;">
        <h5 class="fw-bold">PROVISIONAL MARKSHEET</h5>
        <h6 class="fw-bold"><?= (date('Y')-1).'-'.date('Y') ?></h6>
    </div>

    <!-- Student Info -->
   <div class="mt-4" style="font-size:13.5px;border:1px solid #ccc;padding:10px;background:#fff;position:relative;z-index:1;">

        <table style="width:100%;border-collapse:collapse;">

        <!-- ROW 1 -->
        <tr>

        <td width="18%"><b>Student Name</b></td>
        <td width="32%">: <?= strtoupper($student_data['student_name']) ?></td>

        <td width="18%"><b>Roll No.</b></td>
        <td width="22%">: <?= $student_data['exam_roll_no'] ?></td>

        <td width="10%" rowspan="5" style="text-align:right;vertical-align:top">

        <?php 
        $stu_img_path = !empty($student_data['student_image']) ? $student_data['student_image'] : 'images/default_stu.png';

        if (!file_exists($stu_img_path) && file_exists('admin/'.$stu_img_path)) {
        $stu_img_path = 'admin/'.$stu_img_path;
        }

        if (!file_exists($stu_img_path)) {
        $stu_img_path = "https://via.placeholder.com/120x150?text=Photo";
        }
        ?>

        <img src="<?= $stu_img_path ?>" style="width:100px;height:115px;border:1px solid #000;object-fit:cover">

        </td>

        </tr>


        <!-- ROW 2 -->
        <tr>

        <td><b>Father's Name</b></td>
        <td>: <?= strtoupper($student_data['father_name']) ?></td>

        <td><b>Student Type</b></td>
        <td>: <?= strtoupper($student_data['student_type'] ?? $student_data['category'] ?? 'REGULAR') ?></td>

        </tr>


        <!-- ROW 3 -->
        <tr>

        <td><b>Mother's Name</b></td>
        <td>: <?= strtoupper($student_data['mother_name']) ?></td>

        <td><b>Enrollment No.</b></td>
        <td>: <?= strtoupper($student_data['enroll_no'] ?? '') ?></td>

        </tr>


        <!-- ROW 4 -->
        <tr>

        <td><b>Class</b></td>
        <td>: <?= strtoupper($student_data['class_description']) ?></td>

        <td><b>UIN No.</b></td>
        <td>: <?= strtoupper($student_data['uin_no'] ?? '') ?></td>

        </tr>


        <!-- ROW 5 (2 DATA ONLY) -->
        <tr>

        <td><b>Name of Institution</b></td>
        <td colspan="3">: 0693 - <?= strtoupper($college['college_name']) ?></td>

        </tr>

        </table>

        </div>
    <?php
    $class_desc = strtoupper(trim($student_data['class_description'] ?? ''));
    // Robust BA identification to include B.A, BA, B. A. etc.
    $is_ba = (strpos($class_desc, 'B.A') !== false || strpos($class_desc, 'BA ') !== false || $class_desc === 'BA');
    $is_bed = (strpos($class_desc, 'B.ED') !== false || strpos($class_desc, 'BED') !== false || strpos($class_desc, 'M.ED') !== false || strpos($class_desc, 'MED') !== false);
    $is_ma = (strpos($class_desc, 'M.A') === 0 || strpos($class_desc, 'MA ') === 0 || strpos($class_desc, 'MA(') === 0 || $class_desc === 'MA' || strpos($class_desc, 'M.SC') !== false || strpos($class_desc, 'M.COM') !== false);
    ?>

    <?php if ($is_ba): ?>
    <!-- Marks Table for BA ONLY (NEP Format - Obtained Marks Only) -->
    <table class="marks-table">
        <thead>
            <tr><th width="12%">SUB TYPE</th><th width="18%">SUBJECT NAME</th><th width="30%">PAPER NAME</th><th width="8%">TH</th><th width="8%">SESS</th><th width="10%">TOTAL</th><th width="4%">CR</th><th width="4%">GR</th><th width="6%">G.P.</th></tr>
        </thead>
        <tbody>
            <?php 
            $grand_max = 0; $grand_obt = 0; $total_credits = 0; $earned_credits = 0; $total_cgp = 0;
            $type_spans = []; $subj_spans = []; $current_type = ""; $current_subj = ""; $last_type_idx = 0; $last_subj_idx = 0;
            foreach ($student_data['papers'] as $idx => $p) {
                $t = strtoupper($p['paper_type'] ?? $p['subject_type_name'] ?? 'MAJOR');
                $s = strtoupper($p['subject_name'] ?? '');
                if ($t !== $current_type) { $type_spans[$idx] = 1; $current_type = $t; $last_type_idx = $idx; } else { $type_spans[$last_type_idx]++; }
                if ($s !== $current_subj || $t !== $current_type) { $subj_spans[$idx] = 1; $current_subj = $s; $last_subj_idx = $idx; } else { $subj_spans[$last_subj_idx]++; }
            }
            foreach($student_data['papers'] as $idx => $paper): 
                $p_type = strtoupper($paper['paper_type'] ?? $paper['subject_type_name'] ?? 'MAJOR');
                $p_subj = strtoupper($paper['subject_name'] ?? '');
                $p_title = '[' . $paper['paper_code'] . '] ' . strtoupper($paper['paper_title']);
                $p_th_max = (float)$paper['mid_sem_max_marks']; $p_th_obt = (float)$paper['mid_sem_marks_obt'];
                $p_sess_max = (float)$paper['max_marks']; $p_sess_obt = (float)$paper['marks_obt'];
                if ($paper['practical_max_marks'] > 0 && $p_th_max == 0) { $p_th_max = (float)$paper['practical_max_marks']; $p_th_obt = (float)$paper['practical_marks_obt']; }
                $p_total_max = $p_th_max + $p_sess_max; $p_total_obt = $p_th_obt + $p_sess_obt;
                if ($p_total_max > 0) { $grand_max += $p_total_max; $grand_obt += $p_total_obt; }
                $letter_grade = calculate_grade_letter($p_total_obt, $p_total_max);
                $grade_point = calculate_grade($p_total_obt, $p_total_max);
                $p_credit = (float)($paper['credit'] ?? 4); 
                $total_credits += $p_credit; 
                $total_cgp += ($p_credit * $grade_point);
                if ($grade_point >= 4) $earned_credits += $p_credit;
                ?>
                <tr><?php if (isset($type_spans[$idx])): ?><td rowspan="<?= $type_spans[$idx] ?>" class="fw-bold"><?= $p_type ?></td><?php endif; ?>
                    <?php if (isset($subj_spans[$idx])): ?><td rowspan="<?= $subj_spans[$idx] ?>"><?= $p_subj ?></td><?php endif; ?>
                    <td class="text-start"><?= $p_title ?></td><td><?= (int)$p_th_obt ?></td><td><?= (int)$p_sess_obt ?></td><td class="fw-bold"><?= (int)$p_total_obt ?></td>
                    <td><?= $p_credit ?></td><td class="fw-bold"><?= $letter_grade ?></td><td class="fw-bold"><?= $grade_point ?></td></tr>
            <?php endforeach; ?>
            <tr style="background: #f8f9fa; font-weight: bold;"><td colspan="6" class="text-center">TOTAL CREDITS</td><td><?= $total_credits ?></td><td colspan="2"></td></tr>
        </tbody>
    </table>
    <div style="display: flex; gap: 15px; margin-top: 15px;">
        <div style="flex: 1.5;"><table class="marks-table" style="margin-top: 0;"><thead style="background: #e9ecef;"><tr><th colspan="5">Semester I Records</th></tr>
        <tr><th>SEM</th><th>Total Credits</th><th>Total Credit Earned</th><th>SGPA</th><th>RESULT</th></tr></thead>
        <tbody><?php $sgpa = ($total_credits > 0) ? number_format($total_cgp / $total_credits, 2) : '0.00'; $res_status = ($earned_credits >= ($total_credits * 0.5)) ? 'PASS' : 'FAIL'; ?>
        <tr><td class="fw-bold"><?= str_replace('SEMESTER-', '', strtoupper($student_data['semester'] ?? 'II')) ?></td><td><?= $total_credits ?></td><td><?= $earned_credits ?></td><td class="fw-bold"><?= $sgpa ?></td><td class="fw-bold"><?= $res_status ?></td></tr></tbody></table></div>
        <div style="flex: 1;"><table class="marks-table" style="margin-top: 0;"><thead style="background: #e9ecef;"><tr><th colspan="3">Cumulative Record</th></tr>
        <tr><th>Total Credits</th><th>Total Credit Earned</th><th>CGPA</th></tr></thead><tbody><tr><td><?= $total_credits ?></td><td><?= $earned_credits ?></td><td class="fw-bold"><?= $sgpa ?></td></tr></tbody></table></div>
    </div>

    <?php elseif ($is_bed): ?>
    <!-- Marks Table for B.Ed / M.Ed (Image 2 Layout) -->
    <table class="marks-table">
        <thead>
            <tr><th width="45%" colspan="2" rowspan="2">PAPERS</th><th colspan="2">Maximum Marks</th><th colspan="2">Marks Obtained</th><th width="10%" rowspan="2">TOTAL</th></tr>
            <tr><th width="8%">Exam</th><th width="10%">Sessional</th><th width="8%">Exam</th><th width="10%">Sessional</th></tr>
        </thead>
        <tbody>
            <?php 
            $t_max = 0; $t_obt = 0; $p_max = 0; $p_obt = 0; $maj_count = 0;
            $roman = [1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI'];
            foreach ($student_data['papers'] as $p): 
                $is_prac = ($p['practical_max_marks'] > 0 || strpos(strtolower($p['paper_title']), 'practical') !== false);
                if (!$is_prac):
                    $em_max = (float)$p['mid_sem_max_marks'] ?: 90; $ss_max = (float)$p['max_marks'] ?: 10;
                    $em_obt = (float)$p['mid_sem_marks_obt']; $ss_obt = (float)$p['marks_obt'];
                    $tot = $em_obt + $ss_obt; $t_max += ($em_max + $ss_max); $t_obt += $tot; $maj_count++;
                    $lbl = 'PAPER - ' . ($roman[$maj_count] ?? $maj_count);
            ?>
                <tr><td width="15%" class="fw-bold text-start" style="padding-left:10px;"><?= $lbl ?></td><td class="text-start"><?= strtoupper($p['paper_title']) ?></td>
                <td><?= (int)$em_max ?></td><td><?= (int)$ss_max ?></td><td><?= (int)$em_obt ?></td><td><?= (int)$ss_obt ?></td><td class="fw-bold"><?= (int)$tot ?></td></tr>
            <?php else:
                $pmax = (float)$p['practical_max_marks'] ?: 100; $pobt = (float)$p['practical_marks_obt'] ?: (float)$p['marks_obt'];
                $p_max += $pmax; $p_obt += $pobt;
            endif; endforeach; ?>
            <tr><td colspan="7" class="text-start fw-bold" style="padding:6px 10px; background:#f5f5f5;">Practical/Viva-Voce</td></tr>
            <tr><td class="fw-bold text-start" style="padding-left:10px;">PRACTICAL</td><td class="text-start">PRACTICAL</td><td></td><td><?= (int)$p_max ?></td><td></td><td>-</td><td class="fw-bold"><?= (int)$p_obt ?></td></tr>
        </tbody>
    </table>
    <div style="border: 1px solid #555; border-top:none; display:flex; font-size:12px; font-weight:bold;">
        <div style="flex:1; padding:10px; border-right:1px solid #555;">
            THEORY RESULT : <?= ($t_obt >= $t_max*0.36)?'PASSED':'FAILED' ?><br>
            PRACTICAL RESULT : <?= ($p_max==0 || $p_obt >= $p_max*0.36)?'PASSED':'FAILED' ?>
        </div>
        <div style="flex:1; padding:10px; text-align:right;">
            Marks Obtained Theory : <?= (int)$t_obt ?>/<?= (int)$t_max ?><br>
            Marks Obtained Practical : <?= (int)$p_obt ?>/<?= (int)$p_max ?>
        </div>
    </div>

    <?php elseif ($is_ma): ?>
    <!-- Marks Table for MA (Image 1 Layout) -->
    <table class="marks-table">
        <thead>
            <tr><th width="15%">SUBJECT TYPE</th><th width="40%">COURSE TITLE</th><th width="8%">TH</th><th width="8%">SESS</th><th width="10%">TOTAL</th><th width="6%">CR</th><th width="6%">GR</th><th width="10%">GRADE POINT</th></tr>
        </thead>
        <tbody>
            <?php
            $grand_max = 0; $grand_obt = 0; $total_credits = 0; $earned_credits = 0; $total_cgp = 0;
            foreach ($student_data['papers'] as $p) {
                $p_th_max = (float)$p['mid_sem_max_marks']; $p_th_obt = (float)$p['mid_sem_marks_obt'];
                $p_sess_max = (float)$p['max_marks']; $p_sess_obt = (float)$p['marks_obt'];
                if ($p['practical_max_marks'] > 0 && $p_th_max == 0) { $p_th_max = (float)$p['practical_max_marks']; $p_th_obt = (float)$p['practical_marks_obt']; }
                $p_total_max = $p_th_max + $p_sess_max; $p_total_obt = $p_th_obt + $p_sess_obt;
                if ($p_total_max > 0) { $grand_max += $p_total_max; $grand_obt += $p_total_obt; }
                $gr = calculate_grade_letter($p_total_obt, $p_total_max);
                $gp = calculate_grade($p_total_obt, $p_total_max);
                $cr = (float)($p['credit'] ?? 5); 
                $total_credits += $cr; 
                $total_cgp += ($cr * $gp);
                if ($gp >= 4) $earned_credits += $cr;
                ?>
                <tr><td class="fw-bold"><?= strtoupper($p['paper_type'] ?? 'MAJOR') ?></td><td class="text-start"><?= strtoupper($p['paper_code'] . ' ' . $p['paper_title']) ?></td>
                <td><?= (int)$p_th_obt ?></td><td><?= (int)$p_sess_obt ?></td><td class="fw-bold"><?= str_pad((int)$p_total_obt, 3, '0', STR_PAD_LEFT) ?></td>
                <td><?= $cr ?></td><td class="fw-bold"><?= $gr ?></td><td class="fw-bold"><?= $gp ?></td></tr>
            <?php } ?>
            <tr style="background:#f5f5f5; font-weight:bold;"><td colspan="5" class="text-center">TOTAL CREDITS</td><td><?= $total_credits ?></td><td colspan="2"></td></tr>
        </tbody>
    </table>
    <div style="display: flex; gap: 15px; margin-top: 15px;">
        <div style="flex: 1.5;"><table class="marks-table" style="margin-top: 0;"><thead style="background: #e9ecef;"><tr><th colspan="5">Semester Records</th></tr>
        <tr><th width="10%">SEM</th><th>Total Credits</th><th>Total Credit Earned</th><th>SGPA</th><th>RESULT</th></tr></thead>
        <tbody><?php $sgpa = ($total_credits > 0) ? number_format($total_cgp / $total_credits, 2) : '0.00'; ?>
        <tr><td class="fw-bold"><?= strtoupper($student_data['semester'] ?? '1') ?></td><td><?= $total_credits ?></td><td><?= $earned_credits ?></td><td class="fw-bold"><?= $sgpa ?></td><td class="fw-bold"><?= ($earned_credits >= $total_credits*0.5)?'PASS':'FAIL' ?></td></tr></tbody></table></div>
        <div style="flex: 1;"><table class="marks-table" style="margin-top: 0;"><thead style="background: #e9ecef;"><tr><th colspan="3">Cumulative Record</th></tr>
        <tr><th>Total Credits</th><th>Total Credit Earned</th><th>CGPA</th></tr></thead><tbody><tr><td><?= $total_credits ?></td><td><?= $earned_credits ?></td><td class="fw-bold"><?= $sgpa ?></td></tr></tbody></table></div>
    </div>

    <?php else: ?>
    <!-- Marks Table for Others (B.Sc, B.Com - Original Standard) -->
    <table class="marks-table">
        <thead>
            <tr><th width="40%" rowspan="2">PAPERS</th><th colspan="2">Maximum Marks</th><th colspan="2">Marks Obtained</th><th width="10%" rowspan="2">TOTAL</th></tr>
            <tr><th>Exam</th><th>Sessional</th><th>Exam</th><th>Sessional</th></tr>
        </thead>
        <tbody>
            <?php $grand_max = 0; $grand_obt = 0;
            foreach($student_data['papers'] as $paper): 
                $emax = (float)$paper['mid_sem_max_marks'] ?: 75; $smax = (float)$paper['max_marks'] ?: 25;
                $eobt = (float)$paper['mid_sem_marks_obt']; $sobt = (float)$paper['marks_obt'];
                $tot = $eobt + $sobt; $grand_max += ($emax + $smax); $grand_obt += $tot;
            ?>
            <tr><td class="text-start"><?= strtoupper($paper['paper_title']) ?></td><td><?= (int)$emax ?></td><td><?= (int)$smax ?></td><td><?= (int)$eobt ?></td><td><?= (int)$sobt ?></td><td><?= (int)$tot ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="mt-2 fw-bold">TOTAL: <?= (int)$grand_obt ?> / <?= (int)$grand_max ?> | RESULT: <?= ($grand_obt >= ($grand_max * 0.33)) ? 'PASS' : 'FAIL' ?></div>
    <?php endif; ?>

   <div class="footer-note mt-4 d-flex justify-content-between align-items-end" style="border-top:1px solid #eee; padding-top:15px;">
        <div style="flex: 1;">
            <strong>Disclaimer:</strong> <?= $college['short_name'] ?? 'RVM' ?>, Jaunpur is not responsible for any inadvertent error that may have crept in the results being published here on this website. The results published here are for immediate information to the examinees only and may not be considered as final result.<br><br>
            <strong>RESULT DECLARATION DATE :</strong>
            <?php 
            if(!empty($student_data['result_declaration_date'])){
                echo date('d-m-Y', strtotime($student_data['result_declaration_date']));
            }
            ?> 
        </div>
        <div class="text-center" style="min-width: 250px;">
            <div style="margin-bottom: 5px;">
                <!--<img src="images/principal_sign.jpg" style="height: 45px;" alt="Signature">-->
            </div>
            <!--<strong>(ABHISHEK MISHRA)</strong>-->
            <br>
            <span style="font-size: 13.5px;">Controller of Examinations</span>
        </div>
    </div>
<?php endif; ?>



<?php 
function number_to_word($num) {
    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    return strtoupper($f->format($num));
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
