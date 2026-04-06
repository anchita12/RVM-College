<?php
ob_start();
session_start();
include("script/settings.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Determine if Admin
// Assuming 'admin' username is the superadmin. Adjust logic if 'role' column exists.
$isAdmin = ($username === 'admin' || $user_id == 1); 

// Logic:
// If Admin: Can filter by ANY Class -> Show ANY Paper.
// If Teacher: Show only Assigned Papers.

$papers = [];

if ($isAdmin) {
    // Admin Mode: Check if a class is selected to filter
    $class_filter = $_GET['class_id'] ?? '';
    
    if ($class_filter) {
        $sql = "SELECT p.*, c.class_description, s.subject 
                FROM add_subject_papers p
                JOIN class_detail c ON p.class_id = c.sno
                LEFT JOIN add_subject s ON p.subject_id = s.sno
                WHERE p.class_id = '$class_filter'
                ORDER BY p.paper_code ASC";
    } else {
        // Show nothing initially or last 20? 
        // Better to ask to select class.
        $sql = ""; 
    }
} else {
    // Teacher Mode: Fetch assigned papers
    $sql = "SELECT p.*, c.class_description, s.subject 
            FROM exam_paper_authority epa
            JOIN add_subject_papers p ON epa.paper_id = p.sno
            JOIN class_detail c ON p.class_id = c.sno
            LEFT JOIN add_subject s ON p.subject_id = s.sno
            WHERE epa.user_id = '$user_id' AND epa.can_enter = 1
            ORDER BY c.class_description, p.paper_code ASC";
}

if(!empty($sql)) {
    $q = mysqli_query($db, $sql);
    while($row = mysqli_fetch_assoc($q)) {
        $papers[] = $row;
    }
}

if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Marks Entry Selection</title>
<style>
/* Dashboard Card Style */
.paper-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.paper-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 5px solid #0d6efd;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
}
.paper-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.paper-card h5 { margin: 0 0 10px; font-weight: 700; font-size: 18px; color: #333; }
.paper-card p { margin: 0 0 5px; color: #666; font-size: 14px; }
.paper-tag { 
    display: inline-block; 
    padding: 4px 10px; 
    border-radius: 20px; 
    font-size: 12px; 
    font-weight: 600; 
    margin-top: 10px;
}
.tag-theory { background: #e3f2fd; color: #0d6efd; }
.tag-practical { background: #fce4ec; color: #e91e63; }

/* Filter Bar */
.filter-bar {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}
.filter-bar select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    min-width: 250px;
}
</style>
</head>
<body>

<div style="padding: 20px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin:0; font-weight: 700; color: #333;">Marks Entry Dashboard</h2>
        <?php if($isAdmin): ?>
            <span class="badge bg-danger" style="padding: 8px 15px; border-radius: 20px; color: white; background: #dc3545;">Admin Mode</span>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
        <div class="filter-bar">
            <strong>Filter by Class:</strong>
            <form method="get" style="margin:0;">
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">-- Select Class --</option>
                    <?php
                    $cQ = mysqli_query($db, "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
                    while($c = mysqli_fetch_assoc($cQ)) {
                        $sel = (isset($_GET['class_id']) && $_GET['class_id'] == $c['sno']) ? 'selected' : '';
                        echo "<option value='{$c['sno']}' $sel>{$c['class_description']}</option>";
                    }
                    ?>
                </select>
            </form>
        </div>
    <?php endif; ?>

    <!-- PAPERS GRID -->
    <?php if (empty($papers)): ?>
        <div style="text-align: center; padding: 50px; background: #fff; border-radius: 12px; color: #777;">
            <?php if($isAdmin && !isset($_GET['class_id'])): ?>
                <h3>👈 Please select a class to view papers.</h3>
            <?php elseif($isAdmin): ?>
                <h3>⚠️ No papers found for this class.</h3>
            <?php else: ?>
                <h3>📭 You have not been assigned any papers yet.</h3>
                <p>Please contact the administrator.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="paper-grid">
            <?php foreach($papers as $p): 
                $modeColor = (strtoupper($p['theory_practical']) == 'PRACTICAL') ? 'tag-practical' : 'tag-theory';
                $link = "marks_entry_form.php?paper_id=" . $p['sno'] . "&class_id=" . $p['class_id'];
            ?>
                <a href="<?= $link ?>" class="paper-card">
                    <h5><?= $p['paper_code'] ?></h5>
                    <p style="color: #0d6efd; font-weight: 600;"><?= $p['class_description'] ?></p>
                    <p><?= $p['paper_title'] ?></p>
                    <p style="font-size: 13px; margin-top: 5px;">Subject: <?= $p['subject'] ?></p>
                    
                    <?php if($p['has_theory']): ?>
                        <span class="paper-tag tag-theory">Theory</span>
                    <?php endif; ?>
                    <?php if($p['has_internal']): ?>
                        <span class="paper-tag" style="background: #e0f7fa; color: #00838f;">Internal</span>
                    <?php endif; ?>
                    <?php if($p['has_practical']): ?>
                        <span class="paper-tag tag-practical">Practical</span>
                    <?php endif; ?>
                    
                    <?php if($p['paper_type']): ?>
                        <span class="paper-tag" style="background: #eee; color: #555;"><?= ucfirst($p['paper_type']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
<?php
page_footer();
?>
