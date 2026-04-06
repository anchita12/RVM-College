<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../settings.php';


header('Content-Type: application/json');


if (function_exists('ob_get_length') && ob_get_length()) {
    ob_clean();
}

function send_json($payload) {
    if (function_exists('ob_get_length') && ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['success' => false, 'message' => 'Invalid request method']);
}
$candidate_name     = isset($_POST['candidate_name']) ? trim($_POST['candidate_name']) : '';
$fathers_name       = isset($_POST['fathers_name']) ? trim($_POST['fathers_name']) : '';
$mothers_name       = isset($_POST['mothers_name']) ? trim($_POST['mothers_name']) : '';
$dob_day            = $_POST['dob_day'] ?? '';
$dob_month          = $_POST['dob_month'] ?? '';
$dob_year           = $_POST['dob_year'] ?? '';
$mobile             = isset($_POST['mobile']) ? trim($_POST['mobile']) : '';
$email              = isset($_POST['email']) ? trim($_POST['email']) : '';
$course_type        = isset($_POST['course_type']) ? trim($_POST['course_type']) : '';
$course_applied_for = isset($_POST['course_applied_for']) ? trim($_POST['course_applied_for']) : '';
$category           = isset($_POST['category']) ? trim($_POST['category']) : '';
$aadhaar            = isset($_POST['aadhaar']) ? trim($_POST['aadhaar']) : '';
$p_house            = isset($_POST['p_house']) ? trim($_POST['p_house']) : '';
$p_post             = isset($_POST['p_post']) ? trim($_POST['p_post']) : '';
$p_tehsil           = isset($_POST['p_tehsil']) ? trim($_POST['p_tehsil']) : '';
$p_thana            = isset($_POST['p_thana']) ? trim($_POST['p_thana']) : '';
$p_district         = isset($_POST['p_district']) ? trim($_POST['p_district']) : '';
$p_state            = isset($_POST['p_state']) ? trim($_POST['p_state']) : '';
$p_pin              = isset($_POST['p_pin']) ? trim($_POST['p_pin']) : '';
$parents_mobile     = isset($_POST['parents_mobile']) ? trim($_POST['parents_mobile']) : '';
$weightage          = isset($_POST['weightage']) ? trim($_POST['weightage']) : (isset($_POST['Weightage']) ? trim($_POST['Weightage']) : '0');
$c_thana            = isset($_POST['c_thana']) ? trim($_POST['c_thana']) : '';

$errors = [];
if ($candidate_name === '') $errors[] = "Candidate name is required";
if ($fathers_name === '')   $errors[] = "Father's name is required";
if ($dob_day === '' || $dob_month === '' || $dob_year === '') $errors[] = "Date of birth is required";
if ($mobile === '' || !preg_match('/^[0-9]{10}$/', $mobile)) $errors[] = "Valid mobile number is required";
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required";
if ($course_type === '') $errors[] = "Course type is required";
if ($course_applied_for === '') $errors[] = "Course applying for is required";
if ($category === '') $errors[] = "Category is required";
if ($aadhaar === '' || !preg_match('/^[0-9]{12}$/', $aadhaar)) $errors[] = "Valid Aadhaar number is required";
if ($p_state === '') $errors[] = "State is required";

if (!empty($errors)) {
    send_json(['success' => false, 'message' => implode(', ', $errors)]);
}

$dob = sprintf('%04d-%02d-%02d', $dob_year, $dob_month, $dob_day);

$mobileCheck = $mysqli->prepare("SELECT id FROM uin_register_student WHERE mobile = ? LIMIT 1");
$mobileCheck->bind_param("s", $mobile);
$mobileCheck->execute();
$mobileResult = $mobileCheck->get_result();
if ($mobileResult && $mobileResult->num_rows > 0) {
    $mobileCheck->close();
    send_json(['success' => false, 'message' => 'Mobile number is already registered. Please use login option to continue.']);
}
$mobileCheck->close();

$existingStudent = null;
$checkStmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE email = ? AND mobile = ? LIMIT 1");
$checkStmt->bind_param("ss", $email, $mobile);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult && $checkResult->num_rows > 0) {
    $existingStudent = $checkResult->fetch_assoc();
}
$checkStmt->close();

$year = date('Y');
$student_id = 0;
$courseTypeColumnExists = false;
$registrationNoColumnExists = false;

$columnCheck = $mysqli->query("SHOW COLUMNS FROM uin_register_student LIKE 'course_type'");
if ($columnCheck) {
    $courseTypeColumnExists = $columnCheck->num_rows > 0;
    $columnCheck->free();
}

$regColCheck = $mysqli->query("SHOW COLUMNS FROM uin_register_student LIKE 'registration_no'");
if ($regColCheck) {
    $registrationNoColumnExists = $regColCheck->num_rows > 0;
    $regColCheck->free();
}

