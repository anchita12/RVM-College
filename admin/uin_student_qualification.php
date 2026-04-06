<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$table = "uin_student_qualification";

/* ================= INSERT / UPDATE ================= */
if(isset($_POST['save'])){
    $id = $_POST['id'] ?? '';

    $fields = [
        'student_id','exam_name','college_name','passing_year',
        'roll_no','board','total_marks','marks_obtained',
        'percentage','grade','division','status'
    ];

    $data = [];
    foreach($fields as $f){
        $data[$f] = $db->real_escape_string($_POST[$f] ?? '');
    }

    $set = [];
    foreach($fields as $f){
        $set[] = "$f='{$data[$f]}'";
    }

    if($id==""){
        $sql = "INSERT INTO $table SET ".implode(',', $set);
    }else{
        $sql = "UPDATE $table SET ".implode(',', $set)." WHERE id='$id'";
    }

    $db->query($sql) or die($db->error);
    header("Location: uin_student_qualification.php");
    exit;
}

/* ================= DELETE ================= */
if(isset($_GET['delete'])){
    $db->query("DELETE FROM $table WHERE id=".(int)$_GET['delete']);
    header("Location: uin_student_qualification.php");
    exit;
}

/* ================= EDIT ================= */
$editData = null;
if(isset($_GET['edit'])){
    $editData = $db->query("SELECT * FROM $table WHERE id=".(int)$_GET['edit'])->fetch_assoc();
}

if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Qualification</title>

<style>

/* CARD */
.card-box{
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 8px 18px rgba(0,0,0,.08);
    margin-bottom:30px;
}
.card-heading{
    font-size:20px;
    font-weight:700;
    color:#2563eb;
    margin-bottom:18px;
    border-left:5px solid #2563eb;
    padding-left:12px;
}

/* FORM GRID */
.form-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:16px;
}
.form-group label{
    font-size:13px;
    font-weight:600;
    margin-bottom:6px;
}
.form-group input,
.form-group select{
    width:100%;
    height:42px;
    padding:8px 12px;
    border:1px solid #d1d5db;
    border-radius:6px;
}
.form-group input:focus,
.form-group select:focus{
    border-color:#2563eb;
    box-shadow:0 0 0 2px rgba(37,99,235,.15);
    outline:none;
}

/* BUTTON */
.save-btn{
    background:#2563eb;
    color:#fff;
    padding:10px 36px;
    font-size:15px;
    border:none;
    border-radius:8px;
    cursor:pointer;
}
.save-btn:hover{background:#1e40af}

/* TABLE */
table{width:100%;border-collapse:collapse}
thead th{
    background:#2563eb;
    color:#fff;
    padding:12px;
    font-size:14px;
}
tbody td{
    padding:10px;
    font-size:16px;
    border-bottom:1px solid #eee;
}
tbody tr:hover{background:#f1f5f9}

/* ACTION BUTTON */
.action-btns{display:flex;gap:8px}
.action-btns a{
    padding:6px 14px;
    border-radius:6px;
    color:#fff;
    text-decoration:none;
    font-size:13px;
}
.btn-edit{background:#2563eb}
.btn-delete{background:#ef4444}
.btn-edit:hover{background:#1e40af}
.btn-delete:hover{background:#b91c1c}

@media(max-width:1000px){
    .form-row{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:600px){
    .form-row{grid-template-columns:1fr;}
}
.action-btns{
    display:flex;
    justify-content:center;
    align-items:center;
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

.btn-edit{
    background:#2563eb;
}
.btn-edit:hover{
    background:#1e40af;
}

.btn-delete{
    background:#ef4444;
}
.btn-delete:hover{
    background:#b91c1c;
}

</style>
</head>

<body>

<div class="card-box">
<div class="card-heading"><?= $editData?'Edit Qualification':'Add Qualification' ?></div>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<div class="form-row">

<div class="form-group">
<label>Student</label>
<select name="student_id" required>
<option value="">Select</option>
<?php
$s=$db->query("SELECT id,candidate_name FROM uin_register_student");
while($r=$s->fetch_assoc()){
$sel=($editData && $editData['student_id']==$r['id'])?'selected':'';
echo "<option value='{$r['id']}' $sel>{$r['candidate_name']}</option>";
}
?>
</select>
</div>

<div class="form-group"><label>Exam</label><input name="exam_name" value="<?= $editData['exam_name'] ?? '' ?>"></div>
<div class="form-group"><label>College</label><input name="college_name" value="<?= $editData['college_name'] ?? '' ?>"></div>
<div class="form-group"><label>Year</label><input name="passing_year" value="<?= $editData['passing_year'] ?? '' ?>"></div>

<div class="form-group"><label>Roll No</label><input name="roll_no" value="<?= $editData['roll_no'] ?? '' ?>"></div>
<div class="form-group"><label>Board</label><input name="board" value="<?= $editData['board'] ?? '' ?>"></div>
<div class="form-group"><label>Total Marks</label><input name="total_marks" value="<?= $editData['total_marks'] ?? '' ?>"></div>
<div class="form-group"><label>Marks Obtained</label><input name="marks_obtained" value="<?= $editData['marks_obtained'] ?? '' ?>"></div>

<div class="form-group"><label>Percentage</label><input name="percentage" value="<?= $editData['percentage'] ?? '' ?>"></div>
<div class="form-group"><label>Grade</label><input name="grade" value="<?= $editData['grade'] ?? '' ?>"></div>
<div class="form-group"><label>Division</label><input name="division" value="<?= $editData['division'] ?? '' ?>"></div>

<div class="form-group">
<label>Status</label>
<select name="status">
<option value="Active">Active</option>
<option value="Inactive">Inactive</option>
</select>
</div>

</div>

<button class="save-btn" name="save">Save</button>
</form>
</div>

<div class="card-box">
<div class="card-heading">Qualification Report</div>

<table>
<thead>
<tr>
<th>ID</th><th>Student</th><th>Exam</th><th>Year</th>
<th>Marks</th><th>%</th><th>Division</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php
$q=$db->query("SELECT q.*,s.candidate_name FROM $table q JOIN uin_register_student s ON s.id=q.student_id ORDER BY q.id DESC");
while($r=$q->fetch_assoc()){
echo "<tr>
<td>{$r['id']}</td>
<td>{$r['candidate_name']}</td>
<td>{$r['exam_name']}</td>
<td>{$r['passing_year']}</td>
<td>{$r['marks_obtained']}/{$r['total_marks']}</td>
<td>{$r['percentage']}</td>
<td>{$r['division']}</td>
<td class='action-btns'>
<a class='btn-edit' href='?edit={$r['id']}'>Edit</a>
<a class='btn-delete' href='?delete={$r['id']}' onclick='return confirm(\"Delete?\")'>Delete</a>
</td>
</tr>";
}
?>
</tbody>
</table>
</div>

</body>
</html>
