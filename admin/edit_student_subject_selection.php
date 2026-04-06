<?php
ob_start();
session_start();
include("script/settings.php");

if (!isset($_SESSION['user_id'])) die("Access Denied");

$msg = '';

// 1. Handle AJAX To Fetch Students by Class (Standard fetch for selection)
if (isset($_POST['fetch_students'])) {
    $class_id = intval($_POST['class_id']);
    $studentsQ = mysqli_query($db, "SELECT sno, stu_name, roll_no, uin FROM student_info WHERE class = '$class_id' AND status=1 ORDER BY stu_name ASC");
    $students = [];
    while ($s = mysqli_fetch_assoc($studentsQ)) {
        $students[] = $s;
    }
    echo json_encode(['status' => 'success', 'students' => $students]);
    exit;
}

// 2. Handle AJAX To Fetch Papers & Existing Mappings
if (isset($_POST['fetch_papers_audit'])) {
    $class_id = intval($_POST['class_id']);
    $student_id = intval($_POST['student_id']);
    $year = mysqli_real_escape_string($db, $_POST['academic_year']);
    $semester = intval($_POST['semester']);

    // Fetch All Papers for this Class
    $papersQ = mysqli_query($db, "SELECT p.*, s.subject 
                                  FROM add_subject_papers p 
                                  LEFT JOIN add_subject s ON p.subject_id = s.sno 
                                  WHERE p.class_id = '$class_id' 
                                  ORDER BY s.subject, p.paper_title");
    $papers = [];
    while ($row = mysqli_fetch_assoc($papersQ)) {
        $papers[] = $row;
    }

    // Fetch existing mappings for this student/year/semester
    $mappedQ = mysqli_query($db, "SELECT add_subject_papers_sno FROM student_paper_mapping 
                                   WHERE student_info_sno = '$student_id' 
                                   AND academic_year = '$year' 
                                   AND semester = '$semester'");
    $mapped_ids = [];
    while($r = mysqli_fetch_assoc($mappedQ)) $mapped_ids[] = $r['add_subject_papers_sno'];

    echo json_encode(['status' => 'success', 'papers' => $papers, 'mapped_ids' => $mapped_ids]);
    exit;
}

// 3. Handle Form Submission (Single Student Update)
if (isset($_POST['update_mapping'])) {
    $student_id = intval($_POST['student_id']);
    $academic_year = mysqli_real_escape_string($db, $_POST['academic_year']);
    $semester = intval($_POST['semester']);
    $selected_paper_ids = $_POST['paper_checkbox'] ?? []; 

    if (!$student_id) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Please select a student.</div>';
    } else {
        mysqli_begin_transaction($db);
        try {
            // Delete existing mappings for this combo
            mysqli_query($db, "DELETE FROM student_paper_mapping 
                               WHERE student_info_sno = '$student_id' 
                               AND academic_year = '$academic_year' 
                               AND semester = '$semester'");

            foreach ($selected_paper_ids as $paper_sno => $val) {
                $paper_sno = intval($paper_sno);
                
                // Fetch the category (paper_type)
                $catQ = mysqli_query($db, "SELECT paper_type FROM add_subject_papers WHERE sno = '$paper_sno'");
                $catData = mysqli_fetch_assoc($catQ);
                $category = ($catData && $catData['paper_type']) ? $catData['paper_type'] : 'core'; 
                
                $ins = "INSERT INTO student_paper_mapping 
                        (student_info_sno, add_subject_papers_sno, academic_year, semester, subject_category) 
                        VALUES ('$student_id', '$paper_sno', '$academic_year', '$semester', '$category')";
                mysqli_query($db, $ins);
            }
            
            mysqli_commit($db);
            $_SESSION['msg'] = '<div class="alert alert-success">Mappings updated successfully!</div>';
            header("Location: edit_student_subject_selection.php");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($db);
            $_SESSION['msg'] = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

sidebar();
page_header();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student Subject Selection</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <style>
        .card-box { background: #fff; border-radius: 12px; padding: 25px; margin-bottom: 30px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
        .card-heading { position: relative; font-size: 20px; font-weight: 700; color: #0d6efd; margin-bottom: 20px; padding-left: 18px; }
        .card-heading::before { content: ''; position: absolute; left: 0; top: 4px; width: 5px; height: 90%; background: #0d6efd; border-radius: 4px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .select2-container { width: 100% !important; }
        .paper-list-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .paper-list-table th, .paper-list-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .paper-list-table th { background-color: #f8f9fa; }
        .btn-save { background: #198754; color: #fff; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; }
        .btn-save:hover { background: #157347; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>

<div style="padding: 20px;">
    <?= $msg ?>

    <div class="card-box">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div class="card-heading">Edit Student Subject Selection</div>
            <a href="student_subject_selection.php" class="btn btn-primary" style="background:#0d6efd; color:white; padding:8px 15px; text-decoration:none; border-radius:6px; font-weight:500;">Go to Bulk Selection</a>
        </div>
        
        <form method="post" id="editMappingForm">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="width: 200px;">
                    <label>1. Select Class</label>
                    <select name="class_id" id="classSelect" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php
                        $classQ = mysqli_query($db, "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
                        while ($c = mysqli_fetch_assoc($classQ)) {
                            echo '<option value="' . $c['sno'] . '">' . $c['class_description'] . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group" style="width: 250px;">
                    <label>2. Select Student</label>
                    <select name="student_id" id="studentSelect" class="form-control" required disabled>
                        <option value="">-- Choose Class First --</option>
                    </select>
                </div>

                <div class="form-group" style="width: 140px;">
                    <label>3. Semester</label>
                    <select name="semester" id="semesterSelect" class="form-control" required>
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                        <option value="3">Semester 3</option>
                        <option value="4">Semester 4</option>
                        <option value="5">Semester 5</option>
                        <option value="6">Semester 6</option>
                        <option value="7">Semester 7</option>
                        <option value="8">Semester 8</option>
                    </select>
                </div>

                <div class="form-group" style="width: 160px;">
                    <label>4. Academic Year</label>
                    <input type="text" name="academic_year" id="yearInput" class="form-control" value="<?= date('Y') . '-' . (date('Y') + 1) ?>" placeholder="e.g. 2024-25" required>
                </div>
                
                <div class="form-group">
                    <button type="button" id="loadBtn" class="btn btn-info" style="background:#0dcaf0; border:none; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer;">🔍 Load Mapping</button>
                </div>
            </div>

            <div id="papersSection" style="margin-top: 20px; display: none;">
                <div class="card-heading" style="font-size: 16px;">Tick/Untick Papers to Update Mapping</div>
                <table class="paper-list-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">Mapped</th>
                            <th>Paper Code</th>
                            <th>Paper Title</th>
                            <th>Subject</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody id="papersListBody">
                        <!-- Dynamically Filled -->
                    </tbody>
                </table>
                
                <div style="margin-top: 25px; text-align: center;">
                    <button type="submit" name="update_mapping" class="btn-save">💾 Update Paper Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#classSelect').select2({ placeholder: "-- Select Class --" });
        $('#studentSelect').select2({ placeholder: "-- Choose Class First --" });

        $('#classSelect').on('change', function() {
            var classId = $(this).val();
            if (classId) {
                fetchStudents(classId);
            } else {
                $('#studentSelect').empty().append('<option value="">-- Choose Class First --</option>').attr('disabled', true).trigger('change');
                $('#papersSection').hide();
            }
        });

        function fetchStudents(classId) {
            $.ajax({
                url: 'edit_student_subject_selection.php',
                type: 'POST',
                data: { fetch_students: true, class_id: classId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var options = '<option value="">-- Select Student --</option>';
                        response.students.forEach(function(s) {
                            options += '<option value="' + s.sno + '">' + s.stu_name + ' (' + (s.roll_no || s.uin) + ')</option>';
                        });
                        $('#studentSelect').html(options).attr('disabled', false).trigger('change');
                    }
                }
            });
        }

        $('#loadBtn').on('click', function() {
            var classId = $('#classSelect').val();
            var studentId = $('#studentSelect').val();
            var year = $('#yearInput').val();
            var semester = $('#semesterSelect').val();

            if(!classId || !studentId) {
                alert("Please select Class and Student first.");
                return;
            }

            $.ajax({
                url: 'edit_student_subject_selection.php',
                type: 'POST',
                data: { 
                    fetch_papers_audit: true, 
                    class_id: classId, 
                    student_id: studentId,
                    academic_year: year,
                    semester: semester
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var html = '';
                        var mappedIds = response.mapped_ids;
                        
                        if (response.papers.length > 0) {
                            response.papers.forEach(function(p) {
                                var isChecked = mappedIds.includes(p.sno) ? 'checked' : '';
                                html += '<tr>';
                                html += '<td style="text-align: center;"><input type="checkbox" name="paper_checkbox[' + p.sno + ']" class="paper-checkbox" value="1" ' + isChecked + ' style="transform: scale(1.5); cursor: pointer;"></td>';
                                html += '<td>' + p.paper_code + '</td>';
                                html += '<td>' + p.paper_title + '</td>';
                                html += '<td>' + (p.subject || '-') + '</td>';
                                html += '<td style="text-transform: capitalize;">' + (p.paper_type || 'core') + '</td>';
                                html += '</tr>';
                            });
                            $('#papersListBody').html(html);
                        } else {
                            $('#papersListBody').html('<tr><td colspan="5" style="text-align:center;">No papers found for this class.</td></tr>');
                        }
                        $('#papersSection').show();
                    }
                }
            });
        });
    });
</script>

</body>
</html>
<?php
page_footer();
?>
