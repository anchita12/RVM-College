<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {

$student_id = (int)($_POST['student_id'] ?? 0);

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

$fromEdit = isset($_POST['from_edit']) && $_POST['from_edit'] == '1';



// Do NOT generate UIN at admission save stage. UIN will be generated only on Final Submit (via print_admission_form Final Submit button)
$checkStatus = $mysqli->prepare("SELECT uin, registration_no FROM uin_register_student WHERE id = ?");
$checkStatus->bind_param("i", $student_id);
$checkStatus->execute();
$statusResult = $checkStatus->get_result();
$statusData = $statusResult->fetch_assoc();
$checkStatus->close();

// $generatedUIN intentionally left null here; UIN generation happens only on Final Submit (uin_print.php)
$generatedUIN = null;

if (!$fromEdit && !empty($statusData['uin'])) {
    // Original check for duplicate submission - only if it was already finalized?
    // Actually, if we just generated it above, this check might fail if we don't distinguish
    // "just generated" vs "generated long ago".
    // But wait, we haven't SAVED it yet. So $statusData['uin'] comes from DB.
    // If DB has UIN, that means it was done before.
    echo json_encode(['success' => false, 'message' => 'UIN already generated for this student. UIN: ' . $statusData['uin']]);
    exit;
}




$upload_base = dirname(dirname(__DIR__)) . '/student_images/';
if (!file_exists($upload_base)) {
    if (!mkdir($upload_base, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory: ' . $upload_base]);
        exit;
    }
}

$photo_upload = '';
$signature_upload = '';

$aadhar_file = '';
$marksheet_10_file = '';
$marksheet_12_file = '';
$marksheet_ug_file = '';
$pan_card_file = '';

if (isset($_FILES['photo_upload']) && $_FILES['photo_upload']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['photo_upload']['name'], PATHINFO_EXTENSION));
    $photoFolder = $upload_base . 'photo/';
    if (!file_exists($photoFolder)) {
        mkdir($photoFolder, 0777, true);
    }
    $photoFileName = $student_id . '.' . $ext;
    $photoFullPath = $photoFolder . $photoFileName;
    
    if (!move_uploaded_file($_FILES['photo_upload']['tmp_name'], $photoFullPath)) {
        error_log('Photo upload failed: ' . $_FILES['photo_upload']['error'] . ' to ' . $photoFullPath);
        echo json_encode(['success' => false, 'message' => 'Failed to upload photo. Check folder permissions.']);
        exit;
    }
    $photo_upload = 'student_images/photo/' . $photoFileName;
}

if (isset($_FILES['signature_upload']) && $_FILES['signature_upload']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['signature_upload']['name'], PATHINFO_EXTENSION));
    $signatureFolder = $upload_base . 'signature/';
    if (!file_exists($signatureFolder)) {
        mkdir($signatureFolder, 0777, true);
    }
    $signatureFileName = $student_id . '.' . $ext;
    $signatureFullPath = $signatureFolder . $signatureFileName;
    
    if (!move_uploaded_file($_FILES['signature_upload']['tmp_name'], $signatureFullPath)) {
        error_log('Signature upload failed: ' . $_FILES['signature_upload']['error'] . ' to ' . $signatureFullPath);
        echo json_encode(['success' => false, 'message' => 'Failed to upload signature. Check folder permissions.']);
        exit;
    }
    $signature_upload = 'student_images/signature/' . $signatureFileName;
}


$allowedImageExt = ['jpg', 'jpeg', 'png'];
function processImageUpload($fieldName, $folderName, $student_id, $upload_base, $allowedExt) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG images are allowed for document uploads.']);
        exit;
    }
    $docFolder = $upload_base . $folderName . '/';
    if (!file_exists($docFolder)) {
        mkdir($docFolder, 0755, true);
    }
    $fileName = $student_id . '.' . $ext;
    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $docFolder . $fileName)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload document image.']);
        exit;
    }
    return 'student_images/' . $folderName . '/' . $fileName;
}


