<?php
ob_start();
session_start();
include("script/settings.php"); // $db connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle AJAX Status Toggle for Fee Heads
if (isset($_POST['toggle_head_status'])) {
    $id = intval($_POST['id']);
    $new_status = intval($_POST['status']);
    $sql = "UPDATE fee_heads SET status='$new_status' WHERE id='$id'";
    echo mysqli_query($db, $sql) ? 'success' : 'error';
    exit;
}

// Handle AJAX Status Toggle for Fee Structure
if (isset($_POST['toggle_structure_status'])) {
    $id = intval($_POST['id']);
    $new_status = intval($_POST['status']);
    $sql = "UPDATE fee_structure SET status='$new_status' WHERE id='$id'";
    echo mysqli_query($db, $sql) ? 'success' : 'error';
    exit;
}

$msg = '';

// --- FEE HEADS LOGIC ---
if (isset($_POST['save_head'])) {
    $head_name = mysqli_real_escape_string($db, strtoupper(trim($_POST['head_name'])));
    $is_mandatory = isset($_POST['is_mandatory']) ? 1 : 0;
    
    if (!empty($_POST['head_id'])) {
        // Update
        $sql = "UPDATE fee_heads SET head_name='$head_name', is_mandatory='$is_mandatory' WHERE id='" . intval($_POST['head_id']) . "'";
        $msg = mysqli_query($db, $sql) ? '<div class="alert alert-success">Fee Head updated successfully.</div>' : '<div class="alert alert-danger">Error: ' . mysqli_error($db) . '</div>';
    } else {
        // Insert
        $sql = "INSERT INTO fee_heads (head_name, is_mandatory, status) VALUES ('$head_name', '$is_mandatory', 1)";
        $msg = mysqli_query($db, $sql) ? '<div class="alert alert-success">Fee Head added successfully.</div>' : '<div class="alert alert-danger">Error: ' . mysqli_error($db) . '</div>';
    }
}

// --- FEE STRUCTURE LOGIC ---
if (isset($_POST['save_structure'])) {
    // class_id is now an array because of multiple select
    $class_inputs = isset($_POST['class_id']) ? $_POST['class_id'] : [];
    if (!is_array($class_inputs)) {
        $class_inputs = [$class_inputs];
    }

    $fee_head_id = intval($_POST['fee_head_id']);
    $amount = floatval($_POST['amount']);
    $academic_session = mysqli_real_escape_string($db, $_POST['academic_session']);
    $due_date = !empty($_POST['due_date']) ? "'" . mysqli_real_escape_string($db, $_POST['due_date']) . "'" : "NULL";

    // Criteria Fields
    $criteria_gender = mysqli_real_escape_string($db, $_POST['criteria_gender'] ?? 'All');
    $criteria_category = mysqli_real_escape_string($db, $_POST['criteria_category'] ?? 'All');
    $criteria_subject_type = mysqli_real_escape_string($db, $_POST['criteria_subject_type'] ?? 'All');
    $criteria_income_group = mysqli_real_escape_string($db, $_POST['criteria_income_group'] ?? 'All');

    if (!empty($_POST['structure_id'])) {
        // --- UPDATE MODE (Single Record) ---
        $class_id = intval($class_inputs[0]); 

        $sql = "UPDATE fee_structure SET 
                class_id='$class_id', 
                fee_head_id='$fee_head_id', 
                amount='$amount', 
                academic_session='$academic_session',
                due_date=$due_date,
                criteria_gender='$criteria_gender',
                criteria_category='$criteria_category',
                criteria_subject_type='$criteria_subject_type',
                criteria_income_group='$criteria_income_group'
                WHERE id='" . intval($_POST['structure_id']) . "'";
        $msg = mysqli_query($db, $sql) ? '<div class="alert alert-success">Fee Rule updated successfully.</div>' : '<div class="alert alert-danger">Error: ' . mysqli_error($db) . '</div>';
    
    } else {
        // --- INSERT MODE (Multiple Records) ---
        $successCount = 0;
        
        if (in_array('all', $class_inputs)) {
             $acQry = mysqli_query($db, "SELECT sno FROM class_detail");
             $class_inputs = [];
             while ($acRow = mysqli_fetch_assoc($acQry)) {
                 $class_inputs[] = $acRow['sno'];
             }
        }

        foreach ($class_inputs as $cId) {
            $class_id = intval($cId);
            if ($class_id > 0) {
                $sql = "INSERT INTO fee_structure (class_id, fee_head_id, amount, academic_session, due_date, status, criteria_gender, criteria_category, criteria_subject_type, criteria_income_group) 
                        VALUES ('$class_id', '$fee_head_id', '$amount', '$academic_session', $due_date, 1, '$criteria_gender', '$criteria_category', '$criteria_subject_type', '$criteria_income_group')";
                if (mysqli_query($db, $sql)) $successCount++;
            }
        }
        
        if ($successCount > 0) {
             $msg = '<div class="alert alert-success">Fee Rules added for ' . $successCount . ' classes.</div>';
        } else {
             $msg = '<div class="alert alert-danger">No records added. Please select classes.</div>';
        }
    }
}

