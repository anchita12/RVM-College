<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ================= FILTERS ================= */
$course_id = $_GET['course_id'] ?? '';
$category  = $_GET['category'] ?? '';
$status    = $_GET['status'] ?? '';
$limit     = $_GET['limit'] ?? 25;

/* ================= SORTING ================= */
$sort  = $_GET['sort'] ?? 'uin';
$order = $_GET['order'] ?? 'ASC';

$allowed_sort = [
    'uin' => 'urs.uin',
    'registration' => 'urs.registration_no',
    'name' => 'urs.candidate_name',
    'course' => 'cd.class_description',
    'dob' => 'urs.dob',
    'category' => 'cat.category_name',
    'status' => 'uin_status'
];

if(!isset($allowed_sort[$sort])) $sort = 'uin';
$order = ($order === 'DESC') ? 'DESC' : 'ASC';

/* ================= PAGINATION LIMIT ================= */
$allowed_limits = [25,50,100,200,500,'all'];
if(!in_array($limit,$allowed_limits)) $limit = 25;

/* ================= PAGINATION ================= */
$page = isset($_GET['page']) ? max((int)$_GET['page'],1) : 1;
$offset = ($limit === 'all') ? 0 : ($page - 1) * $limit;

/* ================= DROPDOWNS ================= */
$course_q = $db->query("
    SELECT sno, class_description
    FROM class_detail
    ORDER BY 
        ABS(group_short) ASC,
        semester ASC
");

$cat_q    = $db->query("SELECT categories_sno,category_name FROM categories ORDER BY category_name");

/* ================= WHERE ================= */
$where = "WHERE 1";
if($course_id!='') $where.=" AND urs.course_applied_for='".$db->real_escape_string($course_id)."'";
if($category!='')  $where.=" AND urs.category='".$db->real_escape_string($category)."'";
if($status!=''){
    if($status=='Pending')  $where.=" AND (urs.uin IS NULL OR urs.uin='')";
    if($status=='Complete') $where.=" AND (urs.uin IS NOT NULL AND urs.uin!='')";
}

/* ================= COUNT ================= */
$total = $db->query("SELECT COUNT(*) total FROM uin_register_student urs $where")
            ->fetch_assoc()['total'];
$total_pages = ($limit==='all') ? 1 : ceil($total/$limit);

/* ================= DATA ================= */
$sql = "
SELECT 
    urs.uin,
    urs.registration_no,
    urs.candidate_name,
    urs.fathers_name,
    urs.dob,
    cd.class_description course_name,
    cat.category_name,
    CASE 
        WHEN urs.uin IS NULL OR urs.uin='' THEN 'Pending'
        ELSE 'Complete'
    END AS uin_status
FROM uin_register_student urs
LEFT JOIN class_detail cd ON cd.sno = urs.course_applied_for
LEFT JOIN categories cat ON cat.categories_sno = urs.category
$where
ORDER BY 
    (urs.uin IS NULL OR urs.uin='') ASC,
    {$allowed_sort[$sort]} $order
";

if($limit!=='all') $sql.=" LIMIT $limit OFFSET $offset";
$result = $db->query($sql);

function sort_link($label,$col){
    global $sort,$order;
    $newOrder = ($sort==$col && $order=='ASC') ? 'DESC' : 'ASC';
    $params = $_GET;
    $params['sort']=$col;
    $params['order']=$newOrder;
    return "<a href='?".http_build_query($params)."' style='color:#fff;text-decoration:none'>$label</a>";
}

if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>UIN Student Report</title>
<style>
body{background:#f4f6fb;font-family:'Segoe UI',sans-serif}
.card-box{background:#fff;padding:25px;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.08)}
.filter-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
select{padding:10px;border-radius:8px;border:1px solid #ced4da}
.btn-filter{background:linear-gradient(135deg,#0d6efd,#6610f2);color:#fff;border:none;padding:10px 26px;border-radius:30px;font-weight:600}
table{width:100%;border-collapse:collapse}
th{background:#0d6efd;color:#fff;padding:12px}
td{padding:10px;border-bottom:1px solid #eee}
.badge-pending{color:#ffc107;font-weight:700}
.badge-complete{color:#198754;font-weight:700}
.pagination{display:flex;justify-content:center;margin-top:20px;gap:6px}
.pagination a{padding:8px 14px;border-radius:8px;border:1px solid #0d6efd;color:#0d6efd;text-decoration:none}
.pagination a.active,.pagination a:hover{background:#0d6efd;color:#fff}
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
</style>
</head>

<body>

<div class="card-box">
<h2 class="card-heading"> Registered Students Report</h2>

<form method="get" class="filter-row">
    <select name="course_id"><option value="">🎓 All Courses</option>
        <?php while($c=$course_q->fetch_assoc()){ ?>
            <option value="<?= $c['sno'] ?>" <?= ($course_id==$c['sno'])?'selected':'' ?>><?= $c['class_description'] ?></option>
        <?php } ?>
    </select>

    <select name="category"><option value="">📂 All Categories</option>
        <?php while($cat=$cat_q->fetch_assoc()){ ?>
            <option value="<?= $cat['categories_sno'] ?>" <?= ($category==$cat['categories_sno'])?'selected':'' ?>><?= $cat['category_name'] ?></option>
        <?php } ?>
    </select>

    <select name="status">
        <option value="">📌 All Status</option>
        <option value="Pending" <?= ($status=='Pending')?'selected':'' ?>>Pending</option>
        <option value="Complete" <?= ($status=='Complete')?'selected':'' ?>>Complete</option>
    </select>

    <select name="limit">
        <?php foreach([25,50,100,200,500,'all'] as $l){ ?>
            <option value="<?= $l ?>" <?= ($limit==$l)?'selected':'' ?>><?= strtoupper($l) ?></option>
        <?php } ?>
    </select>

    <button class="btn-filter">Apply</button>
</form>

<table>
<thead>
<tr>
    <th>S.No</th>
    <th><?= sort_link('UIN','uin') ?></th>
    <th><?= sort_link('Registration','registration') ?></th>
    <th><?= sort_link('Candidate','name') ?></th>
    <th><?= sort_link('Course','course') ?></th>
    <th><?= sort_link('DOB','dob') ?></th>
    <th><?= sort_link('Category','category') ?></th>
    <th><?= sort_link('Status','status') ?></th>
</tr>
</thead>
<tbody>
<?php 
$sn = ($limit === 'all') ? 1 : ($offset + 1);
while($r=$result->fetch_assoc()){ ?>
<tr>
    <td><?= $sn++ ?></td>
    <td><?= $r['uin'] ?: '-' ?></td>
    <td><?= $r['registration_no'] ?></td>
    <td><?= $r['candidate_name'] ?></td>
    <td><?= $r['course_name'] ?></td>
    <td><?= $r['dob'] ?></td>
    <td><?= $r['category_name'] ?></td>
    <td class="<?= $r['uin_status']=='Pending'?'badge-pending':'badge-complete' ?>">
        <?= $r['uin_status'] ?>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<?php if($limit!=='all'){ ?>
<div class="pagination">
<?php for($i=1;$i<=$total_pages;$i++){ ?>
<a class="<?= ($i==$page)?'active':'' ?>"
   href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>">
<?= $i ?></a>
<?php } ?>
</div>
<?php } ?>

</div>
</body>
</html>
