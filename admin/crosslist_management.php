<?php
/**
 * Crosslist Management UI
 * Allows selection of Course and provides Preview/Download options.
 */
ob_start();
session_start();
include("script/settings.php");
include("crosslist_functions.php");

$msg = '';
$course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
$students = [];
$class_info = [];

if ($course_id > 0) {
    $students = fetch_crosslist_data($db, $course_id);
    $class_info = get_class_details($db, $course_id);
    if (empty($students)) {
        $msg = '<div class="alert alert-warning">No verified students found for this course.</div>';
    }
}

if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Crosslist Management</title>
    <style>
        .card-box { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .card-heading { position: relative; font-size: 20px; font-weight: 700; color: #0d6efd; margin-bottom: 20px; padding-left: 18px; }
        .card-heading::before { content: ''; position: absolute; left: 0; top: 4px; width: 5px; height: 90%; background: #0d6efd; border-radius: 4px; }
        
        .form-row { display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px; }
        .form-group { flex: 1; display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 6px; font-size: 14px; color: #555; }
        .form-control { padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 14px; }
        
        .btn-search { background: #0d6efd; color: #fff; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-search:hover { background: #084298; }
        
        .btn-download { background: #198754; color: #fff; border: none; padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: 0.3s; }
        .btn-download:hover { background: #157347; color: #fff; }

        .preview-container { border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; background: #fff; overflow-x: auto; }
        .preview-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    </style>
</head>
<body>

<div style="padding: 20px;">
    <?= $msg ?>

    <div class="card-box">
        <div class="card-heading">Generate Crosslist</div>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group" style="max-width: 400px;">
                    <label>Select Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">-- Select Course --</option>
                         <?php
                        // Only show courses where at least one student has marks entered
                        $classQ = mysqli_query($db, "
                            SELECT DISTINCT cd.sno, cd.class_description 
                            FROM class_detail cd
                            WHERE EXISTS (
                                SELECT 1 
                                FROM exam_student_info esi
                                INNER JOIN exam_student_paper_info espi ON espi.exam_student_info_sno = esi.sno
                                WHERE esi.course_name = cd.sno 
                                  AND esi.verify_status = 1
                                  AND (
                                      espi.marks_obt IS NOT NULL OR 
                                      espi.mid_sem_marks_obt IS NOT NULL OR 
                                      espi.practical_marks_obt IS NOT NULL
                                  )
                            )
                            ORDER BY cd.class_description ASC
                        ");
                        if (mysqli_num_rows($classQ) == 0) {
                            echo '<option value="" disabled>No courses with marks found</option>';
                        }
                        while ($c = mysqli_fetch_assoc($classQ)) {
                            $selected = ($course_id == $c['sno']) ? 'selected' : '';
                            echo '<option value="' . $c['sno'] . '" ' . $selected . '>' . $c['class_description'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn-search">View Preview</button>
                </div>
            </div>
        </form>
    </div>

    <?php if ($course_id > 0 && !empty($students)): ?>
        <div class="card-box">
            <div class="preview-header">
                <div class="card-heading" style="margin-bottom: 0;">Crosslist Preview</div>
                <a href="generate_crosslist_pdf.php?course_id=<?= $course_id ?>" class="btn-download" target="_blank">
                    📥 Download PDF
                </a>
            </div>

            <div class="preview-container">
                <!-- Use the shared render function -->
                <?php echo render_crosslist_html($students, $class_info, false); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
<?php
if (function_exists('page_footer')) page_footer();
?>
