<?php
ob_start();
session_start();

/* ==============================
   DATABASE & SETTINGS
================================ */
include("script/settings.php"); // Make sure this file has your $db connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ==============================
   TABLE NAME
================================ */
$table = "class_detail"; // Replace with your actual table name if different

/* ==============================
   INSERT / UPDATE
================================ */
if (isset($_POST['save'])) {

    $sno              = $_POST['sno'] ?? '';
    $class_description= $_POST['class_description'] ?? '';
    $group_name       = $_POST['group_name'] ?? '';
    $course_code      = $_POST['course_code'] ?? '';
    $course_enroll    = $_POST['course_enroll'] ?? '';
    $group_short      = $_POST['group_short'] ?? '';
    $display_sort     = $_POST['display_sort'] ?? '';
    $year             = $_POST['year'] ?? '';
    $total_seat       = $_POST['total_seat'] ?? '';
    $category         = $_POST['category'] ?? '';
    $type             = $_POST['type'] ?? '';
    $sort_no          = $_POST['sort_no'] ?? '';
    $course_year      = $_POST['course_year'] ?? '';
    $exam_form        = $_POST['exam_form'] ?? '';
    $crasslist_type   = $_POST['crasslist_type'] ?? '';
    $class_type       = $_POST['class_type'] ?? '';
    $semester         = $_POST['semester'] ?? '';
    $exam_show        = $_POST['exam_show'] ?? '';
    $show_back        = $_POST['show_back'] ?? '';
    $online_admission = $_POST['online_admission'] ?? '';

    if ($sno == "") {
        // INSERT
        $sql = "INSERT INTO $table
        (class_description, group_name, course_code, course_enroll, group_short,
         display_sort, year, total_seat, category, type, sort_no, course_year,
         exam_form, crasslist_type, class_type, semester, exam_show, show_back, online_admission)
        VALUES
        ('$class_description','$group_name','$course_code','$course_enroll','$group_short',
         '$display_sort','$year','$total_seat','$category','$type','$sort_no','$course_year',
         '$exam_form','$crasslist_type','$class_type','$semester','$exam_show','$show_back','$online_admission')";
    } else {
        // UPDATE
        $sql = "UPDATE $table SET
        class_description='$class_description',
        group_name='$group_name',
        course_code='$course_code',
        course_enroll='$course_enroll',
        group_short='$group_short',
        display_sort='$display_sort',
        year='$year',
        total_seat='$total_seat',
        category='$category',
        type='$type',
        sort_no='$sort_no',
        course_year='$course_year',
        exam_form='$exam_form',
        crasslist_type='$crasslist_type',
        class_type='$class_type',
        semester='$semester',
        exam_show='$exam_show',
        show_back='$show_back',
        online_admission='$online_admission'
        WHERE sno='$sno'";
    }

    if (!$db->query($sql)) {
        die("Database Error: " . $db->error);
    }

    header("Location: ADD_CLASS.php"); // replace with your actual file name
    exit;
}

/* ==============================
   DELETE
================================ */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE sno=$id");
    header("Location: ADD_CLASS.php"); // replace with your actual file name
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
   LAYOUT
================================ */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Class Detail</title>
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
<div class="card-heading">Class Detail Form</div>

<form method="post">
<input type="hidden" name="sno" value="<?= $editData['sno'] ?? '' ?>">

<div class="form-row">
<div class="form-group">
    <label>Class Description</label>
    <input type="text" name="class_description" placeholder="Class Description" value="<?= $editData['class_description'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Group Name</label>
    <input type="text" name="group_name" placeholder="Group Name" value="<?= $editData['group_name'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Course Code</label>
    <input type="text" name="course_code" placeholder="Course Code" value="<?= $editData['course_code'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Enroll</label>
    <input type="text" name="course_enroll" placeholder="Enroll" value="<?= $editData['course_enroll'] ?? '' ?>">
</div>
</div>

<div class="form-row">
<div class="form-group">
    <label>Group Short</label>
    <input type="text" name="group_short" placeholder="Group Short" value="<?= $editData['group_short'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Display Sort</label>
    <input type="text" name="display_sort" placeholder="Display Sort" value="<?= $editData['display_sort'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Year</label>
    <input type="text" name="year" placeholder="Year" value="<?= $editData['year'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Total Seat</label>
    <input type="text" name="total_seat" placeholder="Total Seat" value="<?= $editData['total_seat'] ?? '' ?>">
</div>
</div>

<div class="form-row">
<div class="form-group">
    <label>Category</label>
    <input type="text" name="category" placeholder="Category" value="<?= $editData['category'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Type</label>
    <input type="text" name="type" placeholder="Type" value="<?= $editData['type'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Sort No</label>
    <input type="text" name="sort_no" placeholder="Sort No" value="<?= $editData['sort_no'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Course Year</label>
    <input type="text" name="course_year" placeholder="Course Year" value="<?= $editData['course_year'] ?? '' ?>">
</div>
</div>

<div class="form-row">
<div class="form-group">
    <label>Exam Form</label>
    <input type="text" name="exam_form" placeholder="Exam Form" value="<?= $editData['exam_form'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Classlist Type</label>
    <input type="text" name="crasslist_type" placeholder="Classlist Type" value="<?= $editData['crasslist_type'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Class Type</label>
    <input type="text" name="class_type" placeholder="Class Type" value="<?= $editData['class_type'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Semester</label>
    <input type="text" name="semester" placeholder="Semester" value="<?= $editData['semester'] ?? '' ?>">
</div>
</div>

<div class="form-row">
<div class="form-group">
    <label>Exam Show</label>
    <input type="text" name="exam_show" placeholder="Exam Show" value="<?= $editData['exam_show'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Show Back</label>
    <input type="text" name="show_back" placeholder="Show Back" value="<?= $editData['show_back'] ?? '' ?>">
</div>
<div class="form-group">
    <label>Online Admission</label>
    <input type="text" name="online_admission" placeholder="Online Admission" value="<?= $editData['online_admission'] ?? '' ?>">
</div>
</div>

<button type="submit" name="save" class="save-btn">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>
</div>

<!-- ================= REPORT ================= -->
<div class="card-box">
<div class="card-heading">Class Detail Report</div>

<table>
<thead>
<tr>
<th>ID</th><th>Class</th><th>Group</th><th>Course</th>
<th>Year</th><th>Seat</th><th>Semester</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php
$res = $db->query("SELECT * FROM $table ORDER BY sno DESC");
while($row=$res->fetch_assoc()){
?>
<tr>
<td><?= $row['sno'] ?></td>
<td><?= $row['class_description'] ?></td>
<td><?= $row['group_name'] ?></td>
<td><?= $row['course_code'] ?></td>
<td><?= $row['year'] ?></td>
<td><?= $row['total_seat'] ?></td>
<td><?= $row['semester'] ?></td>
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
