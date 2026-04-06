<?php
ob_start();
session_start();

/* ==============================
   DATABASE & SETTINGS
================================ */
include("script/settings.php"); // $db connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = '';

// Handle AJAX Status Toggle
if (isset($_POST['toggle_status'])) {
    $sno = $_POST['sno'];
    $new_status = $_POST['status'];
    $sql = "UPDATE add_subject SET is_active='$new_status' WHERE sno='$sno'";
    if (mysqli_query($db, $sql)) {
        echo 'success';
    } else {
        echo 'error';
    }
    exit;
}

if (isset($_POST['save'])) {

    $subject = mysqli_real_escape_string($db, $_POST['subject']);
    $subject_type = mysqli_real_escape_string($db, $_POST['subject_type']);
    $order_no = mysqli_real_escape_string($db, $_POST['order_no']);

    if (isset($_POST['sno']) && $_POST['sno'] != '') {
        // UPDATE
        $sql = "UPDATE add_subject SET 
                    subject='$subject',
                    subject_type='$subject_type',
                    order_no='$order_no'
                WHERE sno='" . $_POST['sno'] . "'";
        
        mysqli_query($db, $sql);

        if (mysqli_errno($db)) {
            $msg = '<div class="alert alert-danger">Updation failed: ' . mysqli_error($db) . '</div>';
        } else {
            // Redirect to clear query params and avoid resubmission
            header("Location: add_suubject.php");
            exit;
        }

    } else {
        // INSERT
        $sql = "INSERT INTO add_subject 
                    (subject, subject_type, order_no, is_active) 
                VALUES (
                    '$subject',
                    '$subject_type',
                    '$order_no',
                    '1'
                )";

        mysqli_query($db, $sql);

        if (mysqli_errno($db)) {
            $msg = '<div class="alert alert-danger">Insertion failed: ' . mysqli_error($db) . '</div>';
        } else {
            header("Location: add_suubject.php");
            exit;
        }
    }
}

// ========================================
// PAPER MANAGEMENT HANDLERS
// ========================================

// Handle AJAX request to fetch papers by class
if (isset($_POST['fetch_papers'])) {
    $class_id = $_POST['class_id'];
    $sql = "SELECT p.*, c.class_description, s.subject 
            FROM add_subject_papers p
            LEFT JOIN class_detail c ON p.class_id = c.sno
            LEFT JOIN add_subject s ON p.subject_id = s.sno
            WHERE p.class_id = '$class_id'
            ORDER BY p.sno DESC";
    $qry = mysqli_query($db, $sql);
    $papers = [];
    while ($row = mysqli_fetch_assoc($qry)) {
        $papers[] = $row;
    }
    echo json_encode($papers);
    exit;
}

