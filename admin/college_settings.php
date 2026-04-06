<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors',1);

$table = "college_settings";

/* ================= INSERT / UPDATE ================= */
if(isset($_POST['save'])){
    $id = $_POST['id'] ?? '';

    $fields = [
        'short_name','college_name','tagline','naac_text','affiliated_text',
        'ugc_text','iso_text','established','email','phone',
        'logo','p_logo','background_image','p_background'
    ];

    $data=[];
    foreach($fields as $f){
        $data[$f] = $db->real_escape_string($_POST[$f] ?? '');
    }

    if($id==""){
        $sql="INSERT INTO $table SET
            short_name='{$data['short_name']}',
            college_name='{$data['college_name']}',
            tagline='{$data['tagline']}',
            naac_text='{$data['naac_text']}',
            affiliated_text='{$data['affiliated_text']}',
            ugc_text='{$data['ugc_text']}',
            iso_text='{$data['iso_text']}',
            established='{$data['established']}',
            email='{$data['email']}',
            phone='{$data['phone']}',
            logo='{$data['logo']}',
            p_logo='{$data['p_logo']}',
            background_image='{$data['background_image']}',
            p_background='{$data['p_background']}'
        ";
    }else{
        $sql="UPDATE $table SET
            short_name='{$data['short_name']}',
            college_name='{$data['college_name']}',
            tagline='{$data['tagline']}',
            naac_text='{$data['naac_text']}',
            affiliated_text='{$data['affiliated_text']}',
            ugc_text='{$data['ugc_text']}',
            iso_text='{$data['iso_text']}',
            established='{$data['established']}',
            email='{$data['email']}',
            phone='{$data['phone']}',
            logo='{$data['logo']}',
            p_logo='{$data['p_logo']}',
            background_image='{$data['background_image']}',
            p_background='{$data['p_background']}'
            WHERE id='$id'
        ";
    }

    if(!$db->query($sql)){
        die("DB Error : ".$db->error);
    }
    header("Location: college_settings.php");
    exit;
}

/* ================= DELETE ================= */
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE id=$id");
    header("Location: college_settings.php");
    exit;
}

/* ================= EDIT ================= */
$editData=null;
if(isset($_GET['edit'])){
    $id=(int)$_GET['edit'];
    $res=$db->query("SELECT * FROM $table WHERE id=$id");
    $editData=$res->fetch_assoc();
}

/* sidebar & header */
if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>College Settings</title>

<style>
body{
    background:#f4f6f9;
    font-family:Arial;
}
.card{
    background:#fff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 5px 15px rgba(0,0,0,.1);
    margin-bottom:30px;
}
.card h2{
    margin:0 0 20px;
    color:#0d6efd;
}

/* ===== FORM ===== */
.form-row{
    display:flex;
    flex-wrap:wrap;
    gap:15px;
    margin-bottom:15px;
}
.form-group{
    width:25%;
    min-width:220px;
    display:flex;
    flex-direction:column;
}
.form-group label{
    font-size:14px;
    font-weight:600;
    margin-bottom:6px;
}
.form-group input,
.form-group textarea{
    padding:9px 12px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}
textarea{
    resize:vertical;
    min-height:70px;
}

.save-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:12px 40px;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
}
.save-btn:hover{background:#084298;}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:collapse;
}
th{
    background:#0d6efd;
    color:#fff;
    padding:10px;
    font-size:14px;
}
td{
    padding:10px;
    border-bottom:1px solid #eee;
    font-size:14px;
}
.action a{
    padding:5px 10px;
    border-radius:5px;
    color:#fff;
    text-decoration:none;
    font-size:13px;
}
.edit{background:#0d6efd;}
.del{background:#dc3545;}

/* ===== MOBILE ===== */
@media(max-width:768px){
    .form-group{width:100%;}
}
</style>
</head>

<body>

<div class="card">
<h2><?= isset($editData)?'Edit College Settings':'Add College Settings' ?></h2>

<form method="post">
<input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">

<div class="form-row">
    <div class="form-group"><label>Short Name</label><input name="short_name" value="<?= $editData['short_name'] ?? '' ?>"></div>
    <div class="form-group"><label>College Name</label><input name="college_name" value="<?= $editData['college_name'] ?? '' ?>"></div>
    <div class="form-group"><label>Tagline</label><input name="tagline" value="<?= $editData['tagline'] ?? '' ?>"></div>
    <div class="form-group"><label>Established</label><input name="established" value="<?= $editData['established'] ?? '' ?>"></div>
</div>

<div class="form-row">
    <div class="form-group"><label>Email</label><input name="email" value="<?= $editData['email'] ?? '' ?>"></div>
    <div class="form-group"><label>Phone</label><input name="phone" value="<?= $editData['phone'] ?? '' ?>"></div>
    <div class="form-group"><label>NAAC Text</label><textarea name="naac_text"><?= $editData['naac_text'] ?? '' ?></textarea></div>
    <div class="form-group"><label>Affiliated Text</label><textarea name="affiliated_text"><?= $editData['affiliated_text'] ?? '' ?></textarea></div>
</div>

<div class="form-row">
    <div class="form-group"><label>UGC Text</label><textarea name="ugc_text"><?= $editData['ugc_text'] ?? '' ?></textarea></div>
    <div class="form-group"><label>ISO Text</label><textarea name="iso_text"><?= $editData['iso_text'] ?? '' ?></textarea></div>
    <div class="form-group"><label>Logo Path</label><input name="logo" value="<?= $editData['logo'] ?? '' ?>"></div>
    <div class="form-group"><label>Print Logo</label><input name="p_logo" value="<?= $editData['p_logo'] ?? '' ?>"></div>
</div>

<div class="form-row">
    <div class="form-group"><label>Background Image</label><input name="background_image" value="<?= $editData['background_image'] ?? '' ?>"></div>
    <div class="form-group"><label>Print Background</label><input name="p_background" value="<?= $editData['p_background'] ?? '' ?>"></div>
</div>

<button class="save-btn" name="save"><?= isset($editData)?'Update':'Save' ?></button>
</form>
</div>

<div class="card">
<h2>College Settings List</h2>

<table>
<tr>
<th>ID</th><th>Short Name</th><th>College Name</th>
<th>Email</th><th>Phone</th><th>Action</th>
</tr>

<?php
$res=$db->query("SELECT * FROM $table ORDER BY id DESC");
while($r=$res->fetch_assoc()){
?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= $r['short_name'] ?></td>
<td><?= $r['college_name'] ?></td>
<td><?= $r['email'] ?></td>
<td><?= $r['phone'] ?></td>
<td class="action">
<a class="edit" href="?edit=<?= $r['id'] ?>">Edit</a>
<a class="del" href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php } ?>
</table>
</div>

</body>
</html>