$aadhar_file      = processImageUpload('aadhar_file', 'aadhar_upload', $student_id, $upload_base, $allowedImageExt);
$marksheet_10_file = processImageUpload('marksheet_10_file', 'marksheet10', $student_id, $upload_base, $allowedImageExt);
$marksheet_12_file = processImageUpload('marksheet_12_file', 'marksheet12', $student_id, $upload_base, $allowedImageExt);
$marksheet_ug_file = processImageUpload('marksheet_ug_file', 'other_qualification', $student_id, $upload_base, $allowedImageExt);
$pan_card_file     = processImageUpload('pan_card_file', 'pancard', $student_id, $upload_base, $allowedImageExt);

$candidate_name = trim($_POST['candidate_name'] ?? '');
$fathers_name = trim($_POST['fathers_name'] ?? '');
$mothers_name = trim($_POST['mothers_name'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$aadhaar = trim($_POST['aadhaar'] ?? '');
$email = trim($_POST['email'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$whatsapp_mobile = trim($_POST['whatsapp_mobile'] ?? '');
$parents_mobile = trim($_POST['parents_mobile'] ?? '');
if (!empty($parents_mobile) && !empty($mobile) && $parents_mobile === $mobile) {
    echo json_encode(['success' => false, 'message' => 'Parents mobile number must be different from your mobile number']);
    exit;
}

$course_applied_for = trim($_POST['course_applied_for'] ?? '');
$category = trim($_POST['category'] ?? '');
$religion = trim($_POST['religion'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$domicile = trim($_POST['domicile'] ?? '');
$mother_tongue = trim($_POST['mother_tongue'] ?? '');
$blood_group = trim($_POST['blood_group'] ?? '');
$weightage = trim($_POST['weightage'] ?? '');
$status = trim($_POST['status'] ?? 'Regular');

$p_house = trim($_POST['p_house'] ?? '');
$p_post = trim($_POST['p_post'] ?? '');
$p_tehsil = trim($_POST['p_tehsil'] ?? '');
$p_thana = trim($_POST['p_thana'] ?? '');
$p_district = trim($_POST['p_district'] ?? '');
$p_state = trim($_POST['p_state'] ?? '');
$p_pin = trim($_POST['p_pin'] ?? '');

$c_house = trim($_POST['c_house'] ?? '');
$c_post = trim($_POST['c_post'] ?? '');
$c_tehsil = trim($_POST['c_tehsil'] ?? '');
$c_thana = trim($_POST['c_thana'] ?? '');
$c_district = trim($_POST['c_district'] ?? '');
$c_state = trim($_POST['c_state'] ?? '');
$c_pin = trim($_POST['c_pin'] ?? '');
$update_fields = [];
$params = [];
$types = '';


$fields_map = [
    'candidate_name' => $candidate_name,
    'fathers_name' => $fathers_name,
    'mothers_name' => $mothers_name,
    'dob' => $dob,
    'aadhaar' => $aadhaar,
    'email' => $email,
    'mobile' => $mobile,
    'whatsapp_mobile' => $whatsapp_mobile,
    'parents_mobile' => $parents_mobile,
    'course_applied_for' => $course_applied_for,
    'category' => $category,
    'religion' => $religion,
    'gender' => $gender,
    'domicile' => $domicile,
    'mother_tongue' => $mother_tongue,
    'blood_group' => $blood_group,
    // DB column may be cased as `Weightage`; use that key to ensure update sticks
    'Weightage' => $weightage,
    'status' => $status,
    'p_house' => $p_house,
    'p_post' => $p_post,
    'p_tehsil' => $p_tehsil,
    'p_thana' => $p_thana,
    'p_district' => $p_district,
    'p_state' => $p_state,
    'p_pin' => $p_pin,
    'c_house' => $c_house,
    'c_post' => $c_post,
    'c_tehsil' => $c_tehsil,
    'c_thana' => $c_thana,
    'c_district' => $c_district,
    'c_state' => $c_state,
    'c_pin' => $c_pin
];

foreach ($fields_map as $field => $value) {
    $update_fields[] = "{$field} = ?";
    $params[] = $value;
    $types .= 's';
}

if (!empty($photo_upload)) {
    $update_fields[] = "photo_upload = ?";
    $params[] = $photo_upload;
    $types .= 's';
}

if (!empty($signature_upload)) {
    $update_fields[] = "signature_upload = ?";
    $params[] = $signature_upload;
    $types .= 's';
}


if (!empty($aadhar_file)) {
    $update_fields[] = "aadhar_upload = ?";
    $params[] = $aadhar_file;
    $types .= 's';
}
if (!empty($marksheet_10_file)) {
    $update_fields[] = "result_10 = ?";
    $params[] = $marksheet_10_file;
    $types .= 's';
}
if (!empty($marksheet_12_file)) {
    $update_fields[] = "result_12 = ?";
    $params[] = $marksheet_12_file;
    $types .= 's';
}
if (!empty($marksheet_ug_file)) {
    $update_fields[] = "ug_marksheet = ?";
    $params[] = $marksheet_ug_file;
    $types .= 's';
}
if (!empty($pan_card_file)) {
    $update_fields[] = "pan_card = ?";
    $params[] = $pan_card_file;
    $types .= 's';
}


// UIN generation block removed as it should only happen on Final Submit.


$params[] = $student_id;
$types .= 'i';

$sql = "UPDATE uin_register_student SET " . implode(', ', $update_fields) . " WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    if (isset($_POST['qualification']) && is_array($_POST['qualification'])) {

        $del_stmt = $mysqli->prepare("DELETE FROM uin_student_qualification WHERE student_id = ?");
        if ($del_stmt) {
            $del_stmt->bind_param("i", $student_id);
            $del_stmt->execute();
            $del_stmt->close();
        }
        
        
        $qual_stmt = $mysqli->prepare("
            INSERT INTO uin_student_qualification (
                student_id, exam_name, board, college_name, passing_year, roll_no,
                total_marks, marks_obtained, percentage, grade, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($qual_stmt) {
            foreach ($_POST['qualification'] as $qual) {
                $exam_name = trim($qual['exam_name'] ?? '');
                $board = trim($qual['board'] ?? '');
                $college_name = trim($qual['college_name'] ?? '');
                $passing_year = trim($qual['passing_year'] ?? '');
                $roll_no = trim($qual['roll_no'] ?? '');
                $total_marks = trim($qual['total_marks'] ?? '');
                $marks_obtained = trim($qual['marks_obtained'] ?? '');
                $percentage = trim($qual['percentage'] ?? '');
                $grade = trim($qual['grade'] ?? '');
                $status = trim($qual['status'] ?? 'Passed');
                
                if (!empty($exam_name)) {
                    $qual_stmt->bind_param("issssssssss", 
                        $student_id, $exam_name, $board, $college_name, $passing_year, $roll_no,
                        $total_marks, $marks_obtained, $percentage, $grade, $status
                    );
                    if (!$qual_stmt->execute()) {
                       
                        error_log('Qualification insert error: ' . $qual_stmt->error);
                    }
                }
            }
            $qual_stmt->close();
        }
    }
    

    
    $registration_no = trim($_POST['registration_no'] ?? '');
    if (empty($registration_no)) {
        
        $regCheck = $mysqli->prepare("SELECT registration_no FROM uin_register_student WHERE id = ?");
        if ($regCheck) {
            $regCheck->bind_param("i", $student_id);
            $regCheck->execute();
            $regResult = $regCheck->get_result();
            if ($regRow = $regResult->fetch_assoc()) {
                $registration_no = $regRow['registration_no'] ?? '';
            }
            $regCheck->close();
        }
    }
    
    if (!empty($registration_no)) {
        $pay_stmt = $mysqli->prepare("UPDATE uin_student_payment SET registration_no = ? WHERE student_id = ?");
        if ($pay_stmt) {
            $pay_stmt->bind_param("si", $registration_no, $student_id);
            $pay_stmt->execute();
            $pay_stmt->close();
        }
    }
    
    
    if ($fromEdit) {
        
        $redirectUrl = 'uin_edit.php?success=1&student_id=' . $student_id;
    } else {
        $redirectUrl = 'uin_reg_form.php?action=print&student_id=' . $student_id;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Admission form submitted successfully',
        'redirect' => $redirectUrl
    ]);
} else {
    $error_msg = $stmt->error;
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $error_msg]);
    exit;
}

$stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    exit;
}
?>