// Handle Paper Save (INSERT/UPDATE)
if (isset($_POST['save_paper'])) {
    $paper_sno = mysqli_real_escape_string($db, $_POST['paper_sno'] ?? '');
    $class_id = mysqli_real_escape_string($db, $_POST['class_id']);
    $subject_id = mysqli_real_escape_string($db, $_POST['subject_id']);
    $paper_code = mysqli_real_escape_string($db, $_POST['paper_code']);
    $paper_title = mysqli_real_escape_string($db, $_POST['paper_title']);
    $theory_practical = mysqli_real_escape_string($db, $_POST['theory_practical']);
    $academic_credit = mysqli_real_escape_string($db, $_POST['academic_credit']);
    $class_type = mysqli_real_escape_string($db, $_POST['class_type']);
    $paper_type = mysqli_real_escape_string($db, $_POST['paper_type']);
    $has_theory = isset($_POST['has_theory']) ? 1 : 0;
    $has_internal = isset($_POST['has_internal']) ? 1 : 0;
    $has_practical = isset($_POST['has_practical']) ? 1 : 0;
    $max_marks = mysqli_real_escape_string($db, $_POST['max_marks'] ?? '100');
    $mid_sem_max_marks = mysqli_real_escape_string($db, $_POST['mid_sem_max_marks'] ?? '15');
    $practical_max_marks = mysqli_real_escape_string($db, $_POST['practical_max_marks'] ?? '0');

    if ($paper_sno != '') {
        // UPDATE
        $sql = "UPDATE add_subject_papers SET 
                    class_id='$class_id',
                    subject_id='$subject_id',
                    paper_code='$paper_code',
                    paper_title='$paper_title',
                    theory_practical='$theory_practical',
                    academic_credit='$academic_credit',
                    class_type='$class_type',
                    paper_type='$paper_type',
                    has_theory='$has_theory',
                    has_internal='$has_internal',
                    has_practical='$has_practical',
                    max_marks='$max_marks',
                    mid_sem_max_marks='$mid_sem_max_marks',
                    practical_max_marks='$practical_max_marks'
                WHERE sno='$paper_sno'";
    } else {
        // INSERT
        $sql = "INSERT INTO add_subject_papers 
                    (class_id, subject_id, paper_code, paper_title, theory_practical, academic_credit, class_type, paper_type, has_theory, has_internal, has_practical, max_marks, mid_sem_max_marks, practical_max_marks) 
                VALUES 
                    ('$class_id', '$subject_id', '$paper_code', '$paper_title', '$theory_practical', '$academic_credit', '$class_type', '$paper_type', '$has_theory', '$has_internal', '$has_practical', '$max_marks', '$mid_sem_max_marks', '$practical_max_marks')";
    }

    mysqli_query($db, $sql);

    if (mysqli_errno($db)) {
        $msg = '<div class="alert alert-danger">Paper save failed: ' . mysqli_error($db) . '</div>';
    } else {
        header("Location: add_suubject.php?tab=papers&class_filter=$class_id");
        exit;
    }
}

// Handle Paper Delete
if (isset($_GET['delete_paper'])) {
    $paper_id = $_GET['delete_paper'];
    $class_filter = $_GET['class_filter'] ?? '';
    mysqli_query($db, "DELETE FROM add_subject_papers WHERE sno='$paper_id'");
    header("Location: add_suubject.php?tab=papers&class_filter=$class_filter");
    exit;
}

// Handle Paper Edit - Fetch paper data
$editPaperData = [];
if (isset($_GET['edit_paper'])) {
    $sql = 'SELECT * FROM add_subject_papers WHERE sno=' . $_GET['edit_paper'];
    $qry = mysqli_query($db, $sql);
    $editPaperData = mysqli_fetch_assoc($qry);
}

// Ensure we don't handle ?del or ?toggle via GET anymore since we want AJAX/No Delete
// But keeping GET 'edit' to populate the form

$editData = [];
if (isset($_GET['edit'])) {
    $sql = 'SELECT * FROM add_subject WHERE sno=' . $_GET['edit'];
    $qry = mysqli_query($db, $sql);
    $editData = mysqli_fetch_assoc($qry);
}

/* ==============================
   OPTIONAL LAYOUT FUNCTIONS
================================ */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Subject Management</title>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<style>
/* ===== CORE STYLES ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f6;
}

/* ===== CARD ===== */
.card-box{
    background:#fff;
    border-radius:12px;
    padding:25px;
    margin-bottom:30px;
    box-shadow:0 6px 20px rgba(0,0,0,0.08);
}

/* ===== HEADING ===== */
.card-heading{
    position:relative;
    font-size:20px;
    font-weight:700;
    color:#0d6efd;
    margin-bottom:20px;
    padding-left:18px;
}
.card-heading::before{
    content:'';
    position:absolute;
    left:0;
    top:4px;
    width:5px;
    height:90%;
    background:#0d6efd;
    border-radius:4px;
}

