<?php
ob_start();
session_start();
include("script/settings.php");

/* ======================
   INSERT / UPDATE
====================== */
if(isset($_POST['save'])){

    $id        = $_POST['id'] ?? '';
    $date      = $_POST['date'];
    $title     = $_POST['title'];
    $link      = $_POST['link'];
    $is_new    = isset($_POST['is_new']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if($id==""){
        $sql="INSERT INTO announcements
              (date,title,link,is_new,is_active)
              VALUES
              ('$date','$title','$link','$is_new','$is_active')";
    }else{
        $sql="UPDATE announcements SET
              date='$date',
              title='$title',
              link='$link',
              is_new='$is_new',
              is_active='$is_active'
              WHERE id='$id'";
    }

    $db->query($sql);
    header("Location: announcements.php");
    exit;
}

/* ======================
   DELETE
====================== */
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $db->query("DELETE FROM announcements WHERE id=$id");
    header("Location: announcements.php");
    exit;
}

/* ======================
   EDIT
====================== */
$editData=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $res=$db->query("SELECT * FROM announcements WHERE id=$id");
    $editData=$res->fetch_assoc();
}

/* ======================
   LAYOUT
====================== */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Announcements</title>

<style>
/* ===== AXE HEADING ===== */
.axe-heading{
    position:relative;
    padding-left:14px;
    font-size:18px;
    font-weight:600;
    margin-bottom:15px;
}
.axe-heading::before{
    content:'';
    position:absolute;
    left:0;
    top:3px;
    width:4px;
    height:90%;
    background:#084298;
    border-radius:3px;
}

/* ===== FORM ===== */
form{
    background:#fff;
    padding:20px;
    border-radius:8px;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
    margin-bottom:30px;
}

.form-row.three-col{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
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
input[type=text],input[type=date]{
    padding:8px;
    border:1px solid #ccc;
    border-radius:6px;
}

.sc-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:10px 24px;
    border-radius:6px;
    cursor:pointer;
}
.sc-btn:hover{ background:#084298; }

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
}
th,td{
    padding:12px;
    border-bottom:1px solid #eee;
}
th{
    background:#0d6efd;
    color:#fff;
}
tr:hover{ background:#f9f9f9; }

/* ===== ACTION BUTTONS SAME ROW ===== */
.action-btns{
    display:flex;
    gap:6px;
}
.btn{
    padding:5px 10px;
    color:#fff;
    text-decoration:none;
    border-radius:4px;
    font-size:13px;
    white-space:nowrap;
}
.btn-edit{ background:#0d6efd; }
.btn-delete{ background:#dc3545; }

/* Responsive */
@media(max-width:900px){
    .form-row.three-col{ grid-template-columns:repeat(2,1fr); }
}
@media(max-width:600px){
    .form-row.three-col{ grid-template-columns:1fr; }
}
</style>
</head>

<body>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<!-- 🔥 HEADING INSIDE FORM -->
<h2 class="axe-heading">Announcements Form</h2>

<div class="form-row three-col">
    <div class="form-group">
        <label>Date</label>
        <input type="date" name="date" value="<?= $editData['date'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label>Title</label>
        <input type="text" name="title" value="<?= $editData['title'] ?? '' ?>" required>
    </div>

    <div class="form-group">
        <label>Link</label>
        <input type="text" name="link" value="<?= $editData['link'] ?? '' ?>">
    </div>
</div>

<label>
    <input type="checkbox" name="is_new" <?= isset($editData)&&$editData['is_new']?'checked':'' ?>>
    Is New
</label>
&nbsp;&nbsp;
<label>
    <input type="checkbox" name="is_active" <?= isset($editData)&&$editData['is_active']?'checked':'' ?>>
    Is Active
</label>

<br><br>
<button type="submit" name="save" class="sc-btn">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>

<div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.1);">

    <h2 class="axe-heading">Announcements Report</h2>

    <table>
    </table>

</div>

<table>
<tr>
<th>ID</th>
<th>Date</th>
<th>Title</th>
<th>Link</th>
<th>New</th>
<th>Active</th>
<th>Action</th>
</tr>

<?php
$q=$db->query("SELECT * FROM announcements ORDER BY date DESC");
while($row=$q->fetch_assoc()){
?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= date('d-m-Y',strtotime($row['date'])) ?></td>
<td><?= $row['title'] ?></td>
<td><?= $row['link'] ?></td>
<td><?= $row['is_new']?'Yes':'No' ?></td>
<td><?= $row['is_active']?'Yes':'No' ?></td>
<td>
    <!-- 🔥 EDIT + DELETE SAME ROW -->
    <div class="action-btns">
        <a class="btn btn-edit" href="?edit=<?= $row['id'] ?>">Edit</a>
        <a class="btn btn-delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this record?')">Delete</a>
    </div>
</td>
</tr>
<?php } ?>
</table>

</body>
</html>
