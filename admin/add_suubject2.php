<?php
ob_start();
session_start();

/* ==============================
   DATABASE & SETTINGS
================================ */
include("script/settings.php"); // Make sure $db connection is available

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ==============================
   TABLE NAME
================================ */
$table = "add_subject2";

/* ==============================
   INSERT / UPDATE
================================ */
if (isset($_POST['save'])) {

    $sno = $_POST['sno'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $subject_type = $_POST['subject_type'] ?? '';

    if ($sno == "") {
        // INSERT
        $sql = "INSERT INTO $table (subject, subject_type) VALUES ('$subject','$subject_type')";
    } else {
        // UPDATE
        $sql = "UPDATE $table SET subject='$subject', subject_type='$subject_type' WHERE sno='$sno'";
    }

    if (!$db->query($sql)) {
        die("Database Error: " . $db->error);
    }

    header("Location: add_subject2.php");
    exit;
}

/* ==============================
   DELETE
================================ */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE sno=$id");
    header("Location: add_subject2.php");
    exit;
}

/* ==============================
   EDIT
================================ */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $db->query("SELECT * FROM $table WHERE sno=$id");
    $editData = $res->fetch_assoc();
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
<title>Subject Type Management</title>
<style>

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

/* ===== FORM ROW ===== */
.form-row{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:18px;
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
    font-size:16px;
    color:#333;
}
.form-group input{
    padding:10px 14px;
    font-size:14px;
    border:1px solid #ccc;
    border-radius:8px;
    transition:0.3s;
}
.form-group input:focus{
    border-color:#0d6efd;
    box-shadow:0 0 5px rgba(13,110,253,0.3);
    outline:none;
}

/* ===== BUTTON ===== */
.save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:12px 32px;
    font-size:15px;
    font-weight:600;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
}
.save-btn:hover{
    background:#084298;
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}
thead th{
    background:#0d6efd;
    color:#fff;
    padding:12px;
    font-size:14px;
    text-align:left;
    border-radius:4px;
}
tbody td{
    padding:12px;
    font-size:16px;
    border-bottom:1px solid #eee;
}
tbody tr:hover{
    background:#f1f5f9;
}

/* ===== ACTION BUTTONS ===== */
.action-btns{
    display:flex;
    gap:6px;
}
.btn{
    padding:6px 12px;
    font-size:13px;
    border-radius:6px;
    color:#fff;
    text-decoration:none;
    transition:0.3s;
}
.btn-edit{
    background:#0d6efd;
}
.btn-edit:hover{
    background:#084298;
}
.btn-delete{
    background:#dc3545;
}
.btn-delete:hover{
    background:#a71d2a;
}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .form-row{
        flex-direction:column;
    }
}
</style>
</head>

<body>

<!-- ================= FORM ================= -->
<div class="card-box">
<div class="card-heading"><?= isset($editData) ? 'Edit Subject Type' : 'Add Subject Type' ?></div>

<form method="post">
<input type="hidden" name="sno" value="<?= $editData['sno'] ?? '' ?>">

<div class="form-row">
<div class="form-group">
    <label>Subject</label>
    <input type="text" name="subject" placeholder="Subject Name" value="<?= $editData['subject'] ?? '' ?>" required>
</div>
<div class="form-group">
    <label>Subject Type</label>
    <input type="text" name="subject_type" placeholder="Subject Type" value="<?= $editData['subject_type'] ?? '' ?>" required>
</div>
</div>

<button type="submit" name="save" class="save-btn">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>
</div>

<!-- ================= REPORT ================= -->
<div class="card-box">
<div class="card-heading">Subject Type List</div>

<table>
<thead>
<tr>
<th>ID</th><th>Subject</th><th>Subject Type</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php
$res = $db->query("SELECT * FROM $table ORDER BY sno DESC");
while($row=$res->fetch_assoc()){
?>
<tr>
<td><?= $row['sno'] ?></td>
<td><?= $row['subject'] ?></td>
<td><?= $row['subject_type'] ?></td>
<td>
<div class="action-btns">
<a class="btn btn-edit" href="?edit=<?= $row['sno'] ?>">Edit</a>
<a class="btn btn-delete" href="?delete=<?= $row['sno'] ?>" onclick="return confirm('Delete record?')">Delete</a>
</div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

</body>
</html>