/* ===== FORM ===== */
.form-row{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:18px;
    align-items: flex-end; /* Align inputs and button */
}
.form-group{
    flex:1;
    min-width:200px;
    display:flex;
    flex-direction:column;
}
.form-group label{
    font-weight:600;
    margin-bottom:6px;
    font-size:14px;
    color:#555;
}
.form-group input, .form-group select{
    padding:10px 14px;
    font-size:14px;
    border:1px solid #ccc;
    border-radius:8px;
    transition:0.3s;
    width: 100%;
    box-sizing: border-box;
}
.form-group input:focus, .form-group select:focus{
    border-color:#0d6efd;
    box-shadow:0 0 5px rgba(13,110,253,0.3);
    outline:none;
}

/* ===== BUTTONS ===== */
.save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:10px 32px;
    font-size:15px;
    font-weight:600;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
    height: 42px; /* Match input height roughly */
}
.save-btn:hover{
    background:#084298;
}

.btn{
    padding:6px 12px;
    font-size:13px;
    border-radius:6px;
    color:#fff;
    text-decoration:none;
    transition:0.3s;
    display: inline-block;
    cursor: pointer;
    border: none;
}
.btn-edit{
    background:#0d6efd;
}
.btn-edit:hover{
    background:#084298;
}
.btn-status {
    min-width: 80px;
    text-align: center;
}
.status-enabled {
    background: #198754; /* Green */
}
.status-disabled {
    background: #dc3545; /* Red */
}
.btn-delete {
    background: #dc3545;
}
.btn-delete:hover {
    background: #a71d2a;
}

/* ===== TABLE ===== */
table.dataTable thead th {
    background-color: #0d6efd;
    color: white;
}

/* ===== UTILS ===== */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}
.alert-danger {
    color: #a94442;
    background-color: #f2dede;
    border-color: #ebccd1;
}

