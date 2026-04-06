<?php
require_once __DIR__ . '/scripts/settings.php';

$message = '';
$messageType = '';

// Fetch all courses for the dropdown
$courses_sql = "SELECT sno, class_description FROM class_detail ORDER BY class_description ASC";
$courses_res = $mysqli->query($courses_sql);
$courses = [];
if ($courses_res) {
    while ($row = $courses_res->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Handle generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $course_sno = $_POST['course_sno'];
    $start_num_str = $_POST['start_num'];
    $prefix = $_POST['prefix'];

    if (!$course_sno || $start_num_str === '') {
        $message = "Please select a course and enter a starting number.";
        $messageType = "danger";
    } else {
        $start_num = (int)$start_num_str;
        $padding_length = strlen($start_num_str);
        // Fetch verified students for this course
        $stmt = $mysqli->prepare("
            SELECT esi.student_info_sno 
            FROM exam_student_info esi
            WHERE esi.course_name = ? AND esi.verify_status = 1
            ORDER BY esi.exam_roll_no ASC
        ");
        $stmt->bind_param("i", $course_sno);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $count = 0;
        $current_num = $start_num;

        while ($row = $result->fetch_assoc()) {
            $student_info_sno = $row['student_info_sno'];
            $enroll_no = $prefix . str_pad($current_num, $padding_length, "0", STR_PAD_LEFT);

            $update_stmt = $mysqli->prepare("UPDATE student_info SET enroll_no = ? WHERE sno = ?");
            $update_stmt->bind_param("si", $enroll_no, $student_info_sno);
            $update_stmt->execute();
            $update_stmt->close();

            $current_num++;
            $count++;
        }
        $stmt->close();

        if ($count > 0) {
            $message = "Successfully generated Enrollment Numbers for $count students.";
            $messageType = "success";
        } else {
            $message = "No verified students found for the selected course.";
            $messageType = "warning";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Enrollment Numbers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .generator-card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .header-section h2 {
            font-weight: 700;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header-section p {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .form-label {
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.2);
            color: #fff;
        }

        .form-select option {
            background: #1e1b4b;
            color: #fff;
        }

        .btn-generate {
            background: var(--primary);
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-generate:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }

        .alert {
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(8px);
            color: #fff;
            margin-bottom: 25px;
        }

        .alert-success { border-left: 4px solid #10b981; }
        .alert-danger { border-left: 4px solid #ef4444; }
        .alert-warning { border-left: 4px solid #f59e0b; }

        .input-group-text {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            color: #94a3b8;
            border-radius: 12px 0 0 12px;
        }

        .prefix-input {
            border-radius: 0 12px 12px 0 !important;
        }
    </style>
</head>
<body>

<div class="generator-card">
    <div class="header-section">
        <h2>Enrollment Generator</h2>
        <p>Generate sequential enrollment numbers for student records</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label class="form-label">Select Course</label>
            <select name="course_sno" class="form-select" required>
                <option value="">Choose a class...</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['sno'] ?>"><?= htmlspecialchars($course['class_description']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Enrollment Prefix</label>
            <input type="text" name="prefix" class="form-control" value="RM25-" placeholder="e.g. RM25-">
        </div>

        <div class="mb-4">
            <label class="form-label">Starting Number</label>
            <input type="text" name="start_num" class="form-control" placeholder="e.g. 001" required>
            <div class="form-text text-muted mt-2">Numbers will increment sequentially from here. Padding is preserved.</div>
        </div>

        <button type="submit" name="generate" class="btn btn-primary btn-generate">
            Generate & Sync to Database
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
