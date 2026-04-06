<?php
ob_start();
session_start();
include("script/settings.php");
include("crosslist_functions.php");

// Handle QR Generation POST
if (isset($_POST['action']) && $_POST['action'] == 'generate_qr_bulk') {
    if (!empty($_POST['selected_students'])) {
        $selected_ids = array_map('intval', $_POST['selected_students']);
        $ids_str = implode(',', $selected_ids);
        $update_sql = "UPDATE exam_student_info SET qr_status = 1 WHERE sno IN ($ids_str)";
        if (mysqli_query($db, $update_sql)) {
            $msg = count($selected_ids) . " Student QR codes generated successfully!";
            $msg_type = "success";
        } else {
            $msg = "Error generating QR codes: " . mysqli_error($db);
            $msg_type = "danger";
        }
    } else {
        $msg = "No students selected!";
        $msg_type = "warning";
    }
}

if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();

// Fetch courses for dropdown
$courses_sql = "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC";
$courses_res = mysqli_query($db, $courses_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EXAM STUDENT QR GENRATE REPORT</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #f8f9fa; color: #333; margin: 0; padding: 0; }
        .main-container { padding: 20px; }
        
        .report-header {
            background-color: #5d6d7e;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 32px;
            font-weight: 500;
            text-transform: uppercase;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 400px;
        }

        .filter-label {
            font-weight: 600;
            font-size: 16px;
        }

        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .form-select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            width: 300px;
            background-color: #fff;
        }

        .btn-filter {
            background-color: #448aff;
            color: white;
            border: none;
            padding: 8px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-filter:hover { background-color: #2979ff; }

        .report-table-container {
            background: white;
            border-radius: 8px;
            overflow-x: auto;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .report-table th {
            background-color: #007bff;
            color: white;
            padding: 12px 8px;
            text-align: center;
            font-weight: 500;
            border: 1px solid #dee2e6;
            white-space: nowrap;
        }

        .report-table td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            text-align: center;
            color: #495057;
            background-color: #fcfcfc;
        }

        .report-table tr:nth-child(even) td {
            background-color: #f8f9fa;
        }

        .no-data-msg {
            color: #dc3545;
            padding: 20px;
            text-align: center;
            font-weight: bold;
        }

        .footer-actions {
            text-align: center;
            margin-top: 30px;
            padding-bottom: 50px;
        }

        .btn-generate {
            background-color: #7cb342;
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .btn-generate:hover { background-color: #689f38; }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .checkbox-cell {
            width: 30px;
        }

        /* Responsive */
        @media print {
            .no-print { display: none !important; }
            .main-container { padding: 0; }
            .report-table th { background-color: #007bff !important; color: white !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="report-header">
        EXAM STUDENT QR GENRATE REPORT
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-<?= $msg_type ?> no-print"><?= $msg ?></div>
    <?php endif; ?>

    <div class="filter-section no-print">
        <form method="POST" action="">
            <div class="filter-group">
                <label class="filter-label">Class</label>
                <div class="filter-controls">
                    <select name="course_id" class="form-select" required>
                        <option value="">Select Class</option>
                        <?php 
                        mysqli_data_seek($courses_res, 0);
                        while($row = mysqli_fetch_assoc($courses_res)): 
                        ?>
                            <option value="<?= $row['sno'] ?>" <?= (isset($_POST['course_id']) && $_POST['course_id'] == $row['sno']) ? 'selected' : '' ?>>
                                <?= $row['class_description'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-filter">Filter</button>
                    <?php if (isset($_POST['course_id'])): ?>
                        <button type="button" onclick="window.print()" class="btn-filter" style="background-color: #6c757d;">Print</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <form method="POST" action="">
        <input type="hidden" name="action" value="generate_qr_bulk">
        <input type="hidden" name="course_id" value="<?= $_POST['course_id'] ?? '' ?>">
        
        <div class="report-table-container">
            <table class="report-table">
                <thead>
                    <tr>
                        <th class="checkbox-cell no-print"><input type="checkbox" id="selectAll"></th>
                        <th>S. No.</th>
                        <th>QR Code</th>
                        <th>Full Name</th>
                        <th>Father Name</th>
                        <th>Class</th>
                        <th>Date of Birth</th>
                        <th>Mobile No</th>
                        <th>Exam Form No</th>
                        <th>Roll No</th>
                        <th>UIN Number</th>
                        <th>Marks</th>
                        <th>Passing Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($_POST['course_id'])) {
                        $course_id = intval($_POST['course_id']);
                        $students = fetch_crosslist_data($db, $course_id, null, null);
                        
                        if (!empty($students)) {
                            foreach ($students as $stu) {
                                // Logic for Marks and Status
                                $total_max = 0; $total_obt = 0; $earned_credits = 0; $total_credits = 0;
                                foreach ($stu['papers'] as $p) {
                                    $m = (float)$p['mid_sem_max_marks'] + (float)$p['max_marks'] + (float)$p['practical_max_marks'];
                                    $o = (float)$p['mid_sem_marks_obt'] + (float)$p['marks_obt'] + (float)$p['practical_marks_obt'];
                                    if ($m > 0) {
                                        $total_max += $m; $total_obt += $o;
                                        $cr = (float)($p['credit'] ?? 4); $total_credits += $cr;
                                        if (calculate_grade($o, $m) >= 4) $earned_credits += $cr;
                                    }
                                }
                                $is_bed = (strpos(strtoupper($stu['class_description']), 'BED') !== false);
                                $status = ($is_bed) ? ($total_obt >= ($total_max * 0.36) ? "PASSED" : "FAILED") : ($earned_credits >= ($total_credits * 0.5) ? "PASSED" : "FAILED");
                                if ($total_max == 0) $status = "N/A";

                                $q_extra = mysqli_query($db, "SELECT dob, p_mobile, form_no FROM student_info WHERE sno = '".$stu['exam_sno']."' OR enroll_no = '".($stu['enroll_no'] ?? '')."' LIMIT 1");
                                $ext = mysqli_fetch_assoc($q_extra);
                                
                                ?>
                                <tr>
                                    <td class="no-print"><input type="checkbox" name="selected_students[]" value="<?= $stu['exam_sno'] ?>" class="student-checkbox"></td>
                                    <td><?= $stu['exam_sno'] ?></td>
                                    <td>
                                        <?php if ($stu['qr_status'] == 1): ?>
                                            <span style="color: #2e7d32; font-weight: bold; font-size: 12px;">GENERATED</span>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 10px;">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= strtoupper($stu['student_name']) ?></td>
                                    <td><?= strtoupper($stu['father_name']) ?></td>
                                    <td><?= $stu['class_description'] ?></td>
                                    <td><?= !empty($ext['dob']) ? date('d-m-Y', strtotime($ext['dob'])) : 'N/A' ?></td>
                                    <td><?= $ext['p_mobile'] ?? 'N/A' ?></td>
                                    <td><?= $ext['form_no'] ?? $stu['exam_sno'] ?></td>
                                    <td><?= $stu['exam_roll_no'] ?></td>
                                    <td><?= $stu['uin_no'] ?? 'N/A' ?></td>
                                    <td><?= (int)$total_obt . "/" . (int)$total_max ?></td>
                                    <td style="<?= ($status == 'FAILED') ? 'color: red;' : '' ?>"><?= $status ?></td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="12" class="no-data-msg">No records found for the selected class.</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="12" class="no-data-msg">Please select a class to view the data.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($students)): ?>
        <div class="footer-actions no-print">
            <button type="submit" class="btn-generate">Generate QR Codes</button>
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>

</body>
</html>
<?php if (function_exists('page_footer')) page_footer(); ?>