/* ===== TAB NAVIGATION ===== */
.tab-container {
    background: #fff;
    border-radius: 12px;
    padding: 0;
    margin-bottom: 30px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
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
</style>
</head>

<body>

<div style="padding: 20px;">

    <?= $msg ?>

    <!-- ================= TAB NAVIGATION ================= -->
    <div class="tab-container">
        <div class="tab-navigation">
            <button class="tab-button active" onclick="switchTab('subjects')" id="subjectsTab">
                📚 Subject Management
            </button>
            <button class="tab-button" onclick="switchTab('papers')" id="papersTab">
                📄 Paper Management
            </button>
        </div>

        <div class="tab-content">
            <!-- ================= SUBJECTS TAB ================= -->
            <div id="subjectsPane" class="tab-pane active">
                <!-- SUBJECT FORM -->
                <div class="card-box" style="box-shadow: none; padding: 0; margin-bottom: 25px;">
                    <div class="card-heading"><?= isset($editData['sno']) ? 'Edit Subject' : 'Add Subject' ?></div>
                    
                    <form method="post" action="add_suubject.php">
                        <input type="hidden" name="sno" value="<?= $editData['sno'] ?? '' ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Subject Name</label>
                                <input type="text" name="subject" placeholder="Enter Subject Name" value="<?= $editData['subject'] ?? '' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Subject Type</label>
                                <select name="subject_type" required>
                                    <option value="">Select Type</option>
                                    <?php
                                    $typeQry = mysqli_query($db, "SELECT * FROM subject_type ORDER BY sno ASC");
                                    while ($typeRow = mysqli_fetch_assoc($typeQry)) {
                                        $selected = (isset($editData['subject_type']) && $editData['subject_type'] == $typeRow['sno']) ? 'selected' : '';
                                        echo '<option value="' . $typeRow['sno'] . '" ' . $selected . '>' . $typeRow['type'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Order No</label>
                                <input type="number" name="order_no" placeholder="Order" value="<?= $editData['order_no'] ?? '' ?>" required>
                            </div>

                            <div class="form-group" style="flex: 0;">
                                <label>&nbsp;</label>
                                <button type="submit" name="save" class="save-btn">
                                    <?= isset($editData['sno']) ? 'Update' : 'Save' ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SUBJECTS LIST -->
                <div class="card-box" style="box-shadow: none; padding: 0;">
                    <div class="card-heading">Subjects List</div>

                    <table id="subjectsTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $qry = mysqli_query($db, "SELECT asub.*, st.type as type_name 
                                                     FROM add_subject asub 
                                                     LEFT JOIN subject_type st ON asub.subject_type = st.sno 
                                                     ORDER BY asub.order_no ASC");
                            while ($row = mysqli_fetch_assoc($qry)) {
                                $statusClass = ($row['is_active'] == 1) ? 'status-enabled' : 'status-disabled';
                                $statusText = ($row['is_active'] == 1) ? 'Enabled' : 'Disabled';
                                $toggleTo = ($row['is_active'] == 1) ? 0 : 1;
                            ?>
                                <tr>
                                    <td><?= $row['sno']; ?></td>
                                    <td><?= $row['subject']; ?></td>
                                    <td><?= $row['type_name'] ?? 'N/A'; ?></td>
                                    <td><?= $row['order_no']; ?></td>
                                    <td>
                                        <button class="btn btn-status <?= $statusClass ?>" 
                                                onclick="toggleStatus(<?= $row['sno'] ?>, <?= $toggleTo ?>, this)">
                                            <?= $statusText ?>
                                        </button>
                                    </td>
                                    <td>
                                        <a class="btn btn-edit" href="?edit=<?= $row['sno']; ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= PAPERS TAB ================= -->
            <div id="papersPane" class="tab-pane">
                <!-- CLASS FILTER -->
                <div style="margin-bottom: 25px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <label style="font-weight: 600; margin-right: 10px; font-size: 15px;">🎓 Select Class:</label>
                    <select id="classFilter" style="padding: 10px 16px; font-size: 14px; border: 2px solid #dee2e6; border-radius: 8px; min-width: 350px; background: white;">
                        <option value="">-- Select Class to Manage Papers --</option>
                        <?php
                        $classQry = mysqli_query($db, "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
                        while ($classRow = mysqli_fetch_assoc($classQry)) {
                            $selected = (isset($_GET['class_filter']) && $_GET['class_filter'] == $classRow['sno']) ? 'selected' : '';
                            echo '<option value="' . $classRow['sno'] . '" ' . $selected . '>' . $classRow['class_description'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <!-- PAPER FORM -->
                <div id="paperFormSection" style="display: <?= isset($_GET['class_filter']) || isset($editPaperData['sno']) ? 'block' : 'none' ?>;">
                    <div class="card-box" style="box-shadow: none; padding: 0; margin-bottom: 25px;">
                        <div class="card-heading"><?= isset($editPaperData['sno']) ? '✏️ Edit Paper' : '➕ Add New Paper' ?></div>
                        
                        <form method="post" action="add_suubject.php">
                            <input type="hidden" name="paper_sno" value="<?= $editPaperData['sno'] ?? '' ?>">
                            <input type="hidden" name="class_filter" value="<?= $_GET['class_filter'] ?? $editPaperData['class_id'] ?? '' ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Class</label>
                                    <select name="class_id" required style="padding: 10px 14px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px;">
                                        <option value="">Select Class</option>
                                        <?php
                                        $classQry2 = mysqli_query($db, "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
                                        while ($classRow2 = mysqli_fetch_assoc($classQry2)) {
                                            $selected = '';
                                            if (isset($editPaperData['class_id']) && $editPaperData['class_id'] == $classRow2['sno']) {
                                                $selected = 'selected';
                                            } elseif (isset($_GET['class_filter']) && $_GET['class_filter'] == $classRow2['sno']) {
                                                $selected = 'selected';
                                            }
                                            echo '<option value="' . $classRow2['sno'] . '" ' . $selected . '>' . $classRow2['class_description'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Subject</label>
                                    <select name="subject_id" style="padding: 10px 14px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px;">
                                        <option value="">Select Subject</option>
                                        <?php
                                        $subjectQry = mysqli_query($db, "SELECT sno, subject FROM add_subject WHERE is_active='1' ORDER BY subject ASC");
                                        while ($subjectRow = mysqli_fetch_assoc($subjectQry)) {
                                            $selected = (isset($editPaperData['subject_id']) && $editPaperData['subject_id'] == $subjectRow['sno']) ? 'selected' : '';
                                            echo '<option value="' . $subjectRow['sno'] . '" ' . $selected . '>' . $subjectRow['subject'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Paper Code</label>
                                    <input type="text" name="paper_code" placeholder="e.g., CS101" value="<?= $editPaperData['paper_code'] ?? '' ?>" required>
                                </div>

                                <div class="form-group">
                                    <label>Paper Title</label>
                                    <input type="text" name="paper_title" placeholder="e.g., Introduction to CS" value="<?= $editPaperData['paper_title'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Theory/Practical</label>
                                    <select name="theory_practical" required style="padding: 10px 14px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px;">
                                        <option value="">Select Type</option>
                                        <option value="Theory" <?= (isset($editPaperData['theory_practical']) && strtoupper($editPaperData['theory_practical']) == 'THEORY') ? 'selected' : '' ?>>Theory</option>
                                        <option value="Practical" <?= (isset($editPaperData['theory_practical']) && strtoupper($editPaperData['theory_practical']) == 'PRACTICAL') ? 'selected' : '' ?>>Practical</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Academic Credit (Optional)</label>
                                    <input type="number" name="academic_credit" placeholder="e.g., 4" value="<?= $editPaperData['academic_credit'] ?? '' ?>">
                                </div>

                                <div class="form-group">
                                    <label>Class Type</label>
                                    <select name="class_type" style="padding: 10px 14px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px;">
                                        <option value="">Select Class Type</option>
                                        <option value="UG" <?= (isset($editPaperData['class_type']) && strtoupper($editPaperData['class_type']) == 'UG') ? 'selected' : '' ?>>UG</option>
                                        <option value="PG" <?= (isset($editPaperData['class_type']) && strtoupper($editPaperData['class_type']) == 'PG') ? 'selected' : '' ?>>PG</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Paper Type</label>
                                    <select name="paper_type" style="padding: 10px 14px; font-size: 14px; border: 1px solid #ccc; border-radius: 8px;">
                                        <option value="">Select Paper Type</option>
                                        <option value="major" <?= (isset($editPaperData['paper_type']) && strtolower($editPaperData['paper_type']) == 'major') ? 'selected' : '' ?>>Major</option>
                                        <option value="minor" <?= (isset($editPaperData['paper_type']) && strtolower($editPaperData['paper_type']) == 'minor') ? 'selected' : '' ?>>Minor</option>
                                        <option value="practical" <?= (isset($editPaperData['paper_type']) && strtolower($editPaperData['paper_type']) == 'practical') ? 'selected' : '' ?>>Practical</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row" style="background: #f0f7ff; padding: 15px; border-radius: 8px; border: 1px solid #c2dbff;">
                                <div style="width: 100%; margin-bottom: 10px; font-weight: 600; color: #0d6efd;">Marks Components:</div>
                                <div style="display: flex; gap: 25px; flex-wrap: wrap;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                        <input type="checkbox" name="has_theory" value="1" <?= (!isset($editPaperData['sno']) || (isset($editPaperData['has_theory']) && $editPaperData['has_theory'] == 1)) ? 'checked' : '' ?>> Theory
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                        <input type="checkbox" name="has_internal" value="1" <?= (!isset($editPaperData['sno']) || (isset($editPaperData['has_internal']) && $editPaperData['has_internal'] == 1)) ? 'checked' : '' ?>> Internal (Sessional)
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                        <input type="checkbox" name="has_practical" value="1" <?= (isset($editPaperData['has_practical']) && $editPaperData['has_practical'] == 1) ? 'checked' : '' ?>> Practical / Viva
                                    </label>
                                </div>
                                
                                <div style="display: flex; gap: 20px; margin-top: 15px; border-top: 1px dashed #c2dbff; padding-top: 15px;">
                                    <div class="form-group" style="min-width: 150px;">
                                        <label style="color: #444;">Main Max Marks</label>
                                        <input type="number" name="max_marks" placeholder="e.g., 100" value="<?= $editPaperData['max_marks'] ?? '100' ?>" style="border-color: #c2dbff;">
                                    </div>
                                    <div class="form-group" style="min-width: 150px;">
                                        <label style="color: #444;">Mid-Sem Max Marks</label>
                                        <input type="number" name="mid_sem_max_marks" placeholder="e.g., 15" value="<?= $editPaperData['mid_sem_max_marks'] ?? '15' ?>" style="border-color: #c2dbff;">
                                    </div>
                                    <div class="form-group" style="min-width: 150px;" id="practical_max_marks_container">
                                        <label style="color: #444;">Practical Max Marks</label>
                                        <input type="number" name="practical_max_marks" placeholder="e.g., 50" value="<?= $editPaperData['practical_max_marks'] ?? '0' ?>" style="border-color: #c2dbff;">
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group" style="flex: 0;">
                                    <button type="submit" name="save_paper" class="save-btn">
                                        <?= isset($editPaperData['sno']) ? '💾 Update Paper' : '💾 Save Paper' ?>
                                    </button>
                                </div>
                                <?php if (isset($editPaperData['sno'])): ?>
                                    <div class="form-group" style="flex: 0;">
                                        <a href="add_suubject.php?class_filter=<?= $editPaperData['class_id'] ?>&tab=papers" class="btn btn-edit" style="display: inline-block; padding: 12px 24px; text-decoration: none;">Cancel</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- PAPERS LIST -->
                <div id="papersListSection" style="display: <?= isset($_GET['class_filter']) ? 'block' : 'none' ?>;">
                    <div class="card-box" style="box-shadow: none; padding: 0;">
                        <div class="card-heading">📋 Papers List</div>
                        
                        <table id="papersTable" class="display" style="width:100%">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Subject</th>
                                    <th>Paper Code</th>
                                    <th>Paper Title</th>
                                    <th>Type</th>
                                    <th>Components</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="papersTableBody">
                                <?php
                                if (isset($_GET['class_filter']) && $_GET['class_filter'] != '') {
                                    $class_filter = $_GET['class_filter'];
                                    $papersQry = mysqli_query($db, "SELECT p.*, c.class_description, s.subject 
                                                                     FROM add_subject_papers p
                                                                     LEFT JOIN class_detail c ON p.class_id = c.sno
                                                                     LEFT JOIN add_subject s ON p.subject_id = s.sno
                                                                     WHERE p.class_id = '$class_filter'
                                                                     ORDER BY p.sno ASC");
                                    $serial = 1;
                                    while ($paperRow = mysqli_fetch_assoc($papersQry)) {
                                        $components = [];
                                        if($paperRow['has_theory'] == 1) $components[] = '<span class="badge bg-primary" style="background:#0d6efd; color:white; padding:2px 6px; border-radius:4px; font-size:10px;">TH</span>';
                                        if($paperRow['has_internal'] == 1) $components[] = '<span class="badge bg-info" style="background:#0dcaf0; color:white; padding:2px 6px; border-radius:4px; font-size:10px;">INT</span>';
                                        if($paperRow['has_practical'] == 1) $components[] = '<span class="badge bg-warning" style="background:#ffc107; color:black; padding:2px 6px; border-radius:4px; font-size:10px;">PR</span>';
                                ?>
                                        <tr>
                                            <td><?= $serial++ ?></td>
                                            <td><?= $paperRow['subject'] ?? 'N/A' ?></td>
                                            <td><?= $paperRow['paper_code'] ?></td>
                                            <td><?= $paperRow['paper_title'] ?></td>
                                            <td><?= $paperRow['theory_practical'] ?></td>
                                            <td><?= implode(' ', $components) ?></td>
                                            <td>
                                                <a class="btn btn-edit" href="?edit_paper=<?= $paperRow['sno'] ?>&class_filter=<?= $class_filter ?>&tab=papers" style="margin-right: 5px;">Edit</a>
                                                <a class="btn btn-delete" href="?delete_paper=<?= $paperRow['sno'] ?>&class_filter=<?= $class_filter ?>" onclick="return confirm('Delete this paper?')" style="background: #dc3545;">Delete</a>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // ========================================
    // TAB SWITCHING FUNCTIONALITY
    // ========================================
    function switchTab(tabName) {
        // Hide all tab panes
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab pane
        document.getElementById(tabName + 'Pane').classList.add('active');
        
        // Add active class to selected button
        document.getElementById(tabName + 'Tab').classList.add('active');
    }

    // Check URL parameter to determine which tab to show
    window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const editPaper = urlParams.get('edit_paper');
        const classFilter = urlParams.get('class_filter');
        
        // If editing paper or class filter is set, switch to papers tab
        if (tabParam === 'papers' || editPaper || classFilter) {
            switchTab('papers');
        }
    });

    // ========================================
    // SUBJECTS TAB SCRIPTS
    // ========================================
    $(document).ready(function() {
        var table = $('#subjectsTable').DataTable({
            "lengthMenu": [[25, 50, -1], [25, 50, "All"]],
            "language": {
                "lengthMenu": "Show subjects _MENU_" // Custom label
            },
            initComplete: function() {
                // Add Subject Type Filter next to length menu
                var filterHtml = '<label style="margin-left: 15px; font-weight: normal;">Subject Type: <select id="typeFilter" style="margin-left: 5px; padding: 5px; border: 1px solid #aaa; border-radius: 4px;"><option value="">All</option><?php 
                    $typeQry2 = mysqli_query($db, "SELECT type FROM subject_type");
                    while($t = mysqli_fetch_assoc($typeQry2)) echo '<option value="'.$t['type'].'">'.$t['type'].'</option>';
                ?></select></label>';
                $('.dataTables_length').append(filterHtml);
                
                // Filter Logic
                $('#typeFilter').on('change', function() {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    // Column 2 is Subject Type
                    table.column(2).search(val ? '^' + val + '$' : '', true, false).draw();
                });
            }
        });
    });

    function toggleStatus(sno, newStatus, btnElement) {
        $.ajax({
            url: 'add_suubject.php',
            type: 'POST',
            data: {
                toggle_status: true,
                sno: sno,
                status: newStatus
            },
            success: function(response) {
                if(response.trim() === 'success') {
                    // Update button UI immediately without reload
                    let btn = $(btnElement);
                    if (newStatus == 1) {
                        btn.removeClass('status-disabled').addClass('status-enabled');
                        btn.text('Enabled');
                        btn.attr('onclick', `toggleStatus(${sno}, 0, this)`);
                    } else {
                        btn.removeClass('status-enabled').addClass('status-disabled');
                        btn.text('Disabled');
                        btn.attr('onclick', `toggleStatus(${sno}, 1, this)`);
                    }
                } else {
                    alert('Failed to update status.');
                }
            },
            error: function() {
                alert('Error processing request.');
            }
        });
    }
</script>

<script>
    // ========================================
    // PAPER MANAGEMENT SCRIPTS
    // ========================================

    // Class Filter Change Handler
    $('#classFilter').on('change', function() {
        var classId = $(this).val();
        if (classId) {
            window.location.href = 'add_suubject.php?tab=papers&class_filter=' + classId;
        } else {
            window.location.href = 'add_suubject.php?tab=papers';
        }
    });

    // Initialize Papers DataTable if class is selected
    <?php if (isset($_GET['class_filter']) && $_GET['class_filter'] != ''): ?>
    $(document).ready(function() {
        $('#papersTable').DataTable({
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "pageLength": 10,
            "language": {
                "lengthMenu": "Show _MENU_ papers"
            }
        });
    });
    <?php endif; ?>
</script>

</body>
</html>

