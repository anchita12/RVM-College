<?php
require_once __DIR__ . '/scripts/settings.php';
$registrationColumn = 'uin';
$colCheckReg = $mysqli->query("SHOW COLUMNS FROM uin_register_student LIKE 'registration_no'");
if ($colCheckReg && $colCheckReg->num_rows > 0) {
    $registrationColumn = 'registration_no';
}
if ($colCheckReg) {
    $colCheckReg->free();
}

$searchTerm = trim($_POST['search'] ?? '');
$searchDob = trim($_POST['dob'] ?? '');
$students   = [];
$selectedStudent = null;
if (!empty($_POST['student_id'])) {
    $studentId = (int)$_POST['student_id'];
    if ($studentId > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $selectedStudent = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

if (!$selectedStudent && $searchTerm !== '' && $searchDob !== '') {
    $column = 'uin';
    $like   = '%' . $searchTerm . '%';

    $sql = "SELECT id, uin, {$registrationColumn} AS registration_no, candidate_name, fathers_name, dob, mobile 
            FROM uin_register_student
            WHERE {$column} LIKE ? AND dob = ?
            ORDER BY id DESC
            LIMIT 50";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $like, $searchDob);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

if ($selectedStudent) {
    $formData = $selectedStudent;
    $formData['student_id'] = (int)$selectedStudent['id'];
    $formData['from_edit'] = true; 
}
$successMessage = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $successMessage = 'Admission form updated successfully!';
}

// College settings for header (same style as UIN form)
$college = get_college_settings(1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit UIN Admission Form</title>

   <link rel="stylesheet" href="cdn/css/bootstrap.min.css">
<link rel="stylesheet" href="cdn/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/uin_form.css">

    <script>
    // Prevent back from staying in edit; send user to home
    history.replaceState({ page: 'uin_edit' }, '', location.href);
    window.addEventListener('popstate', function () {
        window.location.href = 'index.php';
    });
    </script>
</head>
<body>
    <style>
        .institute-logo-small {
    width: 80px;     
    height: auto;    
}


        </style>
<header class="institute-header py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if (!empty($college['logo']) && file_exists($college['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($college['logo']); ?>" alt="Logo" class="institute-logo-small">
                <?php else: ?>
                    
                    <div class="institute-logo-placeholder-small">
                        <i class="fa-solid fa-graduation-cap fa-2x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col">
                <h2 class="institute-name mb-1">
                    <?php echo htmlspecialchars($college['college_name'] ); ?>
                </h2>
                <p class="institute-tagline-small mb-0"><?php echo htmlspecialchars($college['tagline'] ?? 'Autonomous Post Graduate College'); ?> || <?php echo htmlspecialchars($college['naac_text'] ?? 'NAAC Accredited B++'); ?></p>
            </div>
        </div>
    </div>
</header>

<main class="main-form-container py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-wrapper">
                    <div class="form-title-section mb-4">
                        <h1 class="form-main-title">UIN Search</h1>
                    </div>

                    <form method="post" class="card card-body mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">UIN Number</label>
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Enter UIN number"
                                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                                       required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Date of Birth</label>
                                <input type="date"
                                       name="dob"
                                       class="form-control"
                                       value="<?php echo htmlspecialchars($searchDob); ?>"
                                       required>
                            </div>
                           <div class="col-md-3 d-flex gap-2">
    <button type="submit" class="btn btn-primary flex-fill mt-3 mt-md-0">
        Search
    </button>

    <a href="index.php" class="btn btn-secondary flex-fill mt-3 mt-md-0">
        Back
    </a>
</div>

                        </div>
                       
                    </form>

                    <?php if (!$selectedStudent && $searchTerm !== ''): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <strong>Search Results</strong>
                            </div>
                            <div class="card-body p-0">
                                <?php if (count($students) === 0): ?>
                                    <div class="p-3 text-center text-danger">
                                        Koi record nahi mila. Search text check kijiye.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover mb-0">
                                            <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>UIN</th>
                                                <th>Registration No.</th>
                                                <th>Name</th>
                                                <th>Father's Name</th>
                                                <th>DOB</th>
                                                <th>Mobile</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($students as $stu): ?>
                                                <tr>
                                                    <td><?php echo (int)$stu['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($stu['uin'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($stu['registration_no'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($stu['candidate_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($stu['fathers_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($stu['dob'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($stu['mobile'] ?? ''); ?></td>
                                                    <td>
                                                       <form method="post" action="uin_edit.php" style="display:inline;">
    <input type="hidden" name="student_id" value="<?php echo (int)$stu['id']; ?>">
    <button type="submit" class="btn btn-sm btn-warning">
        Edit Admission
    </button>
</form>

                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show" id="successAlert" role="alert">
                            <strong>Success!</strong> <?php echo htmlspecialchars($successMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <script>
                            setTimeout(function() {
                                const alert = document.getElementById('successAlert');
                                if (alert) {
                                    const bsAlert = new bootstrap.Alert(alert);
                                    bsAlert.close();
                                }
                            }, 5000);
                        </script>
                    <?php endif; ?>
                    
                    <?php if ($selectedStudent): ?>
                        <div class="form-title-section mb-3">
                            <h2 class="form-step-title">
                                Edit Admission Form for:
                                <?php echo htmlspecialchars($selectedStudent['candidate_name'] ?? ''); ?>
                                (UIN: <?php echo htmlspecialchars($selectedStudent['uin'] ?? ''); ?>)
                            </h2>
                        </div>
                        <?php include __DIR__ . '/uin_steps/step3_admission_form.php'; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</main>

<footer class="py-3 text-bg-primary text-center">
    <div class="container">
        <span class="text-white">UIN Edit Panel</span>
    </div>
    <script src="cdn/js/bootstrap.bundle.min.js"></script>
</footer>
</body>
</html>