// Fetch Edit Data
$editHead = [];
if (isset($_GET['edit_head'])) {
    $editHead = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM fee_heads WHERE id=" . intval($_GET['edit_head'])));
}

$editStructure = [];
if (isset($_GET['edit_structure'])) {
    $editStructure = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM fee_structure WHERE id=" . intval($_GET['edit_structure'])));
}

// Fetch Classes for Dropdown
$classes = [];
$classQry = mysqli_query($db, "SELECT sno, class_description, group_name FROM class_detail ORDER BY sort_no ASC, class_description ASC");
if($classQry) {
    while ($row = mysqli_fetch_assoc($classQry)) {
        $classes[] = $row;
    }
} else {
    $classQry = mysqli_query($db, "SELECT sno, class_description, group_name FROM class_detail ORDER BY sno DESC"); 
    while ($row = mysqli_fetch_assoc($classQry)) {
        $classes[] = $row;
    }
}

// Fetch Fee Heeads
$feeHeads = [];
$hq = mysqli_query($db, "SELECT * FROM fee_heads WHERE status=1 ORDER BY head_name ASC");
if($hq) while($r = mysqli_fetch_assoc($hq)) $feeHeads[] = $r;

if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();

// Fetch Unique Groups
$groups = [];
$grpQry = mysqli_query($db, "SELECT DISTINCT group_name FROM class_detail WHERE group_name != '' ORDER BY group_name ASC");
while ($g = mysqli_fetch_assoc($grpQry)) {
    $groups[] = $g['group_name'];
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Fee Management</title>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* ===== CORE STYLES ===== */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f6;
}

/* ===== CARD ===== */
.card-box {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.card-heading {
    position: relative;
    font-size: 20px;
    font-weight: 700;
    color: #0d6efd;
    margin-bottom: 20px;
    padding-left: 18px;
}
.card-heading::before {
    content: '';
    position: absolute;
    left: 0;
    top: 4px;
    width: 5px;
    height: 90%;
    background: #0d6efd;
    border-radius: 4px;
}

/* ===== TABS ===== */
.tab-container {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 2px solid #ddd;
}
.tab-btn {
    padding: 10px 20px;
    cursor: pointer;
    font-weight: 600;
    border: none;
    background: none;
    border-bottom: 3px solid transparent;
    font-size: 16px;
    color: #555;
    transition: 0.3s;
}
.tab-btn:hover {
    color: #0d6efd;
}
.tab-btn.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
}
.tab-content {
    display: none;
    animation: fadeIn 0.4s ease-in-out;
}
.tab-content.active {
    display: block;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ===== FORM ===== */
.form-row {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 18px;
    align-items: flex-end;
}
.form-group {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 14px;
    color: #555;
}
.form-group input, .form-group select {
    padding: 10px 14px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    width: 100%;
    box-sizing: border-box;
    height: auto; 
}
.form-group input:focus, .form-group select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 5px rgba(13,110,253,0.3);
    outline: none;
}

/* Select2 Overrides */
.select2-container .select2-selection--single,
.select2-container .select2-selection--multiple {
    height: auto !important;
    min-height: 42px;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 5px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #0d6efd;
    border: 1px solid #0b5ed7;
    color: #fff;
    border-radius: 4px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 5px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #eee;
    background: transparent;
}


/* ===== BUTTONS ===== */
.save-btn {
    background: #0d6efd;
    color: #fff;
    border: none;
    padding: 10px 32px;
    font-size: 15px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    height: 42px;
}
.save-btn:hover {
    background: #084298;
}

.btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
    color: #fff;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
    border: none;
}
.btn-edit { background: #0d6efd; }
.btn-edit:hover { background: #084298; }

.status-enabled { background: #198754; }
.status-disabled { background: #dc3545; }

/* ===== TABLE ===== */
table.dataTable thead th {
    background-color: #0d6efd;
    color: white;
}
</style>
</head>

<body>

<div style="padding: 20px;">
    
    <?= $msg ?>

    <div class="card-box">
        <div class="tab-container">
            <button class="tab-btn <?= isset($_GET['edit_structure']) ? '' : 'active' ?>" onclick="switchTab('heads')">Fee Heads (Master)</button>
            <button class="tab-btn <?= isset($_GET['edit_structure']) ? 'active' : '' ?>" onclick="switchTab('structure')">Fee Rules (Rule Builder)</button>
        </div>

        <!-- ================= FEE HEADS TAB ================= -->
        <div id="heads" class="tab-content <?= isset($_GET['edit_structure']) ? '' : 'active' ?>">
            <div class="card-heading"><?= !empty($editHead) ? 'Edit Fee Head' : 'Add Fee Head' ?></div>
            <form method="post" action="fee_management.php">
                <input type="hidden" name="head_id" value="<?= $editHead['id'] ?? '' ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Fee Head Name</label>
                        <input type="text" name="head_name" placeholder="E.g. Admission Fee" value="<?= $editHead['head_name'] ?? '' ?>" required>
                    </div>
                    <div class="form-group" style="flex: 0.2; align-self: center; padding-bottom: 5px;">
                        <input type="checkbox" id="mandatory" name="is_mandatory" <?= (isset($editHead['is_mandatory']) && $editHead['is_mandatory'] != 1) ? '' : 'checked' ?>>
                        <label for="mandatory" style="display:inline;">Is Mandatory?</label>
                    </div>
                    <div class="form-group" style="flex: 0;">
                        <label>&nbsp;</label>
                        <button type="submit" name="save_head" class="save-btn"><?= !empty($editHead) ? 'Update Head' : 'Save Head' ?></button>
                    </div>
                </div>
            </form>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <table id="headTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Head Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hQry = mysqli_query($db, "SELECT * FROM fee_heads ORDER BY id DESC");
                    while ($row = mysqli_fetch_assoc($hQry)) {
                    ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['head_name'] ?></td>
                        <td><?= $row['is_mandatory'] ? 'Mandatory' : 'Optional' ?></td>
                        <td>
                            <button class="btn <?= $row['status'] == 1 ? 'status-enabled' : 'status-disabled' ?>" 
                                    onclick="toggleHead(<?= $row['id'] ?>, <?= $row['status'] == 1 ? 0 : 1 ?>, this)">
                                <?= $row['status'] == 1 ? 'Active' : 'Inactive' ?>
                            </button>
                        </td>
                        <td>
                            <a class="btn btn-edit" href="?edit_head=<?= $row['id'] ?>">Edit</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        
        <!-- ================= FEE STRUCTURE FORM ================= -->
        <div id="structure" class="tab-content <?= isset($_GET['edit_structure']) ? 'active' : '' ?>">
            <div class="card-heading"><?= !empty($editStructure) ? 'Edit Fee Rule' : 'Add Fee Rule' ?>
                <span style="font-size:14px; font-weight:normal; color:#666; margin-left:10px;">(Master Table - Define fees and criteria)</span>
            </div>
            
            <form method="post" action="fee_management.php?tab=structure">
                <input type="hidden" name="structure_id" value="<?= $editStructure['id'] ?? '' ?>">
                
                <!-- ROW 1: Class Selection -->
                <div class="form-row">
                     <!-- GROUP FILTER -->
                     <div class="form-group">
                        <label>Filter by Group</label>
                        <select id="groupSelect" onchange="filterClasses()">
                            <option value="">-- All Groups --</option>
                            <?php foreach($groups as $gname) { 
                                echo "<option value='$gname'>$gname</option>";
                            } ?>
                        </select>
                    </div>

                    <!-- CLASS SELECT (MULTI) -->
                    <div class="form-group" style="flex:2;">
                        <label>Select Class(es)</label>
                        <select name="class_id[]" id="classSelect" multiple="multiple" style="width: 100%;">
                            <option value="all" data-group="all" style="font-weight: bold;">-- All Classes --</option>
                            <?php foreach($classes as $c) { 
                                // For Edit mode
                                $selected = (isset($editStructure['class_id']) && $editStructure['class_id'] == $c['sno']) ? 'selected' : '';
                                echo "<option value='{$c['sno']}' data-group='{$c['group_name']}' $selected>{$c['class_description']} - {$c['group_name']}</option>";
                            } ?>
                        </select>
                    </div>
                </div>

                <!-- ROW 2: Criteria (Gender, Category, Subject, Income) -->
                <div class="form-row" style="background: #f0f8ff; padding: 15px; border-radius: 8px; border: 1px solid #d0e3ff;">
                    <div class="form-group">
                        <label>Gender Criteria</label>
                        <select name="criteria_gender" class="select2-basic">
                            <option value="All" <?= (isset($editStructure['criteria_gender']) && $editStructure['criteria_gender'] == 'All') ? 'selected' : '' ?>>All Genders</option>
                            <?php 
                            $gQ = $db->query("SELECT gender_name FROM genders");
                            while($gRow = $gQ->fetch_assoc()) {
                                $gVal = strtoupper($gRow['gender_name']);
                                $sel = (isset($editStructure['criteria_gender']) && $editStructure['criteria_gender'] == $gVal) ? 'selected' : '';
                                echo "<option value='$gVal' $sel>{$gRow['gender_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category Criteria</label>
                        <select name="criteria_category" class="select2-basic">
                            <option value="All" <?= (isset($editStructure['criteria_category']) && $editStructure['criteria_category'] == 'All') ? 'selected' : '' ?>>All Categories</option>
                            <?php 
                            $cQ = $db->query("SELECT category_name FROM categories");
                            while($cRow = $cQ->fetch_assoc()) {
                                $cVal = $cRow['category_name'];
                                $sel = (isset($editStructure['criteria_category']) && $editStructure['criteria_category'] == $cVal) ? 'selected' : '';
                                echo "<option value='$cVal' $sel>{$cRow['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject Type</label>
                        <select name="criteria_subject_type" class="select2-basic">
                            <option value="All" <?= (isset($editStructure['criteria_subject_type']) && $editStructure['criteria_subject_type'] == 'All') ? 'selected' : '' ?>>All Types</option>
                            <option value="Aided" <?= (isset($editStructure['criteria_subject_type']) && $editStructure['criteria_subject_type'] == 'Aided') ? 'selected' : '' ?>>Aided</option>
                            <option value="Self Finance" <?= (isset($editStructure['criteria_subject_type']) && $editStructure['criteria_subject_type'] == 'Self Finance') ? 'selected' : '' ?>>Self Finance</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Income Group</label>
                        <select name="criteria_income_group" class="select2-basic">
                            <option value="All" <?= (isset($editStructure['criteria_income_group']) && $editStructure['criteria_income_group'] == 'All') ? 'selected' : '' ?>>All Income Groups</option>
                            <?php 
                            $iQ = $db->query("SELECT income_group_name FROM income_groups WHERE is_active=1");
                            while($iRow = $iQ->fetch_assoc()) {
                                $iVal = $iRow['income_group_name'];
                                $sel = (isset($editStructure['criteria_income_group']) && $editStructure['criteria_income_group'] == $iVal) ? 'selected' : '';
                                echo "<option value='$iVal' $sel>{$iRow['income_group_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- ROW 3: Head, Amount, Session -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Fee Head</label>
                        <select name="fee_head_id" class="select2-basic" required>
                            <option value="">Select Head</option>
                            <?php foreach($feeHeads as $h) { 
                                $selected = (isset($editStructure['fee_head_id']) && $editStructure['fee_head_id'] == $h['id']) ? 'selected' : '';
                                echo "<option value='{$h['id']}' $selected>{$h['head_name']}</option>";
                            } ?>
                        </select>
                    </div>
                    

                    <div class="form-group">
                        <label>Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" placeholder="0.00" value="<?= $editStructure['amount'] ?? '' ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Academic Session</label>
                        <input type="text" name="academic_session" placeholder="e.g. 2024-2025" value="<?= $editStructure['academic_session'] ?? get_session_by_date(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" value="<?= $editStructure['due_date'] ?? '' ?>">
                    </div>
                    <div class="form-group" style="flex: 0;">
                        <label>&nbsp;</label>
                        <button type="submit" name="save_structure" class="save-btn"><?= !empty($editStructure) ? 'Update Rule' : 'Save Rule' ?></button>
                    </div>
                </div>
            </form>

            <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

            <table id="structureTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Class</th>
                        <th>Fee Head</th>
                        <th>Criteria Rules</th>
                        <th>Amount</th>
                        <th>Session</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display fee structure Logic
                    $sQry = mysqli_query($db, "SELECT fs.*, cd.class_description, cd.group_name, fh.head_name, fh.is_mandatory 
                                              FROM fee_structure fs 
                                              LEFT JOIN class_detail cd ON fs.class_id = cd.sno 
                                              JOIN fee_heads fh ON fs.fee_head_id = fh.id
                                              ORDER BY fs.id DESC");
                    
                    if($sQry) {
                        while ($row = mysqli_fetch_assoc($sQry)) {
                            // Build Criteria String
                            $rules = [];
                            if($row['criteria_gender'] != 'All') $rules[] = "Gender: " . $row['criteria_gender'];
                            if($row['criteria_category'] != 'All') $rules[] = "Cat: " . $row['criteria_category'];
                            if($row['criteria_subject_type'] != 'All') $rules[] = "Sub: " . $row['criteria_subject_type'];
                            if($row['criteria_income_group'] != 'All') $rules[] = "Inc: " . $row['criteria_income_group'];
                            
                            $ruleBadge = empty($rules) ? '<span style="color:#999;">All Students</span>' : 
                                         '<span style="background:#ffefc1; padding:2px 6px; border-radius:4px; font-size:12px; color:#856404; border:1px solid #ffeeba;">' . implode(', ', $rules) . '</span>';
                            
                            $headDisp = $row['head_name'];
                            if($row['is_mandatory'] == 0) $headDisp .= " <small class='text-muted'>(Optional)</small>";
                        ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['class_description'] ?? 'All' ?> <br><small style='color:#777;'><?= $row['group_name'] ?? '' ?></small></td>
                            <td><?= $headDisp ?></td>
                            <td><?= $ruleBadge ?></td>
                            <td>₹<?= number_format($row['amount'], 2) ?></td>
                            <td><?= $row['academic_session'] ?></td>
                            <td>
                                <button class="btn <?= $row['status'] == 1 ? 'status-enabled' : 'status-disabled' ?>" 
                                        onclick="toggleStructure(<?= $row['id'] ?>, <?= $row['status'] == 1 ? 0 : 1 ?>, this)">
                                    <?= $row['status'] == 1 ? 'Active' : 'Inactive' ?>
                                </button>
                            </td>
                            <td>
                                <a class="btn btn-edit" href="?edit_structure=<?= $row['id'] ?>&tab=structure">Edit</a>
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

<!-- All Class Options Cache for Filtering -->
<script>
    var allClassOptions = [];

    $(document).ready(function() {
        $('#headTable').DataTable();
        $('#structureTable').DataTable();
        
        // Initialize Select2 for Class
        $('#classSelect').select2({
            placeholder: "Select Class(es)",
            allowClear: true,
            width: '100%'
        });
        
        // Initialize Select2 for Group
        $('#groupSelect').select2({
            placeholder: "Filter by Group",
            allowClear: true,
            width: '100%'
        });

        // Initialize Select2 for Basic Dropdowns
        $('.select2-basic').select2({
             width: '100%'
        });

        // Cache options on load
        $('#classSelect option').each(function() {
            allClassOptions.push({
                val: $(this).val(),
                text: $(this).text(),
                group: $(this).attr('data-group'),
                selected: $(this).is(':selected')
            });
        });
    });

    function switchTab(tabId) {
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
        $('.tab-btn').removeClass('active');
        event.target.classList.add('active');
        
        // Remove edit params from URL
        if (!window.location.search.includes('tab=')) {
             const url = new URL(window.location);
             url.searchParams.delete('edit_head');
             url.searchParams.delete('edit_structure');
             window.history.pushState({}, '', url);
        }
    }

    function filterClasses() {
        var group = document.getElementById('groupSelect').value;
        var select = $('#classSelect');
        
        select.empty();
        
        allClassOptions.forEach(function(opt) {
            if (group === "" || group === "all" || opt.group === group || opt.val === "all") {
                var newOpt = new Option(opt.text, opt.val, false, opt.selected);
                $(newOpt).attr('data-group', opt.group);
                select.append(newOpt);
            }
        });
        
        select.trigger('change');
    }

    function toggleHead(id, newStatus, btn) {
        $.post('fee_management.php', { toggle_head_status: true, id: id, status: newStatus }, function(resp) {
            if(resp.trim() == 'success') location.reload(); else alert('Failed');
        });
    }

    function toggleStructure(id, newStatus, btn) {
        $.post('fee_management.php', { toggle_structure_status: true, id: id, status: newStatus }, function(resp) {
            if(resp.trim() == 'success') location.reload(); else alert('Failed');
        });
    }
</script>

</body>
</html>
