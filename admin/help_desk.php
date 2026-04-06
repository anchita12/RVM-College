<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================= INSERT / UPDATE ================= */
if (isset($_POST['save'])) {

    $id         = $_POST['id'] ?? '';
    $title      = $_POST['title'] ?? '';
    $icon       = $_POST['icon'] ?? '';
    $link       = $_POST['link'] ?? '';
    $sort_order = $_POST['sort_order'] ?? 0;
    $is_new     = isset($_POST['is_new']) ? 1 : 0;
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if ($id == "") {
        $sql = "INSERT INTO student_corner
                (title, icon, link, is_new, sort_order, is_active)
                VALUES
                ('$title','$icon','$link','$is_new','$sort_order','$is_active')";
    } else {
        $sql = "UPDATE student_corner SET
                title='$title',
                icon='$icon',
                link='$link',
                is_new='$is_new',
                sort_order='$sort_order',
                is_active='$is_active'
                WHERE id='$id'";
    }

    $db->query($sql);
    header("Location: student_corner.php");
    exit;
}

/* ================= DELETE ================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM student_corner WHERE id=$id");
    header("Location: student_corner.php");
    exit;
}

/* ================= EDIT ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $db->query("SELECT * FROM student_corner WHERE id=$id");
    $editData = $res->fetch_assoc();
}

/* ================= LAYOUT ================= */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Corner</title>

<style>
/* ===== RED AXE HEADING ===== */
.axe-heading{
    position:relative;
    padding-left:14px;
    font-size:18px;
    font-weight:600;
    margin-bottom:18px;
}
.axe-heading::before{
    content:'';
    position:absolute;
    left:0;
    top:3px;
    width:4px;
    height:90%;
    background:#0d6efd;
    border-radius:3px;
}

/* ===== FORM CARD ===== */
form{
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
    margin-bottom:35px;
}

/* ===== ALL INPUTS ONE ROW ===== */
.form-row.one-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:15px;
}

.form-group{
    display:flex;
    flex-direction:column;
}
label{
    font-weight:600;
    margin-bottom:6px;
}
input[type=text],input[type=number]{
    padding:8px 10px;
    border:1px solid #ccc;
    border-radius:6px;
}

/* CHECKBOX ROW */
.checkbox-row{
    display:flex;
    gap:30px;
    margin:10px 0 20px;
}

.sc-save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:10px 24px;
    border-radius:6px;
    cursor:pointer;
}
.sc-save-btn:hover{ background:#084298; }

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
}
th,td{
    padding:12px 14px;
    border-bottom:1px solid #eee;
}
th{
    background:#0d6efd;
    color:#fff;
}

a.btn{
    padding:5px 12px;
    color:#fff;
    border-radius:4px;
    text-decoration:none;
    font-size:13px;
}
.btn-edit{ background:#0d6efd; }
.btn-delete{ background:#dc3545; }

/* Responsive */
@media(max-width:1000px){
    .form-row.one-row{ grid-template-columns:repeat(2,1fr); }
}
@media(max-width:600px){
    .form-row.one-row{ grid-template-columns:1fr; }
}
</style>
</head>

<body>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<!-- 🔴 HEADING INSIDE FORM -->
<h2 class="axe-heading">Student Corner Form</h2>

<!-- 🔥 ALL INPUTS SAME ROW -->
<div class="form-row one-row">
    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="<?= $editData['title'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label>Icon</label>
        <input type="text" name="icon" value="<?= $editData['icon'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Link</label>
        <input type="text" name="link" value="<?= $editData['link'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label>Sort Order</label>
        <input type="number" name="sort_order" value="<?= $editData['sort_order'] ?? 0 ?>">
    </div>
</div>

<!-- CHECKBOX SAME LINE -->
<div class="checkbox-row">
    <label>
        <input type="checkbox" name="is_new" <?= isset($editData)&&$editData['is_new']?'checked':'' ?>>
        Is New
    </label>
    <label>
        <input type="checkbox" name="is_active" <?= isset($editData)&&$editData['is_active']?'checked':'' ?>>
        Is Active
    </label>
</div>

<button type="submit" name="save" class="sc-save-btn">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>

<!-- 🔴 REPORT -->
<div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);">
<h2 class="axe-heading">Student Corner Report</h2>

<table>
<tr>
<th>ID</th><th>Title</th><th>Icon</th><th>Link</th>
<th>New</th><th>Sort</th><th>Active</th><th>Action</th>
</tr>

<?php
$q=$db->query("SELECT * FROM student_corner ORDER BY sort_order");
while($row=$q->fetch_assoc()){
?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['title'] ?></td>
<td><?= $row['icon'] ?></td>
<td><?= $row['link'] ?></td>
<td><?= $row['is_new']?'Yes':'No' ?></td>
<td><?= $row['sort_order'] ?></td>
<td><?= $row['is_active']?'Yes':'No' ?></td>
<td>
<a class="btn btn-edit" href="?edit=<?= $row['id'] ?>">Edit</a>
<a class="btn btn-delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php } ?>
</table>
</div>

</body>
</html>
