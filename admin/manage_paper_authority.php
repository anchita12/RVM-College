<?php
ob_start();
session_start();
include("script/settings.php");

// TODO: Enforce Admin Check here if needed
// if($_SESSION['role'] != 'admin') die("Access Denied");

$msg = '';

// Handle Validation/Save via Post
if(isset($_POST['save_authority'])) {
    $user_id = mysqli_real_escape_string($db, $_POST['user_id']);
    $class_id = mysqli_real_escape_string($db, $_POST['class_id']);
    $paper_ids = $_POST['paper_ids'] ?? [];

    if(!empty($user_id) && !empty($class_id)) {
        // 1. Remove existing authority for this user & class
        //    (We assume if you uncheck everything, you lose authority for this class)
        //    However, to avoid deleting OTHER class authorities, we filter by class_id too.
        
        $delSql = "DELETE FROM exam_paper_authority WHERE user_id='$user_id' AND class_id='$class_id'";
        mysqli_query($db, $delSql);

        // 2. Insert new ones
        if(!empty($paper_ids)) {
            $stmt = $db->prepare("INSERT INTO exam_paper_authority (user_id, class_id, paper_id, can_enter) VALUES (?, ?, ?, 1)");
            foreach($paper_ids as $pid) {
                $pid = intval($pid);
                $stmt->bind_param("iii", $user_id, $class_id, $pid);
                $stmt->execute();
            }
            $stmt->close();
        }
        $msg = '<div class="alert alert-success">Authority Updated Successfully!</div>';
    } else {
        $msg = '<div class="alert alert-danger">Please select User and Class.</div>';
    }
}

// Fetch Users (Teachers)
$users = [];
$uQ = mysqli_query($db, "SELECT id, username FROM users WHERE status=1 ORDER BY username ASC");
while($r = mysqli_fetch_assoc($uQ)) {
    $users[] = $r;
}

// Fetch Classes
$classes = [];
$cQ = mysqli_query($db, "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
while($r = mysqli_fetch_assoc($cQ)) {
    $classes[] = $r;
}

if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Paper Authority</title>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<style>
/* Reuse styles from add_suubject.php or general layout */
body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; }
.card-box { background:#fff; border-radius:12px; padding:25px; margin-bottom:30px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
.card-heading { font-size:20px; font-weight:700; color:#0d6efd; margin-bottom:20px; border-left: 5px solid #0d6efd; padding-left: 15px; }
.form-group { margin-bottom: 20px; }
.form-group label { font-weight: 600; display: block; margin-bottom: 8px; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
.btn-save { background: #0d6efd; color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-size: 16px; }
.btn-save:hover { background: #0b5ed7; }
.paper-list-container { max-height: 400px; overflow-y: auto; border: 1px solid #eee; padding: 15px; border-radius: 6px; }
.paper-item { padding: 8px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; }
.paper-item:last-child { border-bottom: none; }
.paper-item input { margin-right: 12px; width: 20px; height: 20px; }
.alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
.alert-success { background-color: #d4edda; color: #155724; }
.alert-danger { background-color: #f8d7da; color: #721c24; }
</style>
</head>
<body>

<div style="padding: 20px;">
    <?= $msg ?>

    <div class="card-box">
        <div class="card-heading">Manage Paper Authority</div>
        <p class="text-muted">Select a User and a Class to assign paper entry rights.</p>

        <form method="post" id="authorityForm">
            <div class="row" style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label>Select User (Teacher)</label>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <option value="">-- Select User --</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= $user['username'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label>Select Class</label>
                    <select name="class_id" id="class_id" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach($classes as $cls): ?>
                            <option value="<?= $cls['sno'] ?>"><?= $cls['class_description'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="paperSection" style="display: none;">
                <h5 style="margin: 20px 0 10px; font-weight: 600;">Assign Papers</h5>
                <div class="paper-list-container">
                    <div id="paperListLoader" style="text-align: center; padding: 20px; color: #666;">Select User and Class to load papers...</div>
                    <div id="paperListContent"></div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" name="save_authority" class="btn-save">💾 Save Authority</button>
                    <span id="saveStatus" style="margin-left: 10px; font-weight: bold; color: green; display: none;">Saved!</span>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // When User or Class changes, fetch papers and authority status
    $('#user_id, #class_id').change(function() {
        loadPapers();
    });

    function loadPapers() {
        var userId = $('#user_id').val();
        var classId = $('#class_id').val();

        if(classId) {
            $('#paperSection').show();
            // Show loader inside content if user hasn't been selected yet? 
            // Actually we need both to fetch meaningful "checked" status.
            // If only class selected -> show all papers unchecked (or disable checkboxes until user selected?)
            // Requirement: "Drop down me aa jana chahiye".
            
            if(userId && classId) {
                 $('#paperListContent').html('<div style="text-align:center;">Loading...</div>');
                 
                 $.ajax({
                     url: 'manage_paper_authority.php', // Self-calling AJAX
                     type: 'POST',
                     data: {
                         action: 'fetch_papers',
                         user_id: userId,
                         class_id: classId
                     },
                     success: function(resp) {
                         $('#paperListContent').html(resp);
                     }
                 });
            } else if (classId) {
                 // Suggest selecting user
                 $('#paperListContent').html('<div style="color:red; text-align:center;">Please Select a User to Assign Authority.</div>');
            }
        } else {
            $('#paperSection').hide();
        }
    }
});
</script>

<?php
// Handle AJAX Fetch
if(isset($_POST['action']) && $_POST['action'] == 'fetch_papers') {
    // Clean buffer to avoid echoing sidebar/header in AJAX response
    ob_clean(); 
    
    $uid = $_POST['user_id'];
    $cid = $_POST['class_id'];

    // 1. Get All Papers for Class
    $papers = [];
    $pq = mysqli_query($db, "SELECT * FROM add_subject_papers WHERE class_id='$cid' ORDER BY paper_code ASC");
    while($row = mysqli_fetch_assoc($pq)) {
        $papers[] = $row;
    }

    // 2. Get Existing Authority for User & Class
    $authorized_papers = [];
    $authQ = mysqli_query($db, "SELECT paper_id FROM exam_paper_authority WHERE user_id='$uid' AND class_id='$cid'");
    while($row = mysqli_fetch_assoc($authQ)) {
        $authorized_papers[] = $row['paper_id'];
    }

    if(empty($papers)) {
        echo '<div style="padding:10px;">No papers found for this class. Please add papers in Subject Management first.</div>';
    } else {
        echo '<div style="margin-bottom: 10px;"><label><input type="checkbox" id="selectAll"> Select All</label></div>';
        
        foreach($papers as $p) {
            $checked = in_array($p['sno'], $authorized_papers) ? 'checked' : '';
            echo '<div class="paper-item">';
            echo '<label style="display:flex; align-items:center; width:100%; cursor:pointer;">';
            echo '<input type="checkbox" name="paper_ids[]" value="'.$p['sno'].'" class="paper-cb" '.$checked.'>';
            echo '<span><strong>'.$p['paper_code'].'</strong> - '.$p['paper_title'].' ('.$p['theory_practical'].')</span>';
            echo '</label>';
            echo '</div>';
        }

        ?>
        <script>
        $('#selectAll').change(function() {
            $('.paper-cb').prop('checked', $(this).is(':checked'));
        });
        </script>
        <?php
    }
    
    exit;
}
?>

</body>
</html>
<?php
page_footer();
?>
