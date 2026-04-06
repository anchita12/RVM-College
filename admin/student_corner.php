<?php
ob_start();
session_start();

/* ==============================
   DATABASE & SETTINGS
================================ */
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ==============================
   INSERT / UPDATE
================================ */
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

/* ==============================
   DELETE
================================ */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM student_corner WHERE id=$id");
    header("Location: student_corner.php");
    exit;
}

/* ==============================
   EDIT
================================ */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $db->query("SELECT * FROM student_corner WHERE id=$id");
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
<title>Student Corner</title>

<style>

/* ===== CARD ===== */
.card-box{
    background:#fff;
    border-radius:8px;
    padding:20px;
    margin-bottom:30px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

/* ===== HEADING ===== */
.card-heading{
    position:relative;
    padding-left:14px;
    margin-bottom:18px;
    font-size:18px;
    font-weight:600;
    color:#222;
}
.card-heading::before{
    content:'';
    position:absolute;
    left:0;
    top:3px;
    width:4px;
    height:85%;
    background:#084298;
    border-radius:3px;
}

/* ===== FORM ===== */
.form-row{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:14px;
}
.form-group{
    flex:1;
    min-width:200px;
}
.form-group label{
    font-size:14px;
    font-weight:600;
    margin-bottom:6px;
    display:block;
}
.form-group input{
    width:100%;
    padding:9px 10px;
    font-size:14px;
    border:1px solid #ccc;
    border-radius:6px;
}
.checkbox-row{
    display:flex;
    gap:20px;
    margin:10px 0 18px;
    font-size:14px;
}

/* ===== BUTTON ===== */
.save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:10px 26px;
    font-size:14px;
    font-weight:600;
    border-radius:6px;
    cursor:pointer;
}
.save-btn:hover{
    background:#084298;
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
}
thead th{
    background:#0d6efd;
    color:#fff;
    padding:12px;
    font-size:14px;
    text-align:left;
}
tbody td{
    padding:11px;
    font-size:16px;
    border-bottom:1px solid #eee;
}
tbody tr:hover{
    background:#fafafa;
}

/* ===== ACTION ===== */
.action-btns{
    display:flex;
    gap:8px;
}
.btn{
    padding:6px 12px;
    font-size:13px;
    border-radius:5px;
    color:#fff;
    text-decoration:none;
}
.btn-edit{ background:#0d6efd; }
.btn-delete{ background:#dc3545; }

</style>
</head>

<body>

<!-- ================= FORM ================= -->
<div class="card-box">
    <div class="card-heading">Student Corner Form</div>

    <form method="post">
        <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

        <div class="form-row">
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

        <div class="checkbox-row">
            <label><input type="checkbox" name="is_new" <?= isset($editData)&&$editData['is_new']?'checked':'' ?>> Is New</label>
            <label><input type="checkbox" name="is_active" <?= isset($editData)&&$editData['is_active']?'checked':'' ?>> Is Active</label>
        </div>

        <button type="submit" name="save" class="save-btn">
            <?= isset($editData) ? 'Update' : 'Save' ?>
        </button>
    </form>
</div>

<!-- ================= REPORT ================= -->
<div class="card-box">
    <div class="card-heading">Student Corner Report</div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Icon</th>
                <th>Link</th>
                <th>New</th>
                <th>Sort</th>
                <th>Active</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $res = $db->query("SELECT * FROM student_corner ORDER BY sort_order");
        while($row = $res->fetch_assoc()){
        ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['title'] ?></td>
                <td><?= $row['icon'] ?></td>
                <td><?= $row['link'] ?></td>
                <td><?= $row['is_new'] ? 'Yes' : 'No' ?></td>
                <td><?= $row['sort_order'] ?></td>
                <td><?= $row['is_active'] ? 'Yes' : 'No' ?></td>
                <td>
                    <div class="action-btns">
                        <a class="btn btn-edit" href="?edit=<?= $row['id'] ?>">Edit</a>
                        <a class="btn btn-delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>
