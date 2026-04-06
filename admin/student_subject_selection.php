<?php
ob_start();
session_start();
include("script/settings.php");

if (!isset($_SESSION['user_id'])) die("Access Denied");

$msg = '';

// 1. Handle AJAX To Fetch Students by Class (Excluding those already mapped for this Year/Semester)
if (isset($_POST['fetch_students'])) {
    $class_id = intval($_POST['class_id']);
    $academic_year = mysqli_real_escape_string($db, $_POST['academic_year']);
    $semester = intval($_POST['semester']);
    
    // Subquery to find students who already have mappings for this year/semester
    $exclude_sql = "SELECT DISTINCT student_info_sno FROM student_paper_mapping 
                    WHERE academic_year = '$academic_year' AND semester = '$semester'";
    
    // Improved exclusion logic: only filter if mappings actually exist to avoid empty IN clause issues
    $checkQ = mysqli_query($db, $exclude_sql);
    $exclude_ids = [];
    while($r = mysqli_fetch_assoc($checkQ)) $exclude_ids[] = $r['student_info_sno'];
    
    $where_clause = "WHERE class = '$class_id' AND status=1";
    if (!empty($exclude_ids)) {
        $ids_str = implode(',', $exclude_ids);
        $where_clause .= " AND sno NOT IN ($ids_str)";
    }
    
    $studentsQ = mysqli_query($db, "SELECT sno, stu_name, roll_no, uin FROM student_info $where_clause ORDER BY stu_name ASC");
    $students = [];
    while ($s = mysqli_fetch_assoc($studentsQ)) {
        $students[] = $s;
    }
    echo json_encode(['status' => 'success', 'students' => $students]);
    exit;
}

