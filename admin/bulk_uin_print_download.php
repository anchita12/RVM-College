<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/script/settings.php';
    
    // Load college settings from database
    $collegeSettings = get_college_settings(1); 
    function renderCard($label, $formData, $courseName) {
        global $collegeSettings;
        if (!$formData) return;

        $currentDate = date('d-m-Y');
        $dobFormatted = !empty($formData['dob']) ? date('d-m-Y', strtotime($formData['dob'])) : 'N/A';
        $emailMasked = !empty($formData['email']) ? '******' . substr($formData['email'], -12) : 'N/A';
        $mobileMasked = !empty($formData['mobile']) ? '*******' . substr($formData['mobile'], -3) : 'N/A';

        $photoPath = $formData['photo_upload'] ?? $formData['photo'] ?? '';
        $signaturePath = $formData['signature_upload'] ?? $formData['signature'] ?? '';
        
        $baseDirs = ['student_images/', 'images/'];
        
        if (!empty($photoPath) && !file_exists($photoPath)) {
            foreach($baseDirs as $dir) {
                if (file_exists($dir . basename($photoPath))) {
                    $photoPath = $dir . basename($photoPath);
                    break;
                }
            }
        }
        if (!empty($signaturePath) && !file_exists($signaturePath)) {
             foreach($baseDirs as $dir) {
                if (file_exists($dir . basename($signaturePath))) {
                    $signaturePath = $dir . basename($signaturePath);
                    break;
                }
            }
        }

        $photoExists = !empty($photoPath) && file_exists($photoPath);
        $signatureExists = !empty($signaturePath) && file_exists($signaturePath);
        $categoryName = $formData['category'] ?? '';
        if (is_numeric($categoryName) && function_exists('get_category_name')) $categoryName = get_category_name($categoryName) ?: $categoryName;
        
        $genderName = $formData['gender'] ?? '';
        if (is_numeric($genderName) && function_exists('get_gender_name')) $genderName = get_gender_name($genderName) ?: $genderName;

        $religionName = $formData['religion'] ?? '';
        if (is_numeric($religionName) && function_exists('get_religion_name')) $religionName = get_religion_name($religionName) ?: $religionName;
        ?>
        <div class="container-fluid m-auto cont mb-4 <?php echo ($label === 'STUDENT COPY') ? 'student-copy' : ''; ?>" style="page-break-after: always;">
            <?php 
               if (function_exists('print_header')) {
                   print_header('COLLEGE COPY'); 
               } else {
                   echo "<h3 class='text-center'>$label</h3>"; 
               }
            ?>
            <br>
            <div class="table-responsive m-2">
                <table width="100%" class="table table-striped table-hover rounded" style="border: 1px solid #dee2e6;">
                    <tr>
                        <th scope="col" width="18%">UIN NO.:</th>
                        <th scope="col" width="18%" style="white-space: nowrap;">
                            <?php echo htmlspecialchars((!empty($formData['uin']) && str_starts_with($formData['uin'], 'RM')) ? $formData['uin'] : ''); ?>
                        </th>
                        <th scope="col" width="16%"></th>
                        <th scope="col" style="white-space: nowrap; font-size: 0.7rem; padding: 4px 2px !important;">
                            <?php echo htmlspecialchars($formData['transaction_id'] ?? ''); ?>
                        </th>
                        <th rowspan="3" colspan="2" width="20%">
                            <?php if ($photoExists): ?>
                                <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="person Pic" class="img-fluid" style="width:132px; height: 125px; object-fit: cover; border: 1px solid #000; float: right; margin-right: 10px;">
                            <?php else: ?>
                                <div style="width:132px; height:132px; border:1px solid #ccc; float:right; margin-right:10px; display:flex; align-items:center; justify-content:center;">Photo</div>
                            <?php endif; ?>
                        </th>
                    </tr>
                    <tr>
                        <td scope="row"><b>COURSE NAME:</b></td>
                        <td colspan="3"><?php echo htmlspecialchars($courseName); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">APPLICANT NAME</th>
                        <td><?php echo htmlspecialchars($formData['candidate_name'] ?? ''); ?></td>
                        <td scope="row"><b>DATE OF BIRTH</b></td>
                        <td><?php echo htmlspecialchars($dobFormatted); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">FATHER'S NAME</th>
                        <td colspan="2"><?php echo htmlspecialchars($formData['fathers_name'] ?? ''); ?></td>
                        <th>Gender</th>
                        <td colspan="2"><?php echo htmlspecialchars($genderName ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">MOTHER'S NAME</th>
                        <td colspan="2"><?php echo htmlspecialchars($formData['mothers_name'] ?? ''); ?></td>
                        <th>RELIGION : </th>
                        <td colspan="2"><?php echo htmlspecialchars($religionName ?: 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th scope="row">EMAIL</th>
                        <td colspan="2"><?php echo htmlspecialchars($emailMasked); ?></td>
                        <th>MOBILE :</th>
                        <td colspan="2"><?php echo htmlspecialchars($mobileMasked); ?></td>
                    </tr>
                    <tr>
                        <th>CATEGORY :</th>
                        <td colspan="2"><?php echo htmlspecialchars($categoryName ?: 'N/A'); ?></td>
                        <th></th>
                        <th></th>
                    </tr>
                </table>
            </div>
            <table width="100%" style="margin:10px">
                <tr>
                    <td>
                        <br>
                        <h4 align="center"><b>DECLARATION</b></h4>
                        <p>I, <strong><?php echo htmlspecialchars($formData['candidate_name'] ?? ''); ?></strong> HEREBY DECLARE THAT ALL STATEMENTS MADE IN THIS APPLICATION ARE TRUE AND CORRECT TO THE BEST OF MY KNOWLEDGE AND BELIEF. IN CASE IF ANY INFORMATION IS BEING FOUND FALSE OR INCORRECT OR ANY IRREGULARITY IS BEING DETECTED ANY STAGE, MY CANDIDATURE IS LIABLE TO BE CANCELLED AND ACTION MAY BE INITIATED AGAINST ME. I UNDERSTAND THAT, IF MY APPLICATION IS FOUND INCOMPLETE IN ANY MANNER, THE SAME WILL BE REJECTED.</p>
                    </td>
                </tr>
                <tr>
                    <th style="text-align:right; margin-right:10px;">
                        <?php if ($signatureExists): ?>
                            <img src="<?php echo htmlspecialchars($signaturePath); ?>" alt="signature" class="img-fluid" style="width: 150px; height: 60px; object-fit: contain; border: 1px solid #ccc;"><br>
                        <?php else: ?>
                            <div style="width:150px; height:60px; border:1px solid #ccc; display:inline-block;"></div><br>
                        <?php endif; ?>
                        SIGNATURE OF APPLICANT
                    </th>
                </tr>
                <tr>
                    <th>PRINTED ON: <?php echo htmlspecialchars($currentDate); ?></th>
                </tr>
                <tr>
                    <td><br><h4 align="center"><b>DISCLAIMER</b></h4>
                    <h6>It is mandatory to submit UIN registration along with admission fee receipt (Duplicate Copy) in the Account Section.</h6></td>
                </tr>
                <?php if ($collegeSettings && (!empty($collegeSettings['email']) || !empty($collegeSettings['phone']))): ?>
                <tr>
                    <td style="text-align: center; padding-top: 15px;">
                        <?php if (!empty($collegeSettings['email'])): ?>
                            <strong>Email:</strong> <?php echo htmlspecialchars($collegeSettings['email']); ?>
                        <?php endif; ?>
                        <?php if (!empty($collegeSettings['email']) && !empty($collegeSettings['phone'])): ?> | <?php endif; ?>
                        <?php if (!empty($collegeSettings['phone'])): ?>
                            <strong>Phone:</strong> <?php echo htmlspecialchars($collegeSettings['phone']); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }
    $courseId = $_POST['course_id'] ?? '';
    $uinNo = $_POST['uin_no'] ?? '';
    $where = [];
    if (empty($courseId) && empty($uinNo)) {
        die("Please select a course or enter a UIN.");
    }

    global $db; 
    if (!empty($uinNo)) {
        $uinNo = $db->real_escape_string($uinNo);
        $where[] = "u.uin = '$uinNo'";
    } elseif (!empty($courseId)) {
        $courseId = $db->real_escape_string($courseId);
        $where[] = "u.course_applied_for = '$courseId'";
    }
    
    // Optimized Query with JOINs (fetching latest successful payment)
    $sql = "SELECT u.*, 
            COALESCE(cd.class_description, u.course_applied_for) AS class_description,
            COALESCE(up.transaction_id, up.order_id) AS payment_transaction_id
            FROM uin_register_student u
            LEFT JOIN class_detail cd ON u.course_applied_for = cd.sno
            LEFT JOIN (
                SELECT student_id, MAX(id) as max_pay_id
                FROM uin_student_payment
                WHERE payment_status='success'
                GROUP BY student_id
            ) latest_pay ON u.id = latest_pay.student_id
            LEFT JOIN uin_student_payment up ON latest_pay.max_pay_id = up.id
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY u.id ASC";

    $res = $db->query($sql);

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Bulk UIN Print</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
             body { font-family: "Roboto", sans-serif; font-size: .8rem; }
             h1 { font-size: 1.8rem !important; }
             h2 { font-size: 1.5rem !important; }
             h3 { font-size: 1.3rem !important; }
             h4 { font-size: 1rem !important; }
             p { font-size: .8rem !important; }
             td, th { font-size: .8rem !important; padding: 8px 10px !important; vertical-align: middle !important; }
             #overlays {
                 opacity: 0.05 !important;
             }
             .container-fluid.m-auto.cont {
                 max-width: 210mm;
                 margin: 0 auto;
                 padding: 10mm;
             }
             @media print {
                @page { 
                    size: A4; 
                    margin: 10mm;
                }
                * {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                body { 
                    padding: 0 !important; 
                    margin: 0 !important;
                }
                .btn-print { display: none !important; }
                .card-box { box-shadow: none; border: none; }
                .container-fluid.m-auto.cont { 
                    page-break-after: always !important; 
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .student-copy { display: none !important; }
                /* #overlays {
                    opacity: 0.1 !important;
                } */
             }
        </style>
    </head>
    <body class="w-100 m-auto">
        <div class="text-center my-3 btn-print">
            <button class="btn btn-secondary" onclick="window.print()">Print</button>
            <button class="btn btn-danger" onclick="window.location.href='bulk_uin_print_download.php'">Back to Admin</button>
        </div>

        <?php
        if ($res && $res->num_rows > 0) {
            while ($student = $res->fetch_assoc()) {
                $cName = $student['class_description'];
                
                // Use fetched Transaction ID
                if (!empty($student['payment_transaction_id'])) {
                    $student['transaction_id'] = $student['payment_transaction_id'];
                }

                renderCard('COLLEGE COPY', $student, $cName);
            }
        } else {
            echo "<h3 class='text-center mt-5'>No Students Found</h3>";
        }
        ?>
    </body>
    </html>
    <?php
    exit; 
}
include "script/settings.php";

if (function_exists('sidebar')) sidebar($db);
if (function_exists('page_header')) page_header();

// Load college settings for display
$collegeSettings = get_college_settings(1);

$courseOpts = [];
$cQ = $db->query("SELECT sno, class_description FROM class_detail ORDER BY class_description ASC");
if ($cQ) {
    while($r = $cQ->fetch_assoc()) $courseOpts[] = $r;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk UIN Print / Download</title>
    <style>
         
        .search-component .header-strip {
            background-color: transparent;
            color: #0d6efd;
            padding: 15px 20px;
            padding-left: 30px;
            font-size: 20px;
            font-weight: bold;
            border-radius: 4px 4px 0 0;
            position: relative;
        }
        .search-component .header-strip::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background-color: #0d6efd;
            border-radius: 2px;
        }
        .search-component .form-container {
            background: white;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1200px;
        }
        .search-component .form-body {
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }
        .search-component .form-label {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 8px;
            display: block;
            letter-spacing: 0.5px;
        }
        .search-component .form-control,
        .search-component .form-select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .search-component .form-control:focus,
        .search-component .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
            outline: none;
        }
        .search-component .form-control:disabled,
        .search-component .form-select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
        .search-component .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 10px 30px;
            color: white;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .search-component .btn-primary:hover {
            background-color: #084298;
            border-color: #084298;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .search-component .btn-primary:active {
            transform: translateY(0);
        }
        .search-component .row {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .search-component .form-group {
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
        }
        .search-component .col-md-6 {
            padding-left: 15px;
            padding-right: 15px;
        }
    </style>
</head>
<body class="bg-light">

<div class="search-component">
    <div class="form-container">
        <div class="header-strip">
            <?php 
                echo 'BULK UIN PRINT';
            ?>
        </div>
        <div class="form-body">
            <form method="POST" id="searchForm">
                <input type="hidden" name="search" value="1">
                
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="course_id" class="form-label">COURSE</label>
                        <select name="course_id" id="course_id" class="form-select form-control" onchange="handleInput('course')">
                            <option value="">---Select Course---</option>
                            <?php foreach ($courseOpts as $co): ?>
                                <option value="<?= $co['sno'] ?>"><?= $co['class_description'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 form-group">
                        <label for="uin_no" class="form-label">UIN NUMBER</label>
                        <input type="text" name="uin_no" id="uin_no" class="form-control" placeholder="Enter UIN Number" oninput="handleInput('uin')">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function handleInput(type) {
        const course = document.getElementById('course_id');
        const uin = document.getElementById('uin_no');

        if (type === 'course') {
            if (course.value !== "") {
                uin.value = "";
                uin.disabled = true;
            } else {
                uin.disabled = false;
            }
        } else if (type === 'uin') {
            if (uin.value.trim() !== "") {
                course.selectedIndex = 0;
                course.disabled = true;
            } else {
                course.disabled = false;
            }
        }
    }
</script>

</body>
</html>
<?php
if (function_exists('page_footer')) page_footer();
?>
