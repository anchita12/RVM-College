<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = $db; // use the admin $db connection

$row = [];
$found = false;
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

if (isset($_GET['success']))
    $success_msg = $_GET['success'];

// Fetch classes
$classes_query = mysqli_query($conn, "SELECT sno, class_description FROM class_detail ORDER BY sno ASC");
$classes = [];
if ($classes_query) {
    while ($c = mysqli_fetch_assoc($classes_query)) {
        $classes[] = $c;
    }
}

// 1. Handle Verify from single view
if (isset($_POST['verify'])) {
    $exam_form_no_hidden = $_POST['exam_form_no_hidden'];
    $exam_fees = $_POST['exam_fees'];
    $verify_time = date('Y-m-d H:i:s');
    $verify_by = $_SESSION['user_id'] ?? 1;

    $upd = mysqli_query($conn, "UPDATE exam_student_info SET verify_status=1, exam_fees='$exam_fees', verify_by='$verify_by', verify_time='$verify_time' WHERE exam_form_no='$exam_form_no_hidden'");

    if ($upd) {
        $_SESSION['success_msg'] = "Record verified successfully!";
        header("Location: exam_form_verification.php?tab=verification");
        exit;
    } else {
        $_SESSION['error_msg'] = "Failed to verify the record.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 2. Handle Verify from list modal
if (isset($_POST['list_verify'])) {
    $exam_form_no_hidden = $_POST['verify_exam_form_no'];
    $exam_fees = $_POST['list_exam_fees'];
    $verify_time = date('Y-m-d H:i:s');
    $verify_by = $_SESSION['user_id'] ?? 1;

    $upd = mysqli_query($conn, "UPDATE exam_student_info SET verify_status=1, exam_fees='$exam_fees', verify_by='$verify_by', verify_time='$verify_time' WHERE exam_form_no='$exam_form_no_hidden'");
    if ($upd) {
        $_SESSION['success_msg'] = "Student verified successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $_SESSION['error_msg'] = "Failed to verify the student.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 3. Handle Admit Card Status toggle (Individual)
if (isset($_POST['admit_status_submit'])) {
    $exam_form_no_status = $_POST['exam_form_no_status'];
    $admit_status = $_POST['admit_status_value'];
    $upd = mysqli_query($conn, "UPDATE exam_student_info SET admit_card_allow='$admit_status' WHERE exam_form_no='$exam_form_no_status'");
    if ($upd) {
        $_SESSION['success_msg'] = "Admit card status updated successfully!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $_SESSION['error_msg'] = "Failed to update admit card status.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 3.1 Handle Bulk Admit Card Status (New)
if (isset($_POST['bulk_status_submit'])) {
    $exam_form_nos = $_POST['selected_forms'] ?? [];
    $new_status = $_POST['bulk_status_value'];
    if (!empty($exam_form_nos)) {
        $nos_str = "'" . implode("','", array_map(function ($no) use ($conn) {
            return mysqli_real_escape_string($conn, $no);
        }, $exam_form_nos)) . "'";
        $upd = mysqli_query($conn, "UPDATE exam_student_info SET admit_card_allow='$new_status' WHERE exam_form_no IN ($nos_str)");
        if ($upd) {
            $_SESSION['success_msg'] = "Bulk status updated successfully!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['error_msg'] = "Failed to perform bulk status update.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Please select records.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 4. Handle List Search
$list_results = null;
if (isset($_GET['filter_class']) || isset($_GET['filter_roll_no'])) {
    $filter_class = $_GET['filter_class'] ?? '';
    $filter_roll_no = $_GET['filter_roll_no'] ?? '';

    $where = "WHERE 1=1";
    if (!empty($filter_class)) {
        $where .= " AND e.course_name = '" . mysqli_real_escape_string($conn, $filter_class) . "'";
    }
    if (!empty($filter_roll_no)) {
        $where .= " AND (e.college_roll_no = '" . mysqli_real_escape_string($conn, $filter_roll_no) . "' OR e.exam_roll_no = '" . mysqli_real_escape_string($conn, $filter_roll_no) . "')";
    }

    $sql_list = "SELECT e.*, c.class_description, e.student_name, s.father_name, e.sno as student_exam_sno
                 FROM exam_student_info e
                 LEFT JOIN student_info2 s ON s.uin = e.uin_no
                 LEFT JOIN class_detail c ON c.sno = e.course_name
                 $where ORDER BY e.sno DESC";

    $list_results = mysqli_query($conn, $sql_list);
}


// 5. Handle Single Student View
if (isset($_POST['search']) || isset($_GET['search'])) {
    $exam_form_no = isset($_POST['search']) ? $_POST['exam_form_no'] : $_GET['exam_form_no'];

    $sql = "SELECT e.*, c.class_description, cat.category_name, s.father_name, s.mother_name, s.gender, s.photo_id, s.signature_id
        FROM exam_student_info e
        LEFT JOIN student_info2 s ON s.uin = e.uin_no
        LEFT JOIN class_detail c ON c.sno = e.course_name
        LEFT JOIN categories cat ON cat.categories_sno = s.category
        WHERE e.exam_form_no='$exam_form_no'";

    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);

        if ($row['verify_status'] == 1) {
            $form_filled_popup = true;
            $form_status = "Form Filled";
            $found = false;
        } else {
            $form_filled_popup = false;
            $form_status = "Not Filled";
            $found = true;
        }
    } else {
        $error_msg = "No record found for Exam Form No: " . htmlspecialchars($exam_form_no);
    }
}

// 6. Handle Bulk Verify
if (isset($_POST['bulk_verify_submit'])) {
    $exam_form_nos = $_POST['selected_forms'] ?? [];
    $bulk_fees = $_POST['bulk_exam_fees'];
    $verify_time = date('Y-m-d H:i:s');
    $verify_by = $_SESSION['user_id'] ?? 1;

    if (!empty($exam_form_nos) && $bulk_fees > 0) {
        $nos_str = "'" . implode("','", array_map(function ($no) use ($conn) {
            return mysqli_real_escape_string($conn, $no);
        }, $exam_form_nos)) . "'";
        $upd = mysqli_query($conn, "UPDATE exam_student_info SET verify_status=1, exam_fees='$bulk_fees', verify_by='$verify_by', verify_time='$verify_time' WHERE exam_form_no IN ($nos_str)");
        if ($upd) {
            $_SESSION['success_msg'] = "Bulk verification successful for " . count($exam_form_nos) . " records!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['error_msg'] = "Failed to perform bulk verification.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Please select records and enter valid exam fees.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 7. Handle Exam Form Controller Bulk Update
if (isset($_POST['update_controller_bulk'])) {
    $class_snos = $_POST['class_ids'] ?? [];
    $action = $_POST['bulk_action'] ?? ''; // enable_regular, disable_regular, enable_back, disable_back

    if (!empty($class_snos)) {
        $ids_str = implode(",", array_map('intval', $class_snos));
        $sql = "";
        if ($action == 'enable_regular')
            $sql = "UPDATE class_detail SET exam_form='1' WHERE sno IN ($ids_str)";
        elseif ($action == 'disable_regular')
            $sql = "UPDATE class_detail SET exam_form='0' WHERE sno IN ($ids_str)";
        elseif ($action == 'enable_back')
            $sql = "UPDATE class_detail SET show_back='1' WHERE sno IN ($ids_str)";
        elseif ($action == 'disable_back')
            $sql = "UPDATE class_detail SET show_back='0' WHERE sno IN ($ids_str)";

        if ($sql && mysqli_query($conn, $sql)) {
            $_SESSION['success_msg'] = "Class settings updated successfully!";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $_SESSION['error_msg'] = "Failed to update class settings.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "No classes selected.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 7.1 Handle Individual Class Toggle
if (isset($_POST['toggle_class_form'])) {
    $class_id = intval($_POST['class_id']);
    $field = mysqli_real_escape_string($conn, $_POST['field']);
    $new_val = intval($_POST['new_val']);

    $upd = mysqli_query($conn, "UPDATE class_detail SET $field='$new_val' WHERE sno='$class_id'");
    if ($upd) {
        $_SESSION['success_msg'] = "Class setting updated!";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    } else {
        $_SESSION['error_msg'] = "Update failed.";
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// 8. Handle Controller Search & Integration of Counting
$controller_results = null;
if (isset($_GET['controller_search'])) {
    $exam_type = $_GET['exam_type'] ?? '';
    $c_class = $_GET['c_class'] ?? '';

    $where = "WHERE 1=1";
    if ($exam_type == 'Odd') {
        $where .= " AND semester % 2 != 0";
    } elseif ($exam_type == 'Even') {
        $where .= " AND semester % 2 = 0";
    }

    if (!empty($c_class)) {
        $where .= " AND cd.sno = '" . intval($c_class) . "'";
    }

    // Integrated query to get class info + student counts
    $sql_controller = "
        SELECT 
            cd.sno, 
            cd.class_description, 
            cd.exam_form, 
            cd.show_back,
            (SELECT COUNT(*) FROM (
                SELECT uin, class FROM student_info 
                UNION ALL 
                SELECT uin, class FROM student_info2
            ) s WHERE s.class = cd.sno) as total_students,
            (SELECT COUNT(*) FROM (
                SELECT uin, class FROM student_info 
                UNION ALL 
                SELECT uin, class FROM student_info2
            ) s 
            JOIN exam_student_info e ON s.uin = e.uin_no AND cd.semester = e.exam_id
            WHERE s.class = cd.sno) as filled_forms
        FROM class_detail cd 
        $where 
        ORDER BY cd.display_sort ASC, cd.sno ASC";

    $controller_results = mysqli_query($conn, $sql_controller);
}

// 9. Handle Drill-down Logic
$drill_results = null;
$drill_counts = null;
$class_info = null;
if (isset($_GET['drill_down'])) {
    $c_id = intval($_GET['class_id']);
    $c_type = $_GET['c_type'] ?? 'total'; // total, filled, pending
    $v_status = isset($_GET['v_status']) ? $_GET['v_status'] : null;
    $a_status = isset($_GET['a_status']) ? $_GET['a_status'] : null;

    // Fetch class details
    $class_info_res = mysqli_query($conn, "SELECT sno, class_description, semester FROM class_detail WHERE sno = $c_id");
    $class_info = mysqli_fetch_assoc($class_info_res);
    $semester = $class_info['semester'] ?? 0;

    // Base student list for this class (Union of student_info and student_info2)
    $base_sql = "(
        SELECT uin, stu_name as student_name, father_name, p_mobile as mobile_no, class FROM student_info WHERE class = $c_id
        UNION ALL 
        SELECT uin, stu_name as student_name, father_name, p_mobile as mobile_no, class FROM student_info2 WHERE class = $c_id
    ) s";

    // Joins and Filters
    $where_clauses = ["1=1"];
    if ($c_type == 'filled') {
        $where_clauses[] = "e.exam_form_no IS NOT NULL";
    } elseif ($c_type == 'pending') {
        $where_clauses[] = "e.exam_form_no IS NULL";
    }

    if ($v_status !== null) {
        if ($v_status == '1') {
            $where_clauses[] = "e.verify_status = 1";
        } else {
            $where_clauses[] = "(e.verify_status = 0 OR e.verify_status IS NULL)";
        }
    }

    if ($a_status !== null) {
        if ($a_status == '1') {
            $where_clauses[] = "e.admit_download = 1";
        } else {
            $where_clauses[] = "(e.admit_download = 0 OR e.admit_download IS NULL)";
        }
    }

    $where_sql = implode(" AND ", $where_clauses);

    // Get Counts for next level options
    $counts_sql = "
        SELECT 
            COUNT(*) as total_count,
            COUNT(CASE WHEN e.verify_status = 1 THEN 1 END) as verified_count,
            COUNT(CASE WHEN e.verify_status != 1 OR e.verify_status IS NULL THEN 1 END) as not_verified_count,
            COUNT(CASE WHEN e.verify_status = 1 AND e.admit_download = 1 THEN 1 END) as admit_downloaded_count,
            COUNT(CASE WHEN e.verify_status = 1 AND (e.admit_download = 0 OR e.admit_download IS NULL) THEN 1 END) as admit_not_downloaded_count
        FROM $base_sql
        LEFT JOIN exam_student_info e ON s.uin = e.uin_no AND e.exam_id = '$semester'
        WHERE " . ($c_type == 'filled' ? "e.exam_form_no IS NOT NULL" : ($c_type == 'pending' ? "e.exam_form_no IS NULL" : "1=1"));

    $drill_counts_res = mysqli_query($conn, $counts_sql);
    $drill_counts = mysqli_fetch_assoc($drill_counts_res);

    // Final student list for the table
    $sql_drill_list = "
        SELECT s.*, e.exam_form_no, e.verify_status, e.admit_download, e.exam_roll_no, e.college_roll_no,
               e.verify_time, u.username as verified_by_name
        FROM $base_sql 
        LEFT JOIN exam_student_info e ON s.uin = e.uin_no AND e.exam_id = '$semester'
        LEFT JOIN users u ON u.id = e.verify_by
        WHERE $where_sql
        ORDER BY s.student_name ASC";

    $drill_results = mysqli_query($conn, $sql_drill_list);
}

// Include sidebar and header
if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    /* Fix Bootstrap overriding admin sidebar/header */
    aside a,
    aside nav a,
    aside .select-none a,
    header a,
    header nav a,
    .erp-header a {
        text-decoration: none !important;
        border-bottom: none !important;
    }

    aside .child-menu .submenu-link {
        text-decoration: none !important;
    }

    /* Keep header text white (Bootstrap overrides to blue) */
    header a,
    header a:hover,
    header a span,
    header .text-2xl {
        color: #fff !important;
        text-decoration: none !important;
    }


    .search-box {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
    }

    .card-header {
        background: #2c3e50 !important;
        color: #fff !important;
        font-weight: bold;
    }

    input.form-control[readonly] {
        background: #e9ecef;
        font-weight: 500;
    }

    .btn-success {
        background: linear-gradient(45deg, #28a745, #218838);
        border: none;
        font-weight: 600;
    }

    .top-menu {
        margin-top: 15px;
        margin-bottom: 15px;
    }

    /* ===== TAB NAVIGATION ===== */
    .tab-container {
        background: #fff;
        border-radius: 12px;
        padding: 0;
        margin-bottom: 30px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .tab-navigation {
        display: flex;
        background: #f8f9fa;
        border-bottom: 3px solid #e9ecef;
        margin: 0;
        padding: 0;
    }

    .tab-button {
        flex: 1;
        padding: 18px 30px;
        font-size: 16px;
        font-weight: 600;
        color: #6c757d;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        text-align: center;
    }

    .tab-button:hover {
        background: rgba(13, 110, 253, 0.05);
        color: #0d6efd;
    }

    .tab-button.active {
        color: #0d6efd;
        background: #fff;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        right: 0;
        height: 3px;
        background: #0d6efd;
    }

    .tab-button i {
        margin-right: 8px;
        font-size: 18px;
    }

    .tab-content {
        padding: 30px;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Drill-down Styles */
    .drill-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        text-decoration: none !important;
        overflow: hidden;
    }

    .drill-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }

    .drill-card .card-body {
        padding: 20px;
    }

    .drill-card .count {
        font-size: 24px;
        font-weight: 800;
        display: block;
    }

    .drill-card .label {
        font-size: 14px;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .breadcrumb-item a {
        color: #0d6efd;
        text-decoration: none;
    }

    .breadcrumb-item.active {
        font-weight: 700;
        color: #212529;
    }
</style>

<main class="bg-white min-h-screen">
    <div class="flex items-center justify-between px-4 py-4 border-b lg:py-6 dark:border-primary-darker">
        <h1 class="text-2xl font-semibold">Exam Form Management</h1>
    </div>

    <div class="p-4">


        <!-- ================= TAB NAVIGATION ================= -->
        <div class="tab-container">
            <div class="tab-navigation">
                <?php $active_tab = isset($_GET['tab']) ? $_GET['tab'] : (isset($_GET['controller_search']) ? 'controller' : 'verification'); ?>
                <button class="tab-button <?= ($active_tab == 'verification') ? 'active' : '' ?>"
                    onclick="switchTab('verification')" id="verificationTab">
                    Exam Form Verification
                </button>
                <button class="tab-button <?= ($active_tab == 'controller') ? 'active' : '' ?>"
                    onclick="switchTab('controller')" id="controllerTab">
                    Exam Form Controller
                </button>
            </div>

            <div class="tab-content">
                <!-- ================= VERIFICATION TAB ================= -->
                <div id="verificationPane" class="tab-pane <?= ($active_tab == 'verification') ? 'active' : '' ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- SEARCH -->
                            <div class="search-box mb-4">
                                <form method="GET" class="row g-3">
                                    <input type="hidden" name="tab" value="verification">
                                    <div class="col-md-5">
                                        <label>Class</label>
                                        <select name="filter_class" class="form-select">
                                            <option value="">Select Class</option>
                                            <?php foreach ($classes as $c): ?>
                                                <option value="<?= $c['sno'] ?>" <?= (isset($_GET['filter_class']) && $_GET['filter_class'] == $c['sno']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($c['class_description']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label>Exam Roll No.</label>
                                        <input type="text" name="filter_roll_no" class="form-control"
                                            value="<?= isset($_GET['filter_roll_no']) ? htmlspecialchars($_GET['filter_roll_no']) : '' ?>"
                                            placeholder="Optional">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">Search List</button>
                                    </div>
                                </form>
                            </div>

                            <?php if (!empty($error_msg)): ?>
                                <div class="alert alert-danger text-center shadow-sm">
                                    <p class="mb-0"><?= $error_msg ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($success_msg)): ?>
                                <div class="alert alert-success text-center shadow-sm">
                                    <p class="mb-0"><?= $success_msg ?></p>
                                </div>
                            <?php endif; ?>

                            <?php if ($list_results && !isset($_GET['search']) && !isset($_POST['search'])): ?>
                                <form method="POST" id="bulkActionForm">
                                    <div class="mb-3 p-3 bg-light border rounded d-flex align-items-center gap-3 flex-wrap">
                                        <div class="fw-bold">Bulk Actions:</div>

                                        <!-- Verification Action -->
                                        <div class="d-flex align-items-center gap-2 border-end pe-3">
                                            <input type="number" step="0.01" name="bulk_exam_fees"
                                                class="form-control form-control-sm" placeholder="Fees"
                                                style="width: 100px;">
                                            <button type="submit" name="bulk_verify_submit" class="btn btn-sm btn-warning"
                                                onclick="return confirm('Verify all selected students?')">Bulk fee
                                                verification</button>
                                        </div>

                                        <!-- Status Action -->
                                        <div class="d-flex align-items-center gap-2">
                                            <button type="submit" name="bulk_status_submit" class="btn btn-sm btn-success"
                                                onclick="document.getElementById('bulk_status_value').value=1; return confirm('Enable selected records?')">Bulk
                                                Enable</button>
                                            <button type="submit" name="bulk_status_submit" class="btn btn-sm btn-danger"
                                                onclick="document.getElementById('bulk_status_value').value=0; return confirm('Disable selected records?')">Bulk
                                                Disable</button>
                                            <input type="hidden" name="bulk_status_value" id="bulk_status_value" value="">
                                        </div>
                                    </div>

                                    <div class="table-responsive bg-white p-3 rounded shadow-sm mb-4">
                                        <table class="table table-bordered text-center align-middle">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                                                    <th>Sno.</th>
                                                    <th>Class</th>
                                                    <th>Student Name</th>
                                                    <th>Exam Roll No</th>
                                                    <th>UIN No.</th>
                                                    <th>Exam Form No.</th>
                                                    <th>Exam Form</th>
                                                    <th>Admit Card</th>
                                                    <th>Verification</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($list_results) > 0):
                                                    $idx = 1;
                                                    while ($lrow = mysqli_fetch_assoc($list_results)): ?>
                                                        <tr>
                                                            <td>
                                                                <input type="checkbox" name="selected_forms[]"
                                                                    value="<?= htmlspecialchars($lrow['exam_form_no']) ?>">
                                                            </td>
                                                            <td><?= $idx++ ?></td>
                                                            <td><?= htmlspecialchars($lrow['class_description'] ?? '') ?></td>
                                                            <td class="text-start">
                                                                <?= htmlspecialchars($lrow['student_name'] ?? '') ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($lrow['exam_roll_no'] ?? $lrow['college_roll_no']) ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($lrow['uin_no'] ?? '') ?></td>
                                                            <td><?= htmlspecialchars($lrow['exam_form_no'] ?? '') ?></td>
                                                            <td>
                                                                <a href="../admin/exam_form_check.php?success=1&id=<?= urlencode($lrow['student_exam_sno']) ?>"
                                                                    target="_blank" class="btn btn-sm btn-info text-white">View</a>
                                                            </td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-secondary"
                                                                    onclick="alert('Admit card not generated yet.')">Admit
                                                                    Card</button>
                                                            </td>
                                                            <td>
                                                                <?php if ($lrow['verify_status'] == 1): ?>
                                                                    <span class="badge bg-success">Verified</span>
                                                                <?php else: ?>
                                                                    <button type="button" class="btn btn-sm btn-warning"
                                                                        onclick="openVerifyModal('<?= htmlspecialchars($lrow['exam_form_no']) ?>')">Verify</button>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $allow = isset($lrow['admit_card_allow']) ? $lrow['admit_card_allow'] : 0;
                                                                if ($allow == 1): ?>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="exam_form_no_status"
                                                                            value="<?= htmlspecialchars($lrow['exam_form_no']) ?>">
                                                                        <input type="hidden" name="admit_status_value" value="0">
                                                                        <button type="submit" name="admit_status_submit"
                                                                            class="btn btn-sm btn-danger">Disable</button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <form method="POST" class="d-inline">
                                                                        <input type="hidden" name="exam_form_no_status"
                                                                            value="<?= htmlspecialchars($lrow['exam_form_no']) ?>">
                                                                        <input type="hidden" name="admit_status_value" value="1">
                                                                        <button type="submit" name="admit_status_submit"
                                                                            class="btn btn-sm btn-success">Enable</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; else: ?>
                                                    <tr>
                                                        <td colspan="11">No records found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            <?php endif; ?>


                            <?php if ($found) { ?>
                                <form method="POST"
                                    onsubmit="return confirm('Are you sure you want to verify this record?');">
                                    <input type="hidden" name="exam_form_no_hidden" value="<?= $row['exam_form_no'] ?>">
                                    <!-- COURSE -->
                                    <div class="card mb-3">
                                        <div class="card-header">Course Details</div>
                                        <div class="card-body row">
                                            <div class="col-md-6">
                                                <label>Exam Roll No.</label>
                                                <input class="form-control" value="<?= $row['college_roll_no'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Year / Semester</label>
                                                <input class="form-control" value="<?= $row['class_description'] ?>"
                                                    readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Student Type</label>
                                                <input class="form-control" value="<?= $row['student_type'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>College Roll No.</label>
                                                <input class="form-control" value="<?= $row['college_roll_no'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Exam Form No.</label>
                                                <input class="form-control" value="<?= $row['exam_form_no'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>UIN No.</label>
                                                <input class="form-control" value="<?= $row['uin_no'] ?>" readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- STUDENT -->
                                    <div class="card mb-3">
                                        <div class="card-header">Student Details</div>
                                        <div class="card-body row">
                                            <div class="col-md-6">
                                                <label>Name</label>
                                                <input class="form-control" value="<?= $row['student_name'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6">
                                                <label>Father Name</label>
                                                <input class="form-control" value="<?= $row['father_name'] ?? '' ?>"
                                                    readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Mother Name</label>
                                                <input class="form-control" value="<?= $row['mother_name'] ?? '' ?>"
                                                    readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>DOB</label>
                                                <input class="form-control" value="<?= $row['dob'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Aadhar</label>
                                                <input class="form-control" value="<?= $row['aadhar'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Email</label>
                                                <input class="form-control" value="<?= $row['email'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Mobile</label>
                                                <input class="form-control" value="<?= $row['mobile_no'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>WhatsApp No.</label>
                                                <input class="form-control" value="<?= $row['whatsapp_no'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Category</label>
                                                <input class="form-control"
                                                    value="<?= $row['category_name'] ?? $row['category'] ?>" readonly>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label>Gender</label>
                                                <input class="form-control"
                                                    value="<?= (isset($row['gender']) && $row['gender'] == 1) ? 'Male' : ((isset($row['gender']) && $row['gender'] == 2) ? 'Female' : 'Other') ?>"
                                                    readonly>
                                            </div>
                                            <?php if (!empty($row['photo_id'])): ?>
                                                <div class="col-md-6 text-center mt-3">
                                                    <label>Photo</label><br>
                                                    <img src="<?= $row['photo_id'] ?>" width="120" onerror="this.src=''">
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($row['signature_id'])): ?>
                                                <div class="col-md-6 text-center mt-3">
                                                    <label>Signature</label><br>
                                                    <img src="<?= $row['signature_id'] ?>" width="120" onerror="this.src=''">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- SUBJECT -->
                                    <div class="card mb-3">
                                        <div class="card-header">Subjects</div>
                                        <table class="table table-bordered text-center mb-0">
                                            <thead>
                                                <tr>
                                                    <th>SNO</th>
                                                    <th>Subject</th>
                                                    <th>Paper Code</th>
                                                    <th>Paper Title</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $exam_student_info_sno = $row['sno'];
                                                $order_clause = ($row['course_name'] == 8) ? "(p.paper_type = 'practical') ASC, " : "";
                                                $q_papers = mysqli_query($conn, "
                                        SELECT p.*, s.subject 
                                        FROM exam_student_paper_info epi
                                        JOIN add_subject_papers p ON p.sno = epi.add_subject_papers_sno
                                        LEFT JOIN add_subject s ON s.sno = p.subject_id
                                        WHERE epi.exam_student_info_sno = '$exam_student_info_sno' AND (p.paper_type != 'minor' OR p.paper_type IS NULL)
                                        ORDER BY $order_clause p.optional_paper ASC, p.sno ASC
                                    ");
                                                $i = 1;
                                                if ($q_papers && mysqli_num_rows($q_papers) > 0) {
                                                    while ($p = mysqli_fetch_assoc($q_papers)) {
                                                        echo "<tr>";
                                                        echo "<td>" . $i++ . "</td>";
                                                        echo "<td>" . htmlspecialchars($p['subject']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($p['paper_code']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($p['paper_title']) . "</td>";
                                                        echo "</tr>";
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4'>No subjects found.</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- VERIFICATION DETAILS -->
                                    <div class="card p-3 mb-5">
                                        <div class="card-header bg-primary text-white">Verification Action</div>
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-md-4">
                                                    <label class="fw-bold">Exam Fees <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" name="exam_fees"
                                                        class="form-control border-primary" required
                                                        value="<?= $row['exam_fees'] > 0 ? $row['exam_fees'] : '' ?>"
                                                        placeholder="Enter amount">
                                                </div>

                                                <div class="col-md-4 mt-4 text-center">
                                                    <?php if ($row['verify_status'] == 1): ?>
                                                        <button type="button" class="btn btn-success px-4" disabled>Already
                                                            Verified</button>
                                                    <?php else: ?>
                                                        <button type="submit" name="verify" class="btn btn-success px-4">Proceed
                                                            to
                                                            Verify</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- ================= CONTROLLER TAB ================= -->
                <div id="controllerPane" class="tab-pane <?= ($active_tab == 'controller') ? 'active' : '' ?>">
                    <div class="search-box mb-4">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="tab" value="controller">
                            <div class="col-md-5">
                                <label>Exam Type</label>
                                <select name="exam_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">Select Type</option>
                                    <option value="Odd" <?= (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Odd') ? 'selected' : '' ?>>Odd</option>
                                    <option value="Even" <?= (isset($_GET['exam_type']) && $_GET['exam_type'] == 'Even') ? 'selected' : '' ?>>Even</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label>Class</label>
                                <select name="c_class" class="form-select">
                                    <option value="">All Classes</option>
                                    <?php
                                    $ctype = $_GET['exam_type'] ?? '';
                                    $cwhere = "1=1";
                                    if ($ctype == 'Odd')
                                        $cwhere = "semester % 2 != 0";
                                    if ($ctype == 'Even')
                                        $cwhere = "semester % 2 = 0";
                                    $cq = mysqli_query($conn, "SELECT sno, class_description FROM class_detail WHERE $cwhere ORDER BY display_sort ASC");
                                    while ($cr = mysqli_fetch_assoc($cq)) {
                                        echo '<option value="' . $cr['sno'] . '" ' . ((isset($_GET['c_class']) && $_GET['c_class'] == $cr['sno']) ? 'selected' : '') . '>' . htmlspecialchars($cr['class_description']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" name="controller_search" value="1"
                                    class="btn btn-primary w-100">Search</button>
                            </div>
                        </form>
                    </div>

                    <?php if ($controller_results): ?>
                        <form method="POST">
                            <div class="mb-3 p-3 bg-light border rounded d-flex align-items-center gap-3 flex-wrap">
                                <div class="fw-bold">Bulk Controls:</div>

                                <div class="d-flex align-items-center gap-1 border-end pe-3">
                                    <span class="small fw-bold">Regular exam form:</span>
                                    <button type="submit" name="update_controller_bulk" class="btn btn-sm btn-success"
                                        onclick="document.getElementById('bulk_action').value='enable_regular'; return confirm('Enable Regular for selected?')">Enable</button>
                                    <button type="submit" name="update_controller_bulk" class="btn btn-sm btn-danger"
                                        onclick="document.getElementById('bulk_action').value='disable_regular'; return confirm('Disable Regular for selected?')">Disable</button>
                                </div>

                                <div class="d-flex align-items-center gap-1">
                                    <span class="small fw-bold">Back exam form:</span>
                                    <button type="submit" name="update_controller_bulk" class="btn btn-sm btn-success"
                                        onclick="document.getElementById('bulk_action').value='enable_back'; return confirm('Enable Back for selected?')">Enable</button>
                                    <button type="submit" name="update_controller_bulk" class="btn btn-sm btn-danger"
                                        onclick="document.getElementById('bulk_action').value='disable_back'; return confirm('Disable Back for selected?')">Disable</button>
                                </div>

                                <input type="hidden" name="bulk_action" id="bulk_action" value="">
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold">Exam Form Summary</div>
                                <button type="button" onclick="exportToExcel('summaryTable', 'Exam_Form_Summary')"
                                    class="btn btn-success btn-sm">
                                    <i class="fa fa-file-excel me-1"></i> Download Summary
                                </button>
                            </div>
                            <div class="table-responsive bg-white p-3 rounded shadow-sm">
                                <table class="table table-bordered text-center align-middle" id="summaryTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><input type="checkbox" onclick="toggleSelectAllController(this)"></th>
                                            <th>Sno</th>
                                            <th>Class Description</th>
                                            <th>Total Students</th>
                                            <th>Forms Filled</th>
                                            <th>Forms Pending</th>
                                            <th>Regular Form</th>
                                            <th>Back Form</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $idx = 1;
                                        while ($crow = mysqli_fetch_assoc($controller_results)):
                                            $pending = $crow['total_students'] - $crow['filled_forms'];
                                            ?>
                                            <tr>
                                                <td><input type="checkbox" name="class_ids[]" value="<?= $crow['sno'] ?>"></td>
                                                <td><?= $idx++ ?></td>
                                                <td class="text-start"><?= htmlspecialchars($crow['class_description']) ?></td>
                                                <td class="fw-bold">
                                                    <a href="?tab=controller&drill_down=1&class_id=<?= $crow['sno'] ?>&c_type=total"
                                                        class="text-primary text-decoration-none"><?= $crow['total_students'] ?></a>
                                                </td>
                                                <td class="text-success fw-bold">
                                                    <a href="?tab=controller&drill_down=1&class_id=<?= $crow['sno'] ?>&c_type=filled"
                                                        class="text-success text-decoration-none"><?= $crow['filled_forms'] ?></a>
                                                </td>
                                                <td class="text-danger fw-bold">
                                                    <a href="?tab=controller&drill_down=1&class_id=<?= $crow['sno'] ?>&c_type=pending"
                                                        class="text-danger text-decoration-none"><?= $pending ?></a>
                                                </td>
                                                <td>
                                                    <form method="POST" style="margin:0">
                                                        <input type="hidden" name="class_id" value="<?= $crow['sno'] ?>">
                                                        <input type="hidden" name="field" value="exam_form">
                                                        <input type="hidden" name="new_val"
                                                            value="<?= $crow['exam_form'] == 1 ? 0 : 1 ?>">
                                                        <button type="submit" name="toggle_class_form"
                                                            class="btn btn-sm <?= $crow['exam_form'] == 1 ? 'btn-danger' : 'btn-success' ?>">
                                                            <?= $crow['exam_form'] == 1 ? 'Disable' : 'Enable' ?>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td>
                                                    <form method="POST" style="margin:0">
                                                        <input type="hidden" name="class_id" value="<?= $crow['sno'] ?>">
                                                        <input type="hidden" name="field" value="show_back">
                                                        <input type="hidden" name="new_val"
                                                            value="<?= $crow['show_back'] == 1 ? 0 : 1 ?>">
                                                        <button type="submit" name="toggle_class_form"
                                                            class="btn btn-sm <?= $crow['show_back'] == 1 ? 'btn-danger' : 'btn-success' ?>">
                                                            <?= $crow['show_back'] == 1 ? 'Disable' : 'Enable' ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if (isset($_GET['drill_down']) && $class_info): ?>
                        <div class="drill-down-container mt-4 animate-fade-in">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb mb-0 bg-light p-2 rounded border">
                                        <li class="breadcrumb-item"><a
                                                href="?tab=controller&controller_search=1&exam_type=<?= ($_GET['exam_type'] ?? '') ?>&c_class=<?= ($_GET['c_class'] ?? '') ?>">Summary</a>
                                        </li>
                                        <li class="breadcrumb-item <?= (!isset($_GET['v_status'])) ? 'active' : '' ?>">
                                            <?php if (isset($_GET['v_status'])): ?>
                                                <a
                                                    href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>">
                                                    <?= ucfirst($c_type) ?> (<?= $class_info['class_description'] ?>)
                                                </a>
                                            <?php else: ?>
                                                <?= ucfirst($c_type) ?> (<?= $class_info['class_description'] ?>)
                                            <?php endif; ?>
                                        </li>
                                        <?php if (isset($_GET['v_status'])): ?>
                                            <li class="breadcrumb-item <?= (!isset($_GET['a_status'])) ? 'active' : '' ?>">
                                                <?php if (isset($_GET['a_status'])): ?>
                                                    <a
                                                        href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>&v_status=<?= $_GET['v_status'] ?>">
                                                        <?= $_GET['v_status'] == '1' ? 'Verified' : 'Not Verified' ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= $_GET['v_status'] == '1' ? 'Verified' : 'Not Verified' ?>
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                        <?php if (isset($_GET['a_status'])): ?>
                                            <li class="breadcrumb-item active">
                                                <?= $_GET['a_status'] == '1' ? 'Downloaded' : 'Not Downloaded' ?>
                                            </li>
                                        <?php endif; ?>
                                    </ol>
                                </nav>
                                <button
                                    onclick="exportToExcel('drillTable', '<?= str_replace(' ', '_', $class_info['class_description']) ?>_<?= $c_type ?>')"
                                    class="btn btn-success shadow-sm">
                                    <i class="fa fa-file-excel me-1"></i> Download to Excel
                                </button>
                            </div>

                            <!-- Level Options -->
                            <div class="row g-3 mb-4">
                                <?php if (!isset($_GET['v_status'])): ?>
                                    <div class="col-md-6">
                                        <a href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>&v_status=1"
                                            class="card drill-card h-100 border-start border-4 border-success">
                                            <div class="card-body d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span class="label">Verified Students</span>
                                                    <span
                                                        class="count text-success"><?= $drill_counts['verified_count'] ?></span>
                                                </div>
                                                <!-- <i class="fa fa-check-circle fa-2x text-success opacity-50"></i> -->
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>&v_status=0"
                                            class="card drill-card h-100 border-start border-4 border-warning">
                                            <div class="card-body d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span class="label">Not Verified Students</span>
                                                    <span
                                                        class="count text-warning"><?= $drill_counts['not_verified_count'] ?></span>
                                                </div>
                                                <!-- <i class="fa fa-clock fa-2x text-warning opacity-50"></i> -->
                                            </div>
                                        </a>
                                    </div>
                                <?php elseif ($_GET['v_status'] == '1' && !isset($_GET['a_status'])): ?>
                                    <div class="col-md-6">
                                        <a href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>&v_status=1&a_status=1"
                                            class="card drill-card h-100 border-start border-4 border-info">
                                            <div class="card-body d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span class="label">Admit Card Downloaded</span>
                                                    <span
                                                        class="count text-info"><?= $drill_counts['admit_downloaded_count'] ?></span>
                                                </div>
                                                <i class="fa fa-download fa-2x text-info opacity-50"></i>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="?tab=controller&drill_down=1&class_id=<?= $c_id ?>&c_type=<?= $c_type ?>&v_status=1&a_status=0"
                                            class="card drill-card h-100 border-start border-4 border-secondary">
                                            <div class="card-body d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span class="label">Not Downloaded</span>
                                                    <span
                                                        class="count text-secondary"><?= $drill_counts['admit_not_downloaded_count'] ?></span>
                                                </div>
                                                <i class="fa fa-times-circle fa-2x text-secondary opacity-50"></i>
                                            </div>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Student List Table -->
                            <div class="table-responsive bg-white p-3 rounded shadow-sm border">
                                <table class="table table-bordered table-hover text-center align-middle" id="drillTable">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>S.No.</th>
                                            <th>Student Name</th>
                                            <th>Father Name</th>
                                            <th>Mobile</th>
                                            <th>Roll No</th>
                                            <th>Exam Form No</th>
                                            <th>UIN No</th>
                                            <th>Verification</th>
                                            <th>Verified By</th>
                                            <th>Verified Time</th>
                                            <th>Admit Card</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($drill_results && mysqli_num_rows($drill_results) > 0):
                                            $di = 1;
                                            while ($drow = mysqli_fetch_assoc($drill_results)): ?>
                                                <tr>
                                                    <td><?= $di++ ?></td>
                                                    <td class="text-start"><?= htmlspecialchars($drow['student_name']) ?></td>
                                                    <td class="text-start"><?= htmlspecialchars($drow['father_name'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($drow['mobile_no'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($drow['exam_roll_no'] ?? $drow['college_roll_no'] ?? '-') ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($drow['exam_form_no'] ?? '-') ?></td>
                                                    <td><?= htmlspecialchars($drow['uin'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if ($drow['verify_status'] == 1): ?>
                                                            <span class="badge bg-success">Verified</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($drow['verified_by_name'] ?? '-') ?></td>
                                                    <td><?= !empty($drow['verify_time']) ? date('d-M-Y H:i', strtotime($drow['verify_time'])) : '-' ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($drow['admit_download'] == 1): ?>
                                                            <span class="badge bg-info">Downloaded</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Not Downloaded</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="9">No records found matching criteria.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Verify Modal -->
<div class="modal fade" id="verifyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Verify Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="verify_exam_form_no" id="verify_exam_form_no">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Exam Fees <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="list_exam_fees" class="form-control" required
                            placeholder="Enter amount">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="list_verify" class="btn btn-success px-4">Verify</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function switchTab(tabName) {
        // Update URL without refreshing
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.pushState({}, '', url);

        // Hide all panes
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
        // Deactivate all buttons
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

        // Show selected pane
        document.getElementById(tabName + 'Pane').classList.add('active');
        // Activate selected button
        document.getElementById(tabName + 'Tab').classList.add('active');
    }

    function toggleSelectAll(source) {
        checkboxes = document.getElementsByName('selected_forms[]');
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function toggleSelectAllController(source) {
        checkboxes = document.getElementsByName('class_ids[]');
        for (var i = 0, n = checkboxes.length; i < n; i++) {
            checkboxes[i].checked = source.checked;
        }
    }

    function openVerifyModal(formNo) {
        document.getElementById('verify_exam_form_no').value = formNo;
        var myModal = new bootstrap.Modal(document.getElementById('verifyModal'));
        myModal.show();
    }

    setTimeout(function () {
        var msg = document.getElementById("formStatusMsg");
        if (msg) {
            msg.style.transition = "opacity 0.5s ease";
            msg.style.opacity = "0";

            setTimeout(() => {
                msg.style.display = "none";
            }, 500);
        }
    }, 5000);

    function exportToExcel(tableId, filename) {
        let table = document.getElementById(tableId);
        let rows = table.querySelectorAll('tr');
        let csv = [];

        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll('td, th');
            for (let j = 0; j < cols.length; j++) {
                // Clean text from badges or buttons if necessary
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").replace(/,/g, ";");
                row.push(data);
            }
            csv.push(row.join(","));
        }

        let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        let encodedUri = encodeURI(csvContent);
        let link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php if (!empty($form_filled_popup)) { ?>
    <script>
        alert("Form already filled!");
    </script>
<?php } ?>

<?php
if (function_exists('page_footer'))
    page_footer();
?>