// 2. Handle AJAX To Fetch Papers
if (isset($_POST['fetch_papers'])) {
    $class_id = intval($_POST['class_id']);
    $papersQ = mysqli_query($db, "SELECT p.*, s.subject 
                                  FROM add_subject_papers p 
                                  LEFT JOIN add_subject s ON p.subject_id = s.sno 
                                  WHERE p.class_id = '$class_id' 
                                  ORDER BY s.subject, p.paper_title");
    $papers = [];
    while ($row = mysqli_fetch_assoc($papersQ)) {
        $papers[] = $row;
    }
    echo json_encode(['status' => 'success', 'papers' => $papers]);
    exit;
}

// 3. Handle Form Submission (Bulk Support)
if (isset($_POST['save_mapping'])) {
    $student_ids = $_POST['student_id'] ?? []; // Expected to be an array now
    $academic_year = mysqli_real_escape_string($db, $_POST['academic_year']);
    $semester = intval($_POST['semester']);
    $selected_paper_ids = $_POST['paper_checkbox'] ?? []; 

    if (empty($student_ids)) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Please select at least one student.</div>';
    } elseif (empty($selected_paper_ids)) {
        $_SESSION['msg'] = '<div class="alert alert-danger">Please select at least one paper.</div>';
    } else {
        mysqli_begin_transaction($db);
        try {
            foreach ($student_ids as $student_sno) {
                $student_sno = intval($student_sno);
                
                // Delete existing mappings for this combo to refresh
                mysqli_query($db, "DELETE FROM student_paper_mapping 
                                   WHERE student_info_sno = '$student_sno' 
                                   AND academic_year = '$academic_year' 
                                   AND semester = '$semester'");

                foreach ($selected_paper_ids as $paper_sno => $val) {
                    $paper_sno = intval($paper_sno);
                    
                    // Fetch the category (paper_type) from add_subject_papers
                    $catQ = mysqli_query($db, "SELECT paper_type FROM add_subject_papers WHERE sno = '$paper_sno'");
                    $catData = mysqli_fetch_assoc($catQ);
                    $category = $catData['paper_type'] ?: 'core'; 
                    
                    $ins = "INSERT INTO student_paper_mapping 
                            (student_info_sno, add_subject_papers_sno, academic_year, semester, subject_category) 
                            VALUES ('$student_sno', '$paper_sno', '$academic_year', '$semester', '$category')";
                    mysqli_query($db, $ins);
                }
            }
            
            mysqli_commit($db);
            $_SESSION['msg'] = '<div class="alert alert-success">Mappings saved successfully for ' . count($student_ids) . ' student(s)!</div>';
            header("Location: student_subject_selection.php");
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
    <title>Bulk Student Subject Selection</title>
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
        .btn-save { background: #0d6efd; color: #fff; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; }
        .btn-save:hover { background: #084298; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-danger { background: #f8d7da; color: #842029; }
        .select-all-btn { background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: background 0.2s; }
        .select-all-btn:hover { background: #5a6268; }
        .deselect-all-btn { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: background 0.2s; }
        .deselect-all-btn:hover { background: #bb2d3b; }
        
        /* Compact Select2 Multi-select */
        .select2-container--default .select2-selection--multiple {
            max-height: 80px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #0d6efd;
        }
    </style>
</head>
<body>

<div style="padding: 20px;">
    <?= $msg ?>

    <div class="card-box">
        <div class="card-heading">Bulk Student Subject Selection</div>
        
        <form method="post" id="mappingForm">
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

                <div class="form-group" style="width: 350px;">
                    <label>2. Select Student(s) 
                        <div style="float: right;">
                            <button type="button" id="selectAllStudents" class="select-all-btn">Select All</button>
                            <button type="button" id="deselectAllStudents" class="deselect-all-btn">Clear</button>
                        </div>
                    </label>
                    <select name="student_id[]" id="studentSelect" class="form-control" multiple required disabled>
                    </select>
                </div>

                <div class="form-group" style="width: 140px;">
                    <label>Semester</label>
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
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" id="yearInput" class="form-control" value="<?= date('Y') . '-' . (date('Y') + 1) ?>" placeholder="e.g. 2024-25" required>
                </div>
            </div>

            <div id="papersSection" style="margin-top: 20px; display: none;">
                <div class="card-heading" style="font-size: 16px;">Tick Papers (Categories are auto-assigned)</div>
                <table class="paper-list-table">
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">
                                <input type="checkbox" id="paperSelectAll" style="transform: scale(1.3); cursor: pointer;" title="Select/Deselect All">
                            </th>
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
                    <button type="submit" name="save_mapping" class="btn-save">💾 Save Paper Mapping</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#classSelect').select2({ placeholder: "-- Select Class --" });
        $('#studentSelect').select2({ placeholder: "-- Choose Class First --" });

        $('#classSelect, #semesterSelect, #yearInput').on('change', function() {
            var classId = $('#classSelect').val();
            if (classId) {
                fetchStudents(classId);
                fetchPapers(classId);
            } else {
                $('#studentSelect').empty().attr('disabled', true).trigger('change');
                $('#papersSection').hide();
            }
        });

        function fetchStudents(classId) {
            var year = $('#yearInput').val();
            var semester = $('#semesterSelect').val();
            $.ajax({
                url: 'student_subject_selection.php',
                type: 'POST',
                data: { fetch_students: true, class_id: classId, academic_year: year, semester: semester },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var options = '';
                        response.students.forEach(function(s) {
                            options += '<option value="' + s.sno + '">' + s.stu_name + ' (' + (s.roll_no || s.uin) + ')</option>';
                        });
                        $('#studentSelect').html(options).attr('disabled', false).trigger('change');
                    }
                }
            });
        }

        function fetchPapers(classId) {
            $.ajax({
                url: 'student_subject_selection.php',
                type: 'POST',
                data: { fetch_papers: true, class_id: classId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        var html = '';
                        if (response.papers.length > 0) {
                            response.papers.forEach(function(p) {
                                html += '<tr>';
                                html += '<td style="text-align: center;"><input type="checkbox" name="paper_checkbox[' + p.sno + ']" class="paper-checkbox" value="1" style="transform: scale(1.5); cursor: pointer;"></td>';
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
        }

        $('#selectAllStudents').on('click', function() {
            $('#studentSelect option').prop('selected', true).trigger('change');
        });

        $('#deselectAllStudents').on('click', function() {
            $('#studentSelect').val(null).trigger('change');
        });

        $('#paperSelectAll').on('change', function() {
            $('.paper-checkbox').prop('checked', this.checked);
        });

        // Trigger individual checkbox change to uncheck 'Select All' if one is unchecked
        $(document).on('change', '.paper-checkbox', function() {
            if ($('.paper-checkbox:checked').length === $('.paper-checkbox').length) {
                $('#paperSelectAll').prop('checked', true);
            } else {
                $('#paperSelectAll').prop('checked', false);
            }
        });
    });
</script>

</body>
</html>
<?php
page_footer();
?>
