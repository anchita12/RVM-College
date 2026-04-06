<?php
ob_start();
session_start();
include("script/settings.php");

if (!isset($_SESSION['user_id'])) die("Access Denied");
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$isAdmin = ($username === 'admin' || $user_id == 1);

$paper_id = isset($_GET['paper_id']) ? intval($_GET['paper_id']) : 0;
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if(!$paper_id || !$class_id) die("Invalid Request");

if(!$isAdmin) {
    $checkQ = mysqli_query($db, "SELECT sno FROM exam_paper_authority WHERE user_id='$user_id' AND paper_id='$paper_id' AND can_enter=1");
    if(mysqli_num_rows($checkQ) == 0) {
        die("Access Denied");
    }
}

$pQ = mysqli_query($db, "SELECT p.*, c.class_description, s.subject 
                         FROM add_subject_papers p 
                         JOIN class_detail c ON p.class_id = c.sno 
                         LEFT JOIN add_subject s ON p.subject_id = s.sno
                         WHERE p.sno='$paper_id'");
$paper = mysqli_fetch_assoc($pQ);

if(!$paper) die("Paper not found");

$isLocked = (isset($paper['is_locked']) && $paper['is_locked'] == 1);
$classId = $paper['class_id'];

// New Dynamic Component Flags
$hasTheory = (isset($paper['has_theory']) && $paper['has_theory'] == 1);
$hasInternal = (isset($paper['has_internal']) && $paper['has_internal'] == 1);
$hasPractical = (isset($paper['has_practical']) && $paper['has_practical'] == 1);

$sessionalLabel = ($classId == 7) ? 'Sessional' : 'Internal';

// HANDLE FORM SUBMISSION
if(isset($_POST['submit_marks'])) {
    if($isLocked) {
        echo "<script>alert('Paper is locked! Cannot make changes.'); window.location.href = window.location.href;</script>";
        exit;
    }

    $marksArr = $_POST['marks'] ?? [];
    $midSemMarksArr = $_POST['mid_sem_marks'] ?? [];
    $practicalMarksArr = $_POST['practical_marks'] ?? [];
    $mappingIdsArr = $_POST['mapping_ids'] ?? [];
    
    $midSemMaxMarks = intval($_POST['mid_sem_max_marks'] ?? 0);
    $maxInternal = intval($_POST['max_marks_internal'] ?? 0);
    $maxPractical = intval($_POST['max_marks_practical'] ?? 0);

    // Update Max Marks in subject papers table
    // We use max_marks for internal, practical_max_marks for practical
    mysqli_query($db, "UPDATE add_subject_papers SET 
                        max_marks='$maxInternal', 
                        mid_sem_max_marks='$midSemMaxMarks',
                        practical_max_marks='$maxPractical'
                      WHERE sno='$paper_id'");

    foreach($mappingIdsArr as $exam_sno => $mapping_sno) {
        $internal_marks = mysqli_real_escape_string($db, trim($marksArr[$exam_sno] ?? ''));
        $mid_sem_marks = mysqli_real_escape_string($db, trim($midSemMarksArr[$exam_sno] ?? ''));
        $practical_marks = mysqli_real_escape_string($db, trim($practicalMarksArr[$exam_sno] ?? ''));
        $mapping_sno = intval($mapping_sno);
        
        // Normalize Absent for Internal
        $is_absent = (strtoupper($internal_marks) === 'AB' || strtoupper($internal_marks) === 'ABS') ? 1 : 0;
        if($is_absent) $internal_marks = 'ABS';

        // Normalize Absent for Theory Mid-Sem
        $is_mid_sem_absent = (strtoupper($mid_sem_marks) === 'AB' || strtoupper($mid_sem_marks) === 'ABS') ? 1 : 0;
        if($is_mid_sem_absent) $mid_sem_marks = 'ABS';

        // Normalize Absent for Practical
        $is_practical_absent = (strtoupper($practical_marks) === 'AB' || strtoupper($practical_marks) === 'ABS') ? 1 : 0;
        if($is_practical_absent) $practical_marks = 'ABS';
        
        // Find existing record for this specific student/paper
        $check = mysqli_query($db, "SELECT sno FROM exam_student_paper_info WHERE exam_student_info_sno='$exam_sno' AND add_subject_papers_sno='$paper_id'");
        $existingRow = mysqli_fetch_assoc($check);

        if($existingRow) {
            $sno = $existingRow['sno'];
            $updFields = [];
            if($hasInternal) {
                $updFields[] = "marks_obt='$internal_marks'";
                $updFields[] = "is_absent='$is_absent'";
            }
            if($hasTheory) {
                $updFields[] = "mid_sem_marks_obt='$mid_sem_marks'";
                $updFields[] = "is_mid_sem_absent='$is_mid_sem_absent'";
            }
            if($hasPractical) {
                $updFields[] = "practical_marks_obt='$practical_marks'";
                $updFields[] = "is_practical_absent='$is_practical_absent'";
            }
            $updFields[] = "student_paper_mapping_sno='$mapping_sno'";
            $updFields[] = "edition_time=NOW()";
            $updFields[] = "edited_by='$user_id'";

            $upd = "UPDATE exam_student_paper_info SET " . implode(", ", $updFields) . " WHERE sno='$sno'";
            mysqli_query($db, $upd);
        } else {
            if($internal_marks !== '' || $mid_sem_marks !== '' || $practical_marks !== '') {
                $ins = "INSERT INTO exam_student_paper_info 
                        (exam_student_info_sno, add_subject_papers_sno, student_paper_mapping_sno, marks_obt, mid_sem_marks_obt, practical_marks_obt, is_absent, is_mid_sem_absent, is_practical_absent, creation_time, created_by)
                        VALUES 
                        ('$exam_sno', '$paper_id', '$mapping_sno', '$internal_marks', '$mid_sem_marks', '$practical_marks', '$is_absent', '$is_mid_sem_absent', '$is_practical_absent', NOW(), '$user_id')";
                mysqli_query($db, $ins);
            }
        }
    }

    // Lock if requested
    if(isset($_POST['lock_paper']) && $_POST['lock_paper'] == '1') {
        mysqli_query($db, "UPDATE add_subject_papers SET is_locked=1 WHERE sno='$paper_id'");
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$sql = "SELECT esi.sno as exam_sno, esi.exam_roll_no, esi.student_name, si.father_name,
               si.photo_id, spm.sno as mapping_sno
        FROM exam_student_info esi
        LEFT JOIN student_info si ON esi.student_info_sno = si.sno
        JOIN student_paper_mapping spm ON si.sno = spm.student_info_sno
        WHERE esi.course_name = '$classId' 
        AND spm.add_subject_papers_sno = '$paper_id'
        AND esi.verify_status = 1
        ORDER BY esi.exam_roll_no ASC";

$studentsQ = mysqli_query($db, $sql);
$students = [];

while($r = mysqli_fetch_assoc($studentsQ)) {
    $mQ = mysqli_query($db, "SELECT * FROM exam_student_paper_info WHERE exam_student_info_sno='".$r['exam_sno']."' AND add_subject_papers_sno='$paper_id'");
    $marks = mysqli_fetch_assoc($mQ);
    $r['marks'] = $marks;
    $students[] = $r;
}

if(function_exists('sidebar')) sidebar($db);
if(function_exists('page_header')) page_header();
?>

<!DOCTYPE html>
<html>
<head>
<title>Marks Entry - <?= $paper['paper_code'] ?></title>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<style>
.marks-input { width: 80px; padding: 5px; text-align: center; border: 2px solid #ddd; border-radius: 4px; font-weight: bold; font-size: 14px; }
.marks-input:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 5px rgba(13,110,253,0.3); }
.marks-input:disabled { background: #f8f9fa; color: #555; border-color: #eee; }

.absent-btn { padding: 4px 8px; font-size: 11px; cursor: pointer; background: #fff; border: 1px solid #ced4da; border-radius: 4px; font-weight:600; color:#555; transition: all 0.2s; }
.absent-btn:hover { background: #f8f9fa; }
.absent-btn.active { background: #dc3545; color: white; border-color: #dc3545; }
.absent-btn:disabled { opacity: 0.6; cursor: not-allowed; }

thead th { position: sticky; top: 0; background: #0d6efd; color: white; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.max-marks-input { width: 70px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; font-weight: bold; text-align: center; }
.locked-badge { background: #dc3545; color: white; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 12px; display:inline-flex; align-items:center; gap:5px; box-shadow: 0 2px 4px rgba(220,53,69,0.3); }

/* Button Styling */
.btn-save { background: #198754; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s; }
.btn-save:hover { background: #157347; }
.btn-lock { background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background 0.2s; }
.btn-lock:hover { background: #bb2d3b; }
</style>
</head>
<body>

<div style="padding: 20px;">
    
    <form method="POST" id="entryForm" action="">
        <input type="hidden" name="submit_marks" value="1">
        <input type="hidden" name="lock_paper" id="lockPaperInput" value="0">

        <div class="card-box" style="margin-bottom:20px; overflow:visible;">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                <div>
                    <h4 style="margin:0; color:#0d6efd; font-weight:700; font-size:1.25rem;">
                        <?= $paper['paper_title'] ?> (<?= $paper['paper_code'] ?>)
                        <?php if($isLocked): ?>
                            <span class="locked-badge">🔒 LOCKED</span>
                        <?php endif; ?>
                    </h4>
                    <p style="margin:5px 0 0; color:#666; font-size:0.9rem;"><?= $paper['class_description'] ?></p>
                </div>
                
                <div style="display:flex; align-items:center; gap:15px;">
                    <div style="background:#fff; padding:6px 12px; border:1px solid #dee2e6; border-radius:8px; display:flex; align-items:center; gap:8px;">
                        <?php if($hasTheory): ?>
                            <label style="font-weight:600; margin:0; color:#495057; font-size:0.9rem;">Mid-Sem Max:</label>
                            <input type="number" name="mid_sem_max_marks" class="max-marks-input" 
                                   value="<?= $paper['mid_sem_max_marks'] ?? 15 ?>" 
                                   <?= $isLocked ? 'disabled' : '' ?> >
                        <?php endif; ?>

                        <?php if($hasInternal): ?>
                            <label style="font-weight:600; margin:0; color:#495057; font-size:0.9rem; margin-left:10px;"><?= $sessionalLabel ?> Max:</label>
                            <input type="number" name="max_marks_internal" class="max-marks-input" 
                                   value="<?= $paper['max_marks'] ?? 100 ?>" 
                                   <?= $isLocked ? 'disabled' : '' ?> >
                        <?php endif; ?>

                        <?php if($hasPractical): ?>
                            <label style="font-weight:600; margin:0; color:#495057; font-size:0.9rem; margin-left:10px;">Practical Max:</label>
                            <input type="number" name="max_marks_practical" class="max-marks-input" 
                                   value="<?= $paper['practical_max_marks'] ?? 0 ?>" 
                                   <?= $isLocked ? 'disabled' : '' ?> >
                        <?php endif; ?>

                        <?php if(!$hasTheory && !$hasInternal && !$hasPractical): ?>
                            <span style="color:red; font-size:12px;">No components active!</span>
                        <?php endif; ?>
                    </div>

                    <?php if(!$isLocked): ?>
                        <button type="button" class="btn-save" onclick="submitForm(false)">
                            <span>💾</span> Save Details
                        </button>
                        <button type="button" class="btn-lock" onclick="submitForm(true)">
                            <span>🔒</span> Final Submit
                        </button>
                    <?php endif; ?>

                    <a href="marks_entry_selection.php<?= $isAdmin ? '?class_id='.$class_id : '' ?>" class="btn btn-secondary" style="background:#6c757d; color:white; padding:8px 15px; text-decoration:none; border-radius:6px; font-weight:500;">Back</a>
                </div>
            </div>
        </div>

        <div class="card-box" style="padding: 0; overflow: hidden; border:1px solid #dee2e6;">
            <?php if(empty($students)): ?>
                <div style="padding:40px; text-align:center; color:#777;">
                    <h4>No verified students found for this class.</h4>
                    <p>Please ensure students have filled the exam form and are verified.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-hover" style="width:100%; margin:0;">
                        <thead>
                            <tr>
                                <th style="padding:12px 15px;">Roll No</th>
                                <th style="padding:12px 15px;">Student Name</th>
                                <th style="padding:12px 15px;">Father's Name</th>
                                <?php if($hasTheory): ?>
                                    <th style="padding:12px 15px; text-align:center;">Mid-Sem Theory</th>
                                <?php endif; ?>
                                <?php if($hasInternal): ?>
                                    <th style="padding:12px 15px; text-align:center;"><?= $sessionalLabel ?></th>
                                <?php endif; ?>
                                <?php if($hasPractical): ?>
                                    <th style="padding:12px 15px; text-align:center;">Practical Marks</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $stu): 
                                $intMarks = '';
                                $isIntAbs = false;
                                if(isset($stu['marks']['marks_obt'])) {
                                    $intMarks = $stu['marks']['marks_obt'];
                                    if(strtoupper($intMarks) === 'ABS') { $intMarks = 'ABS'; $isIntAbs = true; }
                                }

                                $pracMarks = '';
                                $isPracAbs = false;
                                if(isset($stu['marks']['practical_marks_obt'])) {
                                    $pracMarks = $stu['marks']['practical_marks_obt'];
                                    if(strtoupper($pracMarks) === 'ABS') { $pracMarks = 'ABS'; $isPracAbs = true; }
                                }
                            ?>
                                <tr>
                                    <td style="padding:10px 15px; border-bottom:1px solid #eee;">
                                        <strong><?= $stu['exam_roll_no'] ?></strong>
                                    </td>
                                    <td style="padding:10px 15px; border-bottom:1px solid #eee;">
                                        <?= $stu['student_name'] ?>
                                        <input type="hidden" class="student-name" value="<?= $stu['student_name'] ?>">
                                        <input type="hidden" name="mapping_ids[<?= $stu['exam_sno'] ?>]" value="<?= $stu['mapping_sno'] ?>">
                                    </td>
                                    <td style="padding:10px 15px; border-bottom:1px solid #eee; color:#666;">
                                        <?= $stu['father_name'] ?>
                                    </td>
                                    <?php if($hasTheory): ?>
                                        <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:center;">
                                            <div style="display:flex; justify-content:center; align-items:center; gap:8px;">
                                                <input type="text" 
                                                       name="mid_sem_marks[<?= $stu['exam_sno'] ?>]"
                                                       class="marks-input student-mid-marks" 
                                                       value="<?= $stu['marks']['mid_sem_marks_obt'] ?? '' ?>" 
                                                       placeholder="-"
                                                       autocomplete="off"
                                                       <?= $isLocked ? 'disabled' : '' ?>>
                                                
                                                <button type="button" class="absent-btn <?= (isset($stu['marks']['mid_sem_marks_obt']) && strtoupper($stu['marks']['mid_sem_marks_obt']) === 'ABS') ? 'active' : '' ?>" 
                                                        onclick="markAbsent(this)" title="Mark Absent"
                                                        <?= $isLocked ? 'disabled' : '' ?>>AB</button>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <input type="hidden" class="student-mid-marks" name="mid_sem_marks[<?= $stu['exam_sno'] ?>]" value="">
                                    <?php endif; ?>

                                    <?php if($hasInternal): ?>
                                        <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:center;">
                                            <div style="display:flex; justify-content:center; align-items:center; gap:8px;">
                                                <input type="text" 
                                                       name="marks[<?= $stu['exam_sno'] ?>]"
                                                       class="marks-input student-marks" 
                                                       value="<?= $intMarks ?>" 
                                                       placeholder="-"
                                                       autocomplete="off"
                                                       <?= $isLocked ? 'disabled' : '' ?>>
                                                
                                                <button type="button" class="absent-btn <?= $isIntAbs ? 'active' : '' ?>" 
                                                        onclick="markAbsent(this)" title="Mark Absent"
                                                        <?= $isLocked ? 'disabled' : '' ?>>AB</button>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <input type="hidden" name="marks[<?= $stu['exam_sno'] ?>]" value="">
                                    <?php endif; ?>

                                    <?php if($hasPractical): ?>
                                        <td style="padding:10px 15px; border-bottom:1px solid #eee; text-align:center;">
                                            <div style="display:flex; justify-content:center; align-items:center; gap:8px;">
                                                <input type="text" 
                                                       name="practical_marks[<?= $stu['exam_sno'] ?>]"
                                                       class="marks-input student-practical-marks" 
                                                       value="<?= $pracMarks ?>" 
                                                       placeholder="-"
                                                       autocomplete="off"
                                                       <?= $isLocked ? 'disabled' : '' ?>>
                                                
                                                <button type="button" class="absent-btn <?= $isPracAbs ? 'active' : '' ?>" 
                                                        onclick="markAbsent(this)" title="Mark Absent"
                                                        <?= $isLocked ? 'disabled' : '' ?>>AB</button>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <input type="hidden" name="practical_marks[<?= $stu['exam_sno'] ?>]" value="">
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </form>

</div>

<script>
function markAbsent(btn) {
    let input = $(btn).siblings('input[type="text"]');
    if($(btn).hasClass('active')) {
        input.val(''); 
        $(btn).removeClass('active');
    } else {
        input.val('ABS'); 
        $(btn).addClass('active');
    }
}

function submitForm(lock) {
    if(lock) {
        // Validate for Lock
        let missing = [];
        let midSemMax = parseInt($('input[name="mid_sem_max_marks"]').val()) || 0;
        let internalMax = parseInt($('input[name="max_marks_internal"]').val()) || 0;
        let practicalMax = parseInt($('input[name="max_marks_practical"]').val()) || 0;

        $('tr').each(function() {
            let row = $(this);
            if(row.find('.student-name').length === 0) return; // Header row

            let midVal = row.find('.student-mid-marks').val()?.trim() ?? '';
            let intVal = row.find('.student-marks').val()?.trim() ?? '';
            let pracVal = row.find('.student-practical-marks').val()?.trim() ?? '';
            
            let isMissing = false;
            
            // Check Theory/Mid-Sem
            if(row.find('.student-mid-marks').attr('type') !== 'hidden' && midSemMax > 0 && midVal === '') isMissing = true;
            
            // Check Internal
            if(row.find('.student-marks').attr('type') !== 'hidden' && internalMax > 0 && intVal === '') isMissing = true;
            
            // Check Practical
            if(row.find('.student-practical-marks').attr('type') !== 'hidden' && practicalMax > 0 && pracVal === '') isMissing = true;

            if(isMissing) {
                let name = row.find('.student-name').val();
                missing.push(name);
            }
        });

        if(missing.length > 0) {
            alert("Cannot Final Submit! \n\nThe following students are missing marks:\n\n" + missing.join("\n"));
            return;
        }

        if(!confirm("Are you sure you want to FINAL SUBMIT? \n\nNo further changes will be allowed after this.")) {
            return;
        }
        $('#lockPaperInput').val('1');
    } else {
        // Save Draft - No validation needed
        $('#lockPaperInput').val('0');
    }

    document.getElementById('entryForm').submit();
}
</script>

</body>
</html>
<?php
page_footer();
?>
