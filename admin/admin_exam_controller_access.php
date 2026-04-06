<?php
ob_start();
session_start();
require_once __DIR__ . '/script/settings.php';

// Global Database Connection $db is available from settings.php
// For enroll_no_function logic, it uses $mysqli, let's ensure $mysqli is defined or replace with $db
if (!isset($mysqli)) {
    $mysqli = $db;
}

// ---------------------------------------------------------
// 1. Logic for Result Access Control (from admin_result_access.php)
// ---------------------------------------------------------
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    if ($_POST['action'] === 'update_toggle') {
        $sno = (int) $_POST['sno'];
        $column = $_POST['column'];
        $value = (int) $_POST['value'];

        if (in_array($column, ['show_regular', 'show_back'])) {
            $stmt = $db->prepare("UPDATE class_detail SET $column = ? WHERE sno = ?");
            $stmt->bind_param("ii", $value, $sno);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => ucfirst(str_replace('show_', '', $column)) . ' status updated'];
            } else {
                $response = ['status' => 'error', 'message' => 'Database update failed'];
            }
            $stmt->close();
        }
    } elseif ($_POST['action'] === 'update_date') {
        $sno = (int) $_POST['sno'];
        $date = $_POST['date'];

        $stmt = $db->prepare("UPDATE class_detail SET result_declaration_date = ? WHERE sno = ?");
        $stmt->bind_param("si", $date, $sno);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Declaration date updated'];
        } else {
            $response = ['status' => 'error', 'message' => 'Database update failed'];
        }
        $stmt->close();
    } elseif ($_POST['action'] === 'get_courses_by_category') {
        $cat = $_POST['category'];
        if ($cat) {
            $stmt = $db->prepare("SELECT DISTINCT group_name FROM class_detail WHERE category = ? ORDER BY group_name ASC");
            $stmt->bind_param("s", $cat);
        } else {
            $stmt = $db->prepare("SELECT DISTINCT group_name FROM class_detail ORDER BY group_name ASC");
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $courses = [];
        while ($r = $res->fetch_assoc()) {
            $courses[] = $r['group_name'];
        }
        echo json_encode(['status' => 'success', 'data' => $courses]);
        exit;
    } elseif ($_POST['action'] === 'get_enrollment_stats') {
        $class_sno = (int) $_POST['class_sno'];

        // 1. Fetch Stats
        $query = "SELECT 
                    SUM(CASE WHEN (enroll_no IS NOT NULL AND enroll_no != '') THEN 1 ELSE 0 END) as gen,
                    SUM(CASE WHEN (enroll_no IS NULL OR enroll_no = '') THEN 1 ELSE 0 END) as not_gen
                  FROM student_info WHERE class = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $class_sno);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();

        // 2. Fetch Last Enrollment No
        $last_sql = "SELECT enroll_no FROM student_info WHERE class = ? AND enroll_no IS NOT NULL AND enroll_no != '' ORDER BY enroll_no DESC LIMIT 1";
        $stmt_last = $db->prepare($last_sql);
        $stmt_last->bind_param("i", $class_sno);
        $stmt_last->execute();
        $last_res = $stmt_last->get_result()->fetch_assoc();
        $last_val = $last_res ? $last_res['enroll_no'] : '';

        echo json_encode(['status' => 'success', 'stats' => $stats, 'last_enroll_no' => $last_val]);
        exit;
    } elseif ($_POST['action'] === 'get_enrollment_list') {
        $class_sno = (int) $_POST['class_sno'];
        $type = $_POST['type'];
        $where = ($type === 'gen') ? "(enroll_no IS NOT NULL AND enroll_no != '')" : "(enroll_no IS NULL OR enroll_no = '')";

        // Sort by enrollment no ascending per request
        $sql = "SELECT roll_no, stu_name, father_name, enroll_no FROM student_info WHERE class = ? AND $where ORDER BY enroll_no ASC, stu_name ASC";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $class_sno);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $list[] = $r;
        }
        echo json_encode(['status' => 'success', 'list' => $list]);
        exit;
    }

    echo json_encode($response);
    exit;
}

// Helper for Result Access Filter
$catResult = $db->query("SELECT DISTINCT category FROM class_detail WHERE category IS NOT NULL AND category != '' ORDER BY category");
$allResultCategories = [];
while ($c = $catResult->fetch_assoc()) {
    $allResultCategories[] = $c['category'];
}

