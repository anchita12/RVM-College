<?php
ob_start();
session_start();

include("script/settings.php"); // DB connection

error_reporting(E_ALL);
ini_set('display_errors', 1);

$table = "uin_student_payment";

// INSERT / UPDATE
if (isset($_POST['save'])) {
    $id = $_POST['id'] ?? '';

    $fields = [
        'student_id','registration_no','order_id','transaction_id','amount','payment_status','payment_date','gateway_name','response_json'
    ];

    $data = [];
    foreach($fields as $f){
        $data[$f] = $db->real_escape_string($_POST[$f] ?? '');
    }

    $sets = [];
    foreach($fields as $f) $sets[] = "$f='{$data[$f]}'";
    $sets[] = "created_at=NOW()"; // optional timestamp

    if($id==""){
        $sql = "INSERT INTO $table SET ".implode(',',$sets);
    } else {
        $sql = "UPDATE $table SET ".implode(',',$sets)." WHERE id='$id'";
    }

    if(!$db->query($sql)) die("DB Error: ".$db->error);
    header("Location: uin_student_payment.php");
    exit;
}

// DELETE
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $db->query("DELETE FROM $table WHERE id=$id");
    header("Location: uin_student_payment.php");
    exit;
}

// EDIT
$editData = null;
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $editData = $db->query("SELECT * FROM $table WHERE id=$id")->fetch_assoc();
}

// Sidebar & Header
if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Payment</title>
<style>
.card-box{background:#fff;padding:25px;margin-bottom:30px;border-radius:8px;box-shadow:0 6px 15px rgba(0,0,0,0.1);}
.card-heading{font-size:22px;font-weight:700;color:#0d6efd;margin-bottom:20px;border-left:5px solid #0d6efd;padding-left:15px;}
.form-row{display:flex;gap:15px;flex-wrap:wrap;margin-bottom:15px;}
.form-group{flex:1;min-width:200px;display:flex;flex-direction:column;}
.form-group label{font-weight:600;margin-bottom:6px;font-size:14px;color:#333;}
.form-group input,.form-group select{padding:8px 12px;font-size:14px;border:1px solid #ccc;border-radius:6px;transition:0.3s;}
.form-group input:focus,.form-group select:focus{border-color:#0d6efd;box-shadow:0 0 6px rgba(13,110,253,0.3);outline:none;}
.save-btn{background:#0d6efd;color:#fff;border:none;padding:12px 36px;font-size:16px;font-weight:600;border-radius:8px;cursor:pointer;transition:0.3s;}
.save-btn:hover{background:#084298;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
thead th{background:#0d6efd;color:#fff;padding:12px 10px;font-size:14px;text-align:left;}
tbody td{padding:10px;border-bottom:1px solid #eee;font-size:14px;}
tbody tr:hover{background:#f1f5f9;}
.action-btns{display:flex;gap:8px;}
.action-btns a{padding:6px 12px;font-size:13px;border-radius:5px;color:#fff;text-decoration:none;transition:0.3s;}
.action-btns .btn-edit{background:#0d6efd;}
.action-btns .btn-edit:hover{background:#084298;}
.action-btns .btn-delete{background:#dc3545;}
.action-btns .btn-delete:hover{background:#a71d2a;}
@media(max-width:1200px){.form-row{flex-wrap:wrap;}}
@media(max-width:768px){.form-row{flex-direction:column;}}
</style>
</head>
<body>

<div class="card-box">
    <div class="card-heading"><?= isset($editData) ? 'Edit Student Payment' : 'Add Student Payment' ?></div>
    <form method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editData['id'] ?? '') ?>">

        <?php
        $fields = [
            ['label'=>'Student ID','name'=>'student_id','type'=>'text'],
            ['label'=>'Registration No','name'=>'registration_no','type'=>'text'],
            ['label'=>'Order ID','name'=>'order_id','type'=>'text'],
            ['label'=>'Transaction ID','name'=>'transaction_id','type'=>'text'],
            ['label'=>'Amount','name'=>'amount','type'=>'number'],
            ['label'=>'Payment Status','name'=>'payment_status','type'=>'select','options'=>[''=>'Select','Pending'=>'Pending','Success'=>'Success','Failed'=>'Failed']],
            ['label'=>'Payment Date','name'=>'payment_date','type'=>'date'],
            ['label'=>'Gateway Name','name'=>'gateway_name','type'=>'text'],
            ['label'=>'Response JSON','name'=>'response_json','type'=>'text'],
        ];

        $i=0; echo '<div class="form-row">';
        foreach($fields as $f){
            $val = htmlspecialchars($editData[$f['name']] ?? '');
            echo '<div class="form-group">';
            echo '<label>'.$f['label'].'</label>';
            if(isset($f['type']) && $f['type']=='select'){
                echo '<select name="'.$f['name'].'">';
                foreach($f['options'] as $k=>$v){
                    $selected = ($val==$k)?'selected':'';
                    echo "<option value='$k' $selected>$v</option>";
                }
                echo '</select>';
            } else {
                $type = $f['type'] ?? 'text';
                echo '<input type="'.$type.'" name="'.$f['name'].'" value="'.$val.'">';
            }
            echo '</div>';
            $i++;
            if($i%4==0) echo '</div><div class="form-row">';
        }
        echo '</div>';
        ?>
        <button type="submit" name="save" class="save-btn"><?= isset($editData) ? "Update" : "Save" ?></button>
    </form>
</div>

<div class="card-box">
    <div class="card-heading">Student Payment Report</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student ID</th>
                <th>Reg No</th>
                <th>Order ID</th>
                <th>Transaction ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Gateway</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res = $db->query("SELECT id, student_id, registration_no, order_id, transaction_id, amount, payment_status, payment_date, gateway_name FROM $table ORDER BY id DESC");
            while($row=$res->fetch_assoc()){
                echo '<tr>';
                echo '<td>'.$row['id'].'</td>';
                echo '<td>'.htmlspecialchars($row['student_id']).'</td>';
                echo '<td>'.htmlspecialchars($row['registration_no']).'</td>';
                echo '<td>'.htmlspecialchars($row['order_id']).'</td>';
                echo '<td>'.htmlspecialchars($row['transaction_id']).'</td>';
                echo '<td>'.htmlspecialchars($row['amount']).'</td>';
                echo '<td>'.htmlspecialchars($row['payment_status']).'</td>';
                echo '<td>'.htmlspecialchars($row['payment_date']).'</td>';
                echo '<td>'.htmlspecialchars($row['gateway_name']).'</td>';
                echo '<td class="action-btns">
                        <a class="btn-edit" href="?edit='.$row['id'].'">Edit</a>
                        <a class="btn-delete" href="?delete='.$row['id'].'" onclick="return confirm(\'Are you sure?\')">Delete</a>
                      </td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