$regColumnName = $registrationNoColumnExists ? 'registration_no' : 'uin';
$result = $mysqli->query("SELECT COUNT(*) as count FROM uin_register_student WHERE {$regColumnName} LIKE '{$year}%'");
if (!$result) {
    send_json(['success' => false, 'message' => 'Database query error: ' . $mysqli->error]);
}
$row = $result->fetch_assoc();
$result->free();
$count = ($row['count'] ?? 0) + 1;
$registration_no = $year . str_pad($count, 6, '0', STR_PAD_LEFT);

$uin = '';

if ($existingStudent) {
    $student_id = $existingStudent['id'];
    if ($registrationNoColumnExists && !empty($existingStudent['registration_no'])) {
        $registration_no = $existingStudent['registration_no'];
    } elseif (!empty($existingStudent['uin'])) {
        $registration_no = $existingStudent['uin'];
    }
    $uin = $existingStudent['uin'] ?? '';
}

$insertColumns = [
    'uin'            => $uin,
    'candidate_name' => $candidate_name,
    'fathers_name'   => $fathers_name,
    'mothers_name'   => $mothers_name,
];

if ($courseTypeColumnExists) {
    $insertColumns['course_type'] = $course_type;
}

if ($registrationNoColumnExists) {
    $insertColumns['registration_no'] = $registration_no;
}

$updateColumns = array_merge($insertColumns, [
    'course_applied_for' => $course_applied_for,
    'category'           => $category,
    'dob'                => $dob,
    'aadhaar'            => $aadhaar,
    'email'              => $email,
    'mobile'             => $mobile,
    'p_house'            => $p_house,
    'p_post'             => $p_post,
    'p_tehsil'           => $p_tehsil,
    'p_thana'            => $p_thana,
    'p_district'         => $p_district,
    'p_state'            => $p_state,
    'p_pin'              => $p_pin,
    'parents_mobile'     => $parents_mobile,
    'weightage'          => $weightage,
    'c_thana'            => $c_thana
]);

if ($existingStudent) {
    $updateFields = [];
    $updateValues = [];
    foreach ($updateColumns as $key => $value) {
        if ($key !== 'uin') {
            $updateFields[] = "$key = ?";
            $updateValues[] = $value;
        }
    }
    $updateValues[] = $student_id;

    $updateSql = "UPDATE uin_register_student SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $mysqli->prepare($updateSql);
    if (!$stmt) {
        send_json(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    }

    $types = str_repeat('s', count($updateValues) - 1) . 'i';
    $bindParams = [$types];
    foreach ($updateValues as $idx => $val) {
        $bindParams[] = &$updateValues[$idx];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    if ($stmt->execute()) {
        $stmt->close();
    } else {
        $msg = 'Update failed: ' . $stmt->error;
        $stmt->close();
        send_json(['success' => false, 'message' => $msg]);
    }
} else {
    $placeholders = implode(', ', array_fill(0, count($updateColumns), '?'));
    $insertSql = sprintf(
        "INSERT INTO uin_register_student (%s, status) VALUES (%s, 'pending')",
        implode(', ', array_keys($updateColumns)),
        $placeholders
    );

    $stmt = $mysqli->prepare($insertSql);
    if (!$stmt) {
        send_json(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    }

    $types = str_repeat('s', count($updateColumns));
    $bindValues = array_values($updateColumns);
    $bindParams = [$types];
    foreach ($bindValues as $idx => $val) {
        $bindParams[] = &$bindValues[$idx];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);

    if ($stmt->execute()) {
        $student_id = $mysqli->insert_id;
        $stmt->close();
    } else {
        $msg = 'Registration failed: ' . $stmt->error;
        $stmt->close();
        send_json(['success' => false, 'message' => $msg]);
    }
}

$_SESSION['uin_form_data'] = [
    'student_id'         => $student_id,
    'uin'                => $uin,
    'registration_no'    => $registration_no,
    'candidate_name'     => $candidate_name,
    'fathers_name'       => $fathers_name,
    'mothers_name'       => $mothers_name,
    'dob'                => $dob,
    'mobile'             => $mobile,
    'email'              => $email,
    'course_type'        => $course_type,
    'course_applied_for' => $course_applied_for,
    'category'           => $category,
    'aadhaar'            => $aadhaar,
    'p_house'            => $p_house,
    'p_post'             => $p_post,
    'p_tehsil'           => $p_tehsil,
    'p_thana'            => $p_thana,
    'p_district'         => $p_district,
    'p_state'            => $p_state,
    'p_pin'              => $p_pin
];

send_json([
    'success'      => true,
    'message'      => $existingStudent ? 'Pre-registration updated successfully' : 'Pre-registration successful',
    'student_id'   => $student_id,
    'uin'          => $uin,
    'redirect'     => 'uin_reg_form.php?step=2'
]);

