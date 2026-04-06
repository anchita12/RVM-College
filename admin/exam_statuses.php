<?php
ob_start();
session_start();

include("script/settings.php"); // DB connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

$table = "exam_statuses";

/* ================= INSERT / UPDATE ================= */
if (isset($_POST['save'])) {

    $exam_status_sno  = $_POST['exam_status_sno'] ?? '';
    $exam_status_name = $db->real_escape_string($_POST['exam_status_name'] ?? '');

    if ($exam_status_sno == "") {
        $sql = "INSERT INTO $table SET 
                exam_status_name='$exam_status_name'";
    } else {
        $sql = "UPDATE $table SET 
                exam_status_name='$exam_status_name'
                WHERE exam_status_sno='$exam_status_sno'";
    }

    if (!$db->query($sql)) {
        die("DB Error: " . $db->error);
    }

    header("Location: exam_statuses.php");
    exit;
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE exam_status_sno=$id");
    header("Location: exam_statuses.php");
    exit;
}

/* ================= EDIT ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editData = $db->query("SELECT * FROM $table WHERE exam_status_sno=$id")->fetch_assoc();
}

/* Sidebar & Header */
if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Exam Status Master</title>

<style>

/* CARD */
.card-box{
    background:#fff;
    padding:25px;
    margin-bottom:30px;
    border-radius:10px;
    box-shadow:0 6px 16px rgba(0,0,0,0.08);
}
.card-heading{
    font-size:20px;
    font-weight:700;
    color:#2563eb;
    margin-bottom:18px;
    border-left:5px solid #2563eb;
    padding-left:12px;
}

/* FORM */
.form-row{
    display:grid;
    grid-template-columns:1fr;
    gap:20px;
    margin-bottom:20px;
}
.form-group label{
    font-size:14px;
    font-weight:600;
    margin-bottom:6px;
    display:block;
}
.form-group input{
    width:100%;
    height:42px;
    padding:8px 12px;
    font-size:14px;
    border:1px solid #d1d5db;
    border-radius:6px;
}
.form-group input:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 2px rgba(37,99,235,.15);
    outline:none;
}

/* BUTTON */
.save-btn{
    background:#2563eb;
    color:#fff;
    border:none;
    padding:10px 36px;
    font-size:15px;
    font-weight:600;
    border-radius:8px;
    cursor:pointer;
}
.save-btn:hover{background:#1e40af}

/* TABLE */
table{width:100%;border-collapse:collapse}
thead th{
    background:#2563eb;
    color:#fff;
    padding:12px 10px;
    font-size:14px;
    text-align:left;
}
tbody td{
    padding:10px;
    font-size:14px;
    border-bottom:1px solid #eee;
}
tbody tr:hover{background:#f1f5f9}

/* ACTION */
.action-btns{
    display:flex;
    justify-content:center;
    gap:8px;
}
.action-btns a{
    padding:6px 14px;
    border-radius:6px;
    color:#fff;
    text-decoration:none;
    font-size:13px;
    font-weight:500;
}
.btn-edit{background:#2563eb}
.btn-edit:hover{background:#1e40af}
.btn-delete{background:#ef4444}
.btn-delete:hover{background:#b91c1c}

thead th:last-child,
tbody td:last-child{
    text-align:center;
}
</style>
</head>

<body>

<!-- ================= FORM ================= -->
<div class="card-box">
<div class="card-heading">
<?= isset($editData) ? 'Edit Exam Status' : 'Add Exam Status' ?>
</div>

<form method="post">
<input type="hidden" name="exam_status_sno"
       value="<?= htmlspecialchars($editData['exam_status_sno'] ?? '') ?>">

<div class="form-row">
    <div class="form-group">
        <label>Exam Status Name</label>
        <input type="text" name="exam_status_name" required
               placeholder="Passed / Failed / Appearing"
               value="<?= htmlspecialchars($editData['exam_status_name'] ?? '') ?>">
    </div>
</div>

<button type="submit" name="save" class="save-btn">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>
</div>

<!-- ================= REPORT ================= -->
<div class="card-box">
<div class="card-heading">Exam Status Report</div>

<table>
<thead>
<tr>
    <th>S.No</th>
    <th>Exam Status Name</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php
$res = $db->query("SELECT * FROM $table ORDER BY exam_status_sno DESC");
while ($row = $res->fetch_assoc()) {
    echo "<tr>
        <td>{$row['exam_status_sno']}</td>
        <td>".htmlspecialchars($row['exam_status_name'])."</td>
        <td>
            <div class='action-btns'>
                <a class='btn-edit' href='?edit={$row['exam_status_sno']}'>Edit</a>
                <a class='btn-delete' href='?delete={$row['exam_status_sno']}'
                   onclick=\"return confirm('Are you sure?')\">Delete</a>
            </div>
        </td>
    </tr>";
}
?>
</tbody>
</table>
</div>

</body>
</html>
