<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ob_start();

require_once __DIR__ . '/../settings.php';



$preOutput = ob_get_contents();
if (!empty(trim($preOutput))) {
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Database initialization error. Please contact support.']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}

ob_clean();

header('Content-Type: application/json');

if (isset($mysqli) && $mysqli->connect_error) {
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Invalid request method']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}

$student_id = (int)($_POST['student_id'] ?? 0);
$uin = trim($_POST['uin'] ?? '');
$amount = floatval($_POST['amount'] ?? 100);

if (empty($student_id) || $student_id <= 0) {
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Invalid student ID. Please refresh the page and try again.']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}

$registration_no = $uin;
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

if (empty($registration_no)) {
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Registration number is missing. Please refresh the page and try again.']);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}
$order_id = 'ORDER_' . $registration_no . '_' . time();

$transaction_id = 'TXN_' . strtoupper(uniqid());
$payment_status = 'success';
$payment_date = date('Y-m-d H:i:s');

if (!isset($_SESSION['uin_form_data']) || !is_array($_SESSION['uin_form_data'])) {
    $_SESSION['uin_form_data'] = [];
}

$_SESSION['uin_form_data']['payment_completed'] = true;
$_SESSION['uin_form_data']['order_id'] = $order_id;
$_SESSION['uin_form_data']['transaction_id'] = $transaction_id;

try {
    $stmt = $mysqli->prepare("
        INSERT INTO uin_student_payment (
            student_id, registration_no, order_id, transaction_id, amount, 
            payment_status, payment_date, gateway_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'SIMULATED')
    ");

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $mysqli->error);
    }

    $stmt->bind_param("isssdss", $student_id, $registration_no, $order_id, $transaction_id, $amount, $payment_status, $payment_date);

    if (!$stmt->execute()) {
        throw new Exception('Payment execution failed: ' . $stmt->error);
    }

    $update_stmt = $mysqli->prepare("UPDATE uin_register_student SET status = 'payment_completed' WHERE id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $student_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    $stmt->close();
    
    $response = [
        'success' => true,
        'message' => 'Payment successful',
        'transaction_id' => $transaction_id,
        'student_id' => $student_id,
        'redirect' => 'uin_reg_form.php?step=3&payment=success&student_id=' . $student_id
    ];
    
    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode($response);
    
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo $jsonOutput;
    exit;
    
} catch (Exception $e) {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }

    ob_clean();
    header('Content-Type: application/json');
    $jsonOutput = json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo $jsonOutput;
    exit;
}