$searchTerm = $_POST['search'] ?? '';
$courseType = $_POST['type'] ?? '';

$resCourses = [];
if ($searchTerm || $courseType) {
    $resSql = "SELECT sno, group_name, semester, year, category, show_regular, show_back, result_declaration_date FROM class_detail WHERE 1=1";
    $resParams = [];
    $resTypes = "";

    if ($searchTerm) {
        $resSql .= " AND group_name LIKE ?";
        $resParams[] = "%$searchTerm%";
        $resTypes .= "s";
    }
    if ($courseType) {
        $resSql .= " AND category = ?";
        $resParams[] = $courseType;
        $resTypes .= "s";
    }
    $resSql .= " ORDER BY group_name ASC, semester ASC";

    $stmt = $db->prepare($resSql);
    if (!empty($resParams)) {
        $stmt->bind_param($resTypes, ...$resParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resCourses[] = $row;
    }
    $stmt->close();
}

// Fetch course names for dropdown (filtered by selected category if any)
$courseListForDropdown = [];
if ($courseType) {
    $stmtCourse = $db->prepare("SELECT DISTINCT group_name FROM class_detail WHERE category = ? ORDER BY group_name ASC");
    $stmtCourse->bind_param("s", $courseType);
} else {
    $stmtCourse = $db->prepare("SELECT DISTINCT group_name FROM class_detail ORDER BY group_name ASC");
}
$stmtCourse->execute();
$resultCourses = $stmtCourse->get_result();
while ($row = $resultCourses->fetch_assoc()) {
    $courseListForDropdown[] = $row['group_name'];
}
$stmtCourse->close();

// ---------------------------------------------------------
// 3. Logic for Enrollment Generator (from enroll_no_function.php)
// ---------------------------------------------------------
$gen_message = '';
$gen_messageType = '';
if (isset($_POST['btn_generate_enroll'])) {
    $course_sno = $_POST['course_sno'];
    $start_num_str = $_POST['start_num'];
    $prefix = $_POST['prefix'];

    if (!$course_sno || $start_num_str === '') {
        $gen_message = "Please select a course and enter a starting number.";
        $gen_messageType = "danger";
    } else {
        $start_num = (int) $start_num_str;
        $padding_length = strlen($start_num_str);
        $gen_scope = $_POST['gen_scope'] ?? 'ALL';

        $where_scope = "";
        if ($gen_scope === 'GEN') {
            $where_scope = " AND (si.enroll_no IS NOT NULL AND si.enroll_no != '') ";
        } elseif ($gen_scope === 'NOT_GEN') {
            $where_scope = " AND (si.enroll_no IS NULL OR si.enroll_no = '') ";
        }

        // Modified to handle scope + join with student_info
        $stmt_gen = $db->prepare("
            SELECT esi.student_info_sno 
            FROM exam_student_info esi
            JOIN student_info si ON esi.student_info_sno = si.sno
            WHERE esi.course_name = ? AND esi.verify_status = 1 $where_scope
            ORDER BY si.roll_no ASC
        ");
        $stmt_gen->bind_param("i", $course_sno);
        $stmt_gen->execute();
        $res_gen = $stmt_gen->get_result();

        $gen_count = 0;
        $current_num = $start_num;

        while ($row_gen = $res_gen->fetch_assoc()) {
            $student_info_sno = $row_gen['student_info_sno'];
            $enroll_no = $prefix . str_pad($current_num, $padding_length, "0", STR_PAD_LEFT);

            $upd_stmt = $db->prepare("UPDATE student_info SET enroll_no = ? WHERE sno = ?");
            $upd_stmt->bind_param("si", $enroll_no, $student_info_sno);
            $upd_stmt->execute();
            $upd_stmt->close();

            $current_num++;
            $gen_count++;
        }
        $stmt_gen->close();

        if ($gen_count > 0) {
            $gen_message = "Successfully generated Enrollment Numbers for $gen_count students.";
            $gen_messageType = "success";
        } else {
            $gen_message = "No verified students found for the selected course.";
            $gen_messageType = "warning";
        }
    }
}

$gen_courses_res = $db->query("SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
$gen_courses = [];
while ($row = $gen_courses_res->fetch_assoc()) {
    $gen_courses[] = $row;
}

// Call Sidebar and Header
if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();
?>

<link rel="stylesheet" href="../cdn/css/bootstrap.min.css">
<style>
    /* 🔥 FORCING HEADER COLORS Broken by Bootstrap 🔥 */
    header.relative {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
        color: #fff !important;
    }

    header.relative a,
    header.relative span {
        color: #fff !important;
    }

    .erp-header {
        background-color: #1f3e8f !important;
    }

    .erp-header p,
    .erp-welcome p {
        color: white !important;
    }

    /* Fix: Remove extra underlines from links (sidebar & content) */
    a {
        text-decoration: none !important;
    }

    /* Standard Card-Box from add_class.php / admin_result_access.php */
    .card-box {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid #eee;

    }

    .card-heading {
        position: relative;
        font-size: 18px;
        font-weight: 700;
        color: #306ad3;
        margin-bottom: 25px;
        padding-left: 18px;
        display: flex;
        align-items: center;
    }

    .card-heading::before {
        content: '';
        position: absolute;
        left: 0;
        top: 4px;
        width: 4px;
        height: 20px;
        background: #306ad3;
        border-radius: 4px;
    }

    /* Ledger Styles (Specifically from student_ledger.php) */
    .filter-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-group label {
        font-size: 13px;
        font-weight: 700;
        color: #555;
        text-transform: uppercase;
    }

    .filter-group select,
    .filter-group input {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        background: #fff;
        color: #000;
    }

    .ledger-table thead {
        background: #1e88e5 !important;
        color: #fff !important;
    }

    .table thead th {
        background-color: #306ad3 !important;
        color: white !important;
        border: none !important;
        font-weight: 600;
        padding: 12px 15px;
        font-size: 0.9rem;
    }

    /* Switch Styling */
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 20px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #306ad3;
    }

    input:checked+.slider:before {
        transform: translateX(20px);
    }

    /* Tabs Styling */
    .nav-tabs {
        border-bottom: 2px solid #e2e8f0;
    }

    .nav-tabs .nav-link {
        color: #6c757d;
        font-weight: 600;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 20px;
    }

    .nav-tabs .nav-link.active {
        color: #306ad3;
        border: none;
        border-bottom: 3px solid #306ad3;
        background: transparent;
    }

    /* Status backgrounds for ledger rows */
    .row-regular td {
        background-color: #e8f5e9 !important;
    }

    .row-changed td {
        background-color: #fff9c4 !important;
    }

    /* Enrollment Stats Styling */
    .stat-card {
        padding: 15px;
        border-radius: 12px;
        text-align: center;
        transition: 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .stat-gen {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .stat-gen:hover {
        border-color: #2e7d32;
    }

    .stat-not-gen {
        background: #fff3e0;
        color: #ef6c00;
    }

    .stat-not-gen:hover {
        border-color: #ef6c00;
    }

    .stat-count {
        font-size: 24px;
        font-weight: 800;
        display: block;
    }

    .stat-label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<main class="p-4" x-data="{ }">
    <!-- Combined Tab Control Header -->
    <div class="card-box" style="padding: 15px 25px; margin-bottom: 20px;">
        <ul class="nav nav-tabs border-0" id="examTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="result-tab" data-bs-toggle="tab" data-bs-target="#result"
                    type="button" role="tab">Result Control</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="enroll-tab" data-bs-toggle="tab" data-bs-target="#enroll" type="button"
                    role="tab">Enrollment Generator</button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="examTabsContent">
        <!-- TAB 1: RESULT CONTROL (Matches admin_result_access.php) -->
        <div class="tab-pane fade show active" id="result" role="tabpanel">
            <div class="card-box">
                <div class="card-heading">Filter Courses</div>
                <form method="POST" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label font-weight-bold">Exam Category</label>
                        <select name="type" id="categoryFilter" class="form-select form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($allResultCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $courseType == $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label font-weight-bold">Course Name</label>
                        <select name="search" id="courseFilter" class="form-select form-control">
                            <option value="">All Courses</option>
                            <?php foreach ($courseListForDropdown as $course): ?>
                                <option value="<?php echo htmlspecialchars($course); ?>" <?php echo ($searchTerm == $course) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100"
                            style="background:#306ad3; border:none; padding:8px;">Search</button>
                    </div>
                    <div class="col-md-2">
                        <a href="admin_exam_controller_access.php" class="btn btn-light border w-100"
                            style="padding:8px;">Reset</a>
                    </div>
                </form>
            </div>

            <div class="card-box">
                <div class="card-heading">Result Control List</div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="5%">S.No</th>
                                <th>Course Details</th>
                                <th class="text-center">Regular</th>
                                <th class="text-center">Back</th>
                                <th>Declaration Date</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resCourses)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No courses found.</td>
                                </tr>
                            <?php else:
                                $ri = 1;
                                foreach ($resCourses as $course): ?>
                                    <tr id="row-<?php echo $course['sno']; ?>">
                                        <td class="text-muted"><?php echo $ri++; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($course['group_name']); ?></div>
                                        </td>
                                        <td class="text-center">
                                            <label class="switch">
                                                <input type="checkbox" class="status-toggle"
                                                    data-sno="<?php echo $course['sno']; ?>" data-column="show_regular" <?php echo $course['show_regular'] == 1 ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td class="text-center">
                                            <label class="switch">
                                                <input type="checkbox" class="status-toggle"
                                                    data-sno="<?php echo $course['sno']; ?>" data-column="show_back" <?php echo $course['show_back'] == 1 ? 'checked' : ''; ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm"
                                                id="date-<?php echo $course['sno']; ?>"
                                                value="<?php echo $course['result_declaration_date']; ?>" style="width: 140px;">
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary btn-save-date"
                                                data-sno="<?php echo $course['sno']; ?>"
                                                style="background:#306ad3; border:none;">
                                                <i class="fas fa-save me-1"></i> Save
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: ENROLLMENT GENERATOR (Adapted from enroll_no_function.php) -->
        <div class="tab-pane fade" id="enroll" role="tabpanel">
            <div class="row">
                <div class="col-md-12">
                    <div class="card-box">
                        <div class="card-heading">Enrollment Generator</div>

                        <?php if ($gen_message): ?>
                            <div class="alert alert-<?= $gen_messageType ?> alert-dismissible fade show">
                                <?= $gen_message ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="gen_scope" id="genScopeInput" value="ALL">

                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Select Course</label>
                                <select name="course_sno" id="genCourseSelect" class="form-select form-control"
                                    required>
                                    <option value="">Choose a class...</option>
                                    <?php foreach ($gen_courses as $gc): ?>
                                        <option value="<?= $gc['sno'] ?>"><?= htmlspecialchars($gc['class_description']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-7">
                                    <div class="mb-3">
                                        <label class="form-label font-weight-bold">Enrollment Prefix</label>
                                        <input type="text" name="prefix" id="enrollPrefixInput" class="form-control"
                                            value="RM25/" placeholder="e.g. RM25/">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="mb-4">
                                        <label class="form-label font-weight-bold">Start Sequence</label>
                                        <input type="text" name="start_num" id="enrollStartNumInput"
                                            class="form-control" placeholder="e.g. 001" required>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" name="btn_generate_enroll" id="btnGenEnroll"
                                class="btn btn-primary w-100"
                                style="background:#306ad3; border:none; padding:12px; font-weight:600;">
                                <i class="fas fa-magic me-2"></i> Generate enrollment No.
                            </button>

                            <div id="genScopeAlert" class="mt-2 text-center small text-muted d-none">
                                <!-- <i class="fas fa-info-circle me-1"></i> Running for: <span id="genScopeLabel" class="fw-bold">ALL STUDENTS</span> -->
                            </div>

                            <!-- Enrollment Stats Row (Appears when course is selected) -->
                            <div id="enrollStatsRow" class="row g-3 mt-3 d-none">
                                <div class="col-md-6">
                                    <div class="stat-card stat-gen border" data-type="gen"
                                        title="Click to view & target this list">
                                        <span class="stat-count" id="countGen">0</span>
                                        <span class="stat-label">Enrollment Generated</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-card stat-not-gen border" data-type="not_gen"
                                        title="Click to view & target this list">
                                        <span class="stat-count" id="countNotGen">0</span>
                                        <span class="stat-label">Pending Generation</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Enrollment Details List (Standardized Style) -->
                    <div id="enrollDetailCard" class="card-box mt-4 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <div class="card-heading m-0" id="detailHeading"
                                style="padding-bottom:0px; margin-bottom:0px !important;">GENERATED ENROLLMENT REPORT
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" style="font-weight: 600;"
                                    onclick="window.print()">
                                    <!-- <i class="fas fa-print me-1"></i> Print -->
                                </button>
                                <button type="button" class="btn btn-sm btn-light border p-1"
                                    onclick="$('#enrollDetailCard').addClass('d-none')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-heading after main heading line -->
                        <div class="mb-4">
                            <span class="text-muted fw-bold small text-uppercase letter-spacing-1">Report for:</span>
                            <span id="reportClassName" class="fw-bold text-primary ms-1">...</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle border mb-0"
                                style="border-collapse: separate; border-spacing: 0;">
                                <thead style="background: #2196F3; color: #fff;">
                                    <tr>
                                        <th width="8%" class="text-center p-3 text-uppercase fw-bold small">S.No.</th>
                                        <th width="15%" class="p-3 text-uppercase fw-bold small">Roll No</th>
                                        <th width="35%" class="p-3 text-uppercase fw-bold small">Student Name</th>
                                        <th width="27%" class="p-3 text-uppercase fw-bold small">Father's Name</th>
                                        <th width="15%" class="text-center p-3 text-uppercase fw-bold small">Enrollment
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="enrollDetailBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Unified Toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
    <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        // Handle Tab Persistence
        let activeTab = window.location.hash;
        if (activeTab) {
            $('.nav-link[data-bs-target="' + activeTab + '"]').tab('show');
        }
        $('.nav-link').on('shown.bs.tab', function (e) {
            window.location.hash = $(e.target).attr('data-bs-target');
        });

        // AJAX for dependent dropdown in Result Control
        $('#categoryFilter').on('change', function () {
            const category = $(this).val();
            const courseSelect = $('#courseFilter');
            courseSelect.html('<option value="">Loading...</option>');

            $.ajax({
                url: 'admin_exam_controller_access.php',
                method: 'POST',
                data: { action: 'get_courses_by_category', category: category },
                success: function (resp) {
                    if (resp.status === 'success') {
                        let options = '<option value="">All Courses</option>';
                        resp.data.forEach(function (course) {
                            options += `<option value="${course}">${course}</option>`;
                        });
                        courseSelect.html(options);
                    }
                }
            });
        });

        // Toast Helper
        let toastEl = document.getElementById('liveToast');
        let toastInstance = new bootstrap.Toast(toastEl);
        function showMessage(msg, isError = false) {
            $('#toastMessage').text(msg);
            $('#liveToast').removeClass('bg-success bg-danger').addClass(isError ? 'bg-danger' : 'bg-success');
            toastInstance.show();
        }

        // AJAX for Result Control
        $('.status-toggle').on('change', function () {
            const sno = $(this).data('sno');
            const column = $(this).data('column');
            const value = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: 'admin_exam_controller_access.php',
                method: 'POST',
                data: { action: 'update_toggle', sno: sno, column: column, value: value },
                success: function (resp) {
                    if (resp.status === 'success') showMessage(resp.message);
                    else showMessage(resp.message, true);
                },
                error: function () { showMessage('Connection Error', true); }
            });
        });

        $('.btn-save-date').on('click', function () {
            const sno = $(this).data('sno');
            const date = $('#date-' + sno).val();
            $.ajax({
                url: 'admin_exam_controller_access.php',
                method: 'POST',
                data: { action: 'update_date', sno: sno, date: date },
                success: function (resp) {
                    if (resp.status === 'success') showMessage(resp.message);
                    else showMessage(resp.message, true);
                },
                error: function () { showMessage('Connection Error', true); }
            });
        });

        // ==========================================
        // ENROLLMENT GENERATOR ENHANCEMENTS
        // ==========================================
        let lastEnrollNo = "";

        // Helper to parse enrollment number RM25/089 -> {prefix: 'RM25/', num: '090'}
        function suggestNextEnrollNo(last) {
            if (!last) return { prefix: 'RM25/', num: '001' };
            // Find the last numeric part
            let match = last.match(/^(.*?)(\d+)$/);
            if (match) {
                let prefix = match[1];
                let numPart = match[2];
                let nextVal = parseInt(numPart) + 1;
                // Preserve leading zeros
                let nextStr = nextVal.toString().padStart(numPart.length, '0');
                return { prefix: prefix, num: nextStr };
            }
            return { prefix: last + '/', num: '001' };
        }

        // Fetch stats when course changes
        $('#genCourseSelect').on('change', function () {
            const classSno = $(this).val();
            const statsRow = $('#enrollStatsRow');
            const detailCard = $('#enrollDetailCard');

            // Reset scope on change
            $('#genScopeInput').val('ALL');
            $('#genScopeAlert').addClass('d-none');

            if (!classSno) {
                statsRow.addClass('d-none');
                detailCard.addClass('d-none');
                return;
            }

            $.ajax({
                url: 'admin_exam_controller_access.php',
                method: 'POST',
                data: { action: 'get_enrollment_stats', class_sno: classSno },
                success: function (resp) {
                    if (resp.status === 'success') {
                        $('#countGen').text(resp.stats.gen || 0);
                        $('#countNotGen').text(resp.stats.not_gen || 0);
                        lastEnrollNo = resp.last_enroll_no; // Store for later use
                        $('#reportClassName').text($('#genCourseSelect option:selected').text());
                        statsRow.removeClass('d-none');
                        detailCard.addClass('d-none');
                    }
                }
            });
        });

        // Click on stat cards to see details AND set generation scope
        $('.stat-card').on('click', function () {
            const type = $(this).data('type'); // 'gen' or 'not_gen'
            const classSno = $('#genCourseSelect').val();
            const detailCard = $('#enrollDetailCard');
            const detailBody = $('#enrollDetailBody');

            // Set Scope & UI Feedback
            if (type === 'gen') {
                $('#genScopeInput').val('GEN');
                $('#genScopeLabel').text('ONLY GENERATED RECORDS (RE-SYNC)');
            } else {
                $('#genScopeInput').val('NOT_GEN');
                $('#genScopeLabel').text('ONLY PENDING RECORDS');

                // Smart Suggestion for Pending
                let suggested = suggestNextEnrollNo(lastEnrollNo);
                $('#enrollPrefixInput').val(suggested.prefix);
                $('#enrollStartNumInput').val(suggested.num);
            }
            $('#genScopeAlert').removeClass('d-none');
            $('.stat-card').removeClass('border-primary shadow-sm').css('background-image', 'none');
            $(this).addClass('border-primary shadow-sm');


            const heading = (type === 'gen') ? 'Generated Enrollment Report' : 'Pending Enrollment Report';

            $('#detailHeading').text(heading);
            detailBody.html('<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Building Report...</td></tr>');
            detailCard.removeClass('d-none');

            // Scroll to detail card
            $('html, body').animate({
                scrollTop: detailCard.offset().top - 20
            }, 600);

            $.ajax({
                url: 'admin_exam_controller_access.php',
                method: 'POST',
                data: { action: 'get_enrollment_list', class_sno: classSno, type: type },
                success: function (resp) {
                    if (resp.status === 'success') {
                        let rows = '';
                        if (resp.list.length === 0) {
                            rows = '<tr><td colspan="5" class="text-center py-4 text-muted">No students found in this category.</td></tr>';
                        } else {
                            resp.list.forEach(function (s, index) {
                                rows += `<tr style="background-color: #f1f8e9;">
                                    <td class="text-center text-muted p-2 border-bottom">${index + 1}</td>
                                    <td class="p-2 border-bottom fw-bold text-dark">${s.roll_no || '-'}</td>
                                    <td class="p-2 border-bottom fw-bold text-dark text-uppercase">${s.stu_name}</td>
                                    <td class="p-2 border-bottom text-muted">${s.father_name}</td>
                                    <td class="text-center p-2 border-bottom"><span class="badge ${s.enroll_no ? 'bg-success' : 'bg-warning'} px-3">${s.enroll_no || 'PENDING'}</span></td>
                                </tr>`;
                            });
                        }
                        detailBody.html(rows);
                    }
                }
            });
        });
    });
</script>

</script>

<?php if (function_exists('page_footer'))
    page_footer(); ?>
</body>

</html>