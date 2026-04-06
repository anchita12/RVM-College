<?php
ob_start();
session_start();

include("script/settings.php"); // $db connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

$table = "menu_master";

/* ================= INSERT / UPDATE ================= */
if(isset($_POST['save'])){
    $id         = $_POST['id'] ?? '';
    $parent_id  = $_POST['parent_id'] ?? 0;
    $title      = $_POST['title'] ?? '';
    $url        = $_POST['url'] ?? '';
    $icon       = $_POST['icon'] ?? '';
    $sort_order = $_POST['sort_order'] ?? 0;
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if($id==""){
        $sql="INSERT INTO $table (parent_id,title,url,icon,sort_order,is_active)
              VALUES ('$parent_id','$title','$url','$icon','$sort_order','$is_active')";
    }else{
        $sql="UPDATE $table SET
              parent_id='$parent_id',
              title='$title',
              url='$url',
              icon='$icon',
              sort_order='$sort_order',
              is_active='$is_active'
              WHERE id='$id'";
    }
    $db->query($sql);
    header("Location: menu_master.php");
    exit;
}

/* ================= DELETE ================= */
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE id=$id");
    header("Location: menu_master.php");
    exit;
}

/* ================= EDIT ================= */
$editData=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $res=$db->query("SELECT * FROM $table WHERE id=$id");
    $editData=$res->fetch_assoc();
}

/* ================= LAYOUT ================= */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Menu Master</title>

<style>
/* ===== CARD ===== */
.card-box{
    background:#fff;
    border-radius:10px;
    padding:20px;
    margin-bottom:25px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

/* ===== HEADING ===== */
.card-heading{
    font-size:18px;
    font-weight:700;
    color:#0d6efd;
    margin-bottom:15px;
    border-left:4px solid #0d6efd;
    padding-left:10px;
}

/* ===== FORM ===== */
.form-row{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:15px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

.form-group label{
    font-weight:600;
    margin-bottom:6px;
    font-size:14px;
}

.form-group input{
    padding:8px 10px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}

/* ===== WIDTH CONTROL ===== */
.parent-small{ width:90px; }
.title-wide{ flex:2; min-width:220px; }
.url-wide{ flex:2; min-width:220px; }
.icon-medium{ width:180px; }
.sort-xsmall{ width:80px; }

/* ===== CHECKBOX ===== */
.checkbox-row{
    display:flex;
    gap:20px;
    align-items:center;
}
.checkbox-row label{
    display:flex;
    align-items:center;
    gap:6px;
    font-weight:600;
}

/* ===== BUTTON ===== */
.save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:10px 30px;
    border-radius:6px;
    font-weight:600;
    cursor:pointer;
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
}
thead th{
    background:#0d6efd;
    color:#fff;
    padding:10px;
}
tbody td{
    padding:10px;
    border-bottom:1px solid #eee;
}
.action-btns a{
    padding:5px 10px;
    border-radius:5px;
    color:#fff;
    text-decoration:none;
    font-size:13px;
}
.edit-btn{background:#0d6efd}
.delete-btn{background:#dc3545}

@media(max-width:768px){
    .form-row{flex-direction:column}
    .parent-small,.sort-xsmall,.icon-medium{width:100%}
}
</style>
</head>

<body>

<!-- ================= FORM ================= -->
<div class="card-box">
<div class="card-heading">Menu Master Form</div>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<!-- ROW 1 -->
<div class="form-row">
    <div class="form-group parent-small">
        <label>Parent ID</label>
        <input type="number" name="parent_id" value="<?= $editData['parent_id'] ?? 0 ?>">
    </div>

    <div class="form-group title-wide">
        <label>Title</label>
        <input type="text" name="title" value="<?= $editData['title'] ?? '' ?>" required>
    </div>

    <div class="form-group url-wide">
        <label>URL</label>
        <input type="text" name="url" value="<?= $editData['url'] ?? '' ?>">
    </div>

    <div class="form-group icon-medium">
        <label>Icon</label>
        <input type="text" name="icon" value="<?= $editData['icon'] ?? '' ?>">
    </div>

    <div class="form-group sort-xsmall">
        <label>Sort</label>
        <input type="number" name="sort_order" value="<?= $editData['sort_order'] ?? 0 ?>">
    </div>
</div>

<!-- ROW 2 -->
<div class="checkbox-row">
    <label>
        <input type="checkbox" name="is_active"
        <?= (isset($editData['is_active']) && $editData['is_active']==1)?'checked':'' ?>>
        Is Active
    </label>
</div>

<br>

<button class="save-btn" name="save">
<?= isset($editData) ? 'Update' : 'Save' ?>
</button>
</form>
</div>

<!-- ================= REPORT ================= -->
<div class="card-box">
<div class="card-heading">Menu Master Report</div>

<table>
<thead>
<tr>
<th>ID</th><th>Parent</th><th>Title</th><th>URL</th>
<th>Icon</th><th>Sort</th><th>Active</th><th>Action</th>
</tr>
</thead>
<tbody>
<?php
$res=$db->query("SELECT * FROM $table ORDER BY sort_order ASC");
while($row=$res->fetch_assoc()){
?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['parent_id'] ?></td>
<td><?= $row['title'] ?></td>
<td><?= $row['url'] ?></td>
<td><?= $row['icon'] ?></td>
<td><?= $row['sort_order'] ?></td>
<td><?= $row['is_active']?'Yes':'No' ?></td>
<td class="action-btns">
<a class="edit-btn" href="?edit=<?= $row['id'] ?>">Edit</a>
<a class="delete-btn" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

</body>
</html>
