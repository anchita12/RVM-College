<?php
session_start();
include('script/settings.php');

// Use the global $db connection from settings.php

if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();

// Fetching Dropdown Data
$classes = $db->query("SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
$genders = $db->query("SELECT gender_sno, gender_name FROM genders");
$categories = $db->query("SELECT categories_sno, category_name FROM categories");
$subjects = $db->query("SELECT sno, subject FROM add_subject WHERE is_active=1 ORDER BY order_no");

// Filter Params
$f_class = $_POST['f_class'] ?? 'ALL';
$f_gender = $_POST['f_gender'] ?? 'ALL';
$f_cat = $_POST['f_cat'] ?? 'ALL';
$f_sub1 = $_POST['f_sub1'] ?? '';
$f_sub2 = $_POST['f_sub2'] ?? '';
$f_sub3 = $_POST['f_sub3'] ?? '';
$f_type = $_POST['f_type'] ?? 'ALL';
$f_date_type = $_POST['f_date_type'] ?? 'Admission Date';
$f_from_date = $_POST['f_from_date'] ?? date('Y-01-01');
$f_to_date = $_POST['f_to_date'] ?? date('Y-m-d');

// Building Query
$where = " WHERE 1=1 ";

if ($f_class != 'ALL')
    $where .= " AND si.class = '$f_class' ";
if ($f_gender != 'ALL')
    $where .= " AND si.gender = '$f_gender' ";
if ($f_cat != 'ALL')
    $where .= " AND si.category = '$f_cat' ";

if ($f_sub1)
    $where .= " AND (si.sub1 = '$f_sub1' OR si.sub2 = '$f_sub1' OR si.sub3 = '$f_sub1') ";
if ($f_sub2)
    $where .= " AND (si.sub1 = '$f_sub2' OR si.sub2 = '$f_sub2' OR si.sub3 = '$f_sub2') ";
if ($f_sub3)
    $where .= " AND (si.sub1 = '$f_sub3' OR si.sub2 = '$f_sub3' OR si.sub3 = '$f_sub3') ";

$date_column = ($f_date_type == 'Admission Date') ? 'date_of_admission' : 'dob';
$where .= " AND si.$date_column BETWEEN '$f_from_date' AND '$f_to_date' ";

$sql = "SELECT si.*, cd.class_description, g.gender_name, cat.category_name,
               s1.subject as sub1_name, s2.subject as sub2_name, s3.subject as sub3_name
        FROM student_info si 
        LEFT JOIN class_detail cd ON si.class = cd.sno
        LEFT JOIN genders g ON si.gender = g.gender_sno
        LEFT JOIN categories cat ON si.category = cat.categories_sno
        LEFT JOIN add_subject s1 ON si.sub1 = s1.sno
        LEFT JOIN add_subject s2 ON si.sub2 = s2.sno
        LEFT JOIN add_subject s3 ON si.sub3 = s3.sno
        $where ORDER BY si.sno ASC";

$results = null;
if (isset($_POST['btn_submit'])) {
    $results = $db->query($sql);
}
?>

<style>
    .ledger-box {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin: 20px;
    }

    .page-title {
        font-size: 28px;
        font-weight: 600;
        color: #333;
        margin-bottom: 25px;
    }

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

    .submit-btn {
        background: #4a90e2;
        color: #fff;
        border: none;
        padding: 12px 30px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
        width: fit-content;
        margin-top: 10px;
    }

    .submit-btn:hover {
        background: #357abd;
        transform: translateY(-1px);
    }

    .ledger-table-container {
        margin-top: 30px;
        overflow-x: auto;
        border-radius: 8px;
        border: 1px solid #eee;
    }

    .ledger-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .ledger-table thead {
        background: #1e88e5;
        color: #fff;
    }

    .ledger-table th {
        padding: 12px 15px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .ledger-table td {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        font-size: 13px;
        border-right: 1px solid #eee;
        color: #000;
    }

    /* STATUS COLORS */
    .row-regular td {
        background-color: #e8f5e9 !important;
    }

    /* Greenish Background */
    .row-changed td {
        background-color: #fff9c4 !important;
    }

    /* Yellowish Background */

    tr:hover td {
        background-color: #f5f5f5 !important;
    }

    /* Nested History Table Style */
    .info-row td {
        background-color: #ffffff !important;
        padding: 5px !important;
        border: 2px solid #1e88e5 !important;
    }
    
    .nest-table-container {
        padding: 10px;
        background: #fdfdfd;
        border-radius: 4px;
        animation: slideDown 0.3s ease;
    }

    .nest-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-bottom: 5px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    .nest-table thead th {
        background: #1e88e5;
        color: #fff;
        padding: 8px 10px;
        text-align: left;
        font-weight: 700;
        text-transform: uppercase;
        border-right: 1px solid rgba(255,255,255,0.2);
    }

    .nest-table tbody td {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
        border-right: 1px solid #eee;
        color: #333;
    }

    .row-type-new {
        background-color: #e3f2fd;
        font-weight: bold;
    }

    .row-type-old {
        background-color: #fffde7;
    }

    .badge-type {
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 800;
        font-size: 10px;
    }

    .badge-old { background: #fff9c4; color: #f57f17; border: 1px solid #ffeb3b; }
    .badge-new { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .clickable-row { cursor: pointer; }
    .active-row td { background-color: #e3f2fd !important; font-weight: bold; }
</style>

<div class="ledger-box">
    <h1 class="page-title">Student Ledger</h1>

    <form method="POST">
        <div class="filter-row">
            <div class="filter-group">
                <label>Class</label>
                <select name="f_class">
                    <option value="ALL">ALL</option>
                    <?php while ($row = $classes->fetch_assoc()) { ?>
                        <option value="<?= $row['sno'] ?>" <?= ($f_class == $row['sno']) ? 'selected' : '' ?>>
                            <?= $row['class_description'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Gender</label>
                <select name="f_gender">
                    <option value="ALL">ALL</option>
                    <?php
                    $genders->data_seek(0);
                    while ($row = $genders->fetch_assoc()) { ?>
                        <option value="<?= $row['gender_sno'] ?>" <?= ($f_gender == $row['gender_sno']) ? 'selected' : '' ?>>
                            <?= $row['gender_name'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Caste</label>
                <select name="f_cat">
                    <option value="ALL">ALL</option>
                    <?php
                    $categories->data_seek(0);
                    while ($row = $categories->fetch_assoc()) { ?>
                        <option value="<?= $row['categories_sno'] ?>" <?= ($f_cat == $row['categories_sno']) ? 'selected' : '' ?>>
                            <?= $row['category_name'] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-group">
                <label>Select Subject 1</label>
                <select name="f_sub1">
                    <option value="">--Select--</option>
                    <?php
                    $subjects->data_seek(0);
                    while ($row = $subjects->fetch_assoc()) { ?>
                        <option value="<?= $row['sno'] ?>" <?= ($f_sub1 == $row['sno']) ? 'selected' : '' ?>>
                            <?= $row['subject'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Select Subject 2</label>
                <select name="f_sub2">
                    <option value="">--Select--</option>
                    <?php
                    $subjects->data_seek(0);
                    while ($row = $subjects->fetch_assoc()) { ?>
                        <option value="<?= $row['sno'] ?>" <?= ($f_sub2 == $row['sno']) ? 'selected' : '' ?>>
                            <?= $row['subject'] ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Select Subject 3</label>
                <select name="f_sub3">
                    <option value="">--Select--</option>
                    <?php
                    $subjects->data_seek(0);
                    while ($row = $subjects->fetch_assoc()) { ?>
                        <option value="<?= $row['sno'] ?>" <?= ($f_sub3 == $row['sno']) ? 'selected' : '' ?>>
                            <?= $row['subject'] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-group">
                <label>Type</label>
                <select name="f_type">
                    <option value="ALL" <?= ($f_type == 'ALL') ? 'selected' : '' ?>>ALL</option>
                    <option value="Regular" <?= ($f_type == 'Regular') ? 'selected' : '' ?>>Regular</option>
                    <option value="Private" <?= ($f_type == 'Private') ? 'selected' : '' ?>>Private</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Date Type</label>
                <select name="f_date_type">
                    <option value="Admission Date" <?= ($f_date_type == 'Admission Date') ? 'selected' : '' ?>>Admission Date
                    </option>
                    <option value="DOB" <?= ($f_date_type == 'DOB') ? 'selected' : '' ?>>DOB</option>
                </select>
            </div>
            <div class="filter-group">
                <label>From Date</label>
                <input type="date" name="f_from_date" value="<?= $f_from_date ?>">
            </div>
        </div>

        <div class="filter-row">
            <div class="filter-group">
                <label>To Date</label>
                <input type="date" name="f_to_date" value="<?= $f_to_date ?>">
            </div>
            <div class="filter-group"></div>
            <div class="filter-group"></div>
        </div>

        <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="submit-btn" name="btn_submit">Submit</button>
            <button type="button" class="submit-btn" style="background:#6c757d;"
                onclick="window.location.href='student_ledger.php'">Reset</button>
        </div>
    </form>

    <?php if (isset($_POST['btn_submit'])): ?>
        <div class="ledger-table-container">
            <table class="ledger-table">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Father's Name</th>
                        <th>Admission Date</th>
                        <th>Gender</th>
                        <th>Cat</th>
                        <th>Sub 1</th>
                        <th>Sub 2</th>
                        <th>Sub 3</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($results && $results->num_rows > 0) {
                        $sno_count = 1;
                        while ($st = $results->fetch_assoc()) {
                            // Check history for all subject changes
                            $q_hist = $db->query("SELECT si2.*, cd.class_description as hist_class, g.gender_name as hist_gender, cat.category_name as hist_cat,
                                                   s1.subject as s1n, s2.subject as s2n, s3.subject as s3n
                                            FROM student_info2 si2
                                            LEFT JOIN class_detail cd ON si2.class = cd.sno
                                            LEFT JOIN genders g ON si2.gender = g.gender_sno
                                            LEFT JOIN categories cat ON si2.category = cat.categories_sno
                                            LEFT JOIN add_subject s1 ON si2.sub1 = s1.sno
                                            LEFT JOIN add_subject s2 ON si2.sub2 = s2.sno
                                            LEFT JOIN add_subject s3 ON si2.sub3 = s3.sno
                                            WHERE student_info_sno = '" . $st['sno'] . "' 
                                            AND reason LIKE '%Subject Change%' 
                                            ORDER BY si2.sno DESC");
                            
                            $is_changed = ($q_hist && $q_hist->num_rows > 0);
                            $row_class = $is_changed ? 'row-changed' : 'row-regular';

                            // Gather New (Current) record
                            $current_data = [
                                'type' => 'NEW',
                                'roll' => $st['roll_no'] ?: '-',
                                'name' => $st['stu_name'],
                                'father' => $st['father_name'],
                                'date' => date('d-M-Y', strtotime($st['date_of_admission'])),
                                'gen' => $st['gender_name'],
                                'cat' => $st['category_name'],
                                'sub1' => $st['sub1_name'] ?: ($st['sub1'] ?: '-'),
                                'sub2' => $st['sub2_name'] ?: ($st['sub2'] ?: '-'),
                                'sub3' => $st['sub3_name'] ?: ($st['sub3'] ?: '-'),
                                'captured' => '-'
                            ];

                            $history_records = [];
                            if ($is_changed) {
                                while ($h = $q_hist->fetch_assoc()) {
                                    $history_records[] = [
                                        'type' => 'OLD',
                                        'roll' => (!empty($h['roll_no']) ? $h['roll_no'] : ($st['roll_no'] ?: '-')), 
                                        'name' => $h['stu_name'],
                                        'father' => $h['father_name'],
                                        'date' => date('d-M-Y', strtotime($h['date_of_admission'])),
                                        'gen' => $h['hist_gender'] ?: '-',
                                        'cat' => $h['hist_cat'] ?: '-',
                                        'sub1' => $h['s1n'] ?: ($h['sub1'] ?: '-'),
                                        'sub2' => $h['s2n'] ?: ($h['sub2'] ?: '-'),
                                        'sub3' => $h['s3n'] ?: ($h['sub3'] ?: '-'),
                                        'captured' => $h['timestamp'] ?? ($h['sno'] ?: '-')
                                    ];
                                }
                            }
                            
                            // Combine for JS
                            $all_info = json_encode(['current' => $current_data, 'history' => $history_records]);
                            ?>
                            <tr class="<?= $row_class ?> clickable-row" data-info='<?= htmlspecialchars($all_info, ENT_QUOTES) ?>' onclick="showStudentInfo(this)">
                                <td><?= $sno_count++ ?></td>
                                <td><?= $st['roll_no'] ?: '-' ?></td>
                                <td><strong><?= $st['stu_name'] ?></strong></td>
                                <td><?= $st['father_name'] ?></td>
                                <td><?= date('d-M-Y', strtotime($st['date_of_admission'])) ?></td>
                                <td><?= $st['gender_name'] ?></td>
                                <td><?= $st['category_name'] ?></td>
                                <td><?= ($st['sub1_name'] ?: ($st['sub1'] ?: '-')) ?></td>
                                <td><?= ($st['sub2_name'] ?: ($st['sub2'] ?: '-')) ?></td>
                                <td><?= ($st['sub3_name'] ?: ($st['sub3'] ?: '-')) ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="10" style="text-align:center; padding: 20px;">No students found for given criteria.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function showStudentInfo(row) {
    const isActive = row.classList.contains('active-row');
    const existingInfo = document.querySelector('.info-row');
    if (existingInfo) existingInfo.remove();
    document.querySelectorAll('.active-row').forEach(r => r.classList.remove('active-row'));
    
    if (isActive) return;

    row.classList.add('active-row');
    const infoRow = document.createElement('tr');
    infoRow.className = 'info-row';
    
    const data = JSON.parse(row.dataset.info);
    
    let rowsHtml = '';

    // RENDER OLD (History) - Only show old records as requested
    if (data.history.length > 0) {
        data.history.forEach(h => {
            rowsHtml += `<tr class="row-type-old">
                <td><span class="badge-type badge-old">CURRENT</span></td>
                <td>${h.roll}</td>
                <td>${h.name}</td>
                <td>${h.father}</td>
                <td>${h.date}</td>
                <td>${h.gen}</td>
                <td>${h.cat}</td>
                <td>${h.sub1}</td>
                <td>${h.sub2}</td>
                <td>${h.sub3}</td>
                
            </tr>`;
        });
    } else {
        rowsHtml = '<tr><td colspan="11" style="text-align:center; padding: 20px;">No historical records found for this student.</td></tr>';
    }

    infoRow.innerHTML = `
        <td colspan="10">
            <div class="nest-table-container">
                <table class="nest-table">
                    <thead>
                        <tr>
                            <th>TYPE</th>
                            <th>ROLL NO</th>
                            <th>STUDENT NAME</th>
                            <th>FATHER NAME</th>
                            <th>ADMSN DATE</th>
                            <th>GEN</th>
                            <th>CAT</th>
                            <th>SUB 1</th>
                            <th>SUB 2</th>
                            <th>SUB 3</th>
                            
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </td>
    `;
    
    row.parentNode.insertBefore(infoRow, row.nextSibling);
    infoRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>

<?php if (function_exists('page_footer'))
    page_footer(); ?>