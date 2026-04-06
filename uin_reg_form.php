<?php
require_once __DIR__ . '/scripts/settings.php';

// Step management
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 4) {
    $step = 1;
}


$showPreregistration = isset($_GET['action']) && $_GET['action'] === 'preregistration';
$isNewRegistration = ($showPreregistration && isset($_GET['mode']) && $_GET['mode'] === 'new');
if ($isNewRegistration) {
    $_SESSION['uin_form_data'] = [];
}


$loginError = '';
$loginErrorTimeout = 5000;

if (!empty($_SESSION['login_flash'])) {
    $loginError = $_SESSION['login_flash'];
    $loginErrorTimeout = $_SESSION['login_flash_timeout'] ?? 5000;
    unset($_SESSION['login_flash'], $_SESSION['login_flash_timeout']);
}

$registrationColumn = 'uin';
$colCheckReg = $mysqli->query("SHOW COLUMNS FROM uin_register_student LIKE 'registration_no'");
if ($colCheckReg && $colCheckReg->num_rows > 0) {
    $registrationColumn = 'registration_no';
}
if ($colCheckReg) {
    $colCheckReg->free();
}

if (isset($_GET['reg_no']) && !empty($_GET['reg_no'])) {
    $regNo = trim($_GET['reg_no']);
    $loginOption = $_GET['login_option'] ?? 'payment_due';
    
    if ($loginOption === 'payment_due' && isset($_GET['dob']) && !empty($_GET['dob'])) {
       
        $dob = trim($_GET['dob']);
        $sql = "SELECT * FROM uin_register_student WHERE {$registrationColumn} = ? AND dob = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ss", $regNo, $dob);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        if ($student) {
            if (!empty($student['uin'])) {
                echo "<script>
                    alert('⚠️ UIN Already Generated!\\n\\nRegistration Number: {$regNo}\\nUIN: {$student['uin']}\\nName: {$student['candidate_name']}\\n\\nYour admission is already completed. You cannot register again.\\n\\nClick OK to go back.');
                    window.location.href = 'index.php';
                </script>";
                exit;
            }
            
            $paymentCheck = $mysqli->prepare("SELECT * FROM uin_student_payment WHERE student_id = ? AND payment_status = 'success'");
            $paymentCheck->bind_param("i", $student['id']);
            $paymentCheck->execute();
            $paymentResult = $paymentCheck->get_result();
            $hasPayment = $paymentResult->num_rows > 0;
            $paymentCheck->close();
            
            if ($hasPayment) {
                $loginError = 'Payment already completed. Please use the second option to login.';
            } else {
                $_SESSION['uin_form_data'] = array_merge($_SESSION['uin_form_data'] ?? [], $student);
                $_SESSION['uin_form_data']['student_id'] = $student['id'];
                header('Location: uin_reg_form.php?step=2');
                exit;
            }
        } else {
            $loginError = 'Invalid Registration Number or Date of Birth.';
        }
    } elseif ($loginOption === 'payment_done' && isset($_GET['txn_id']) && !empty($_GET['txn_id'])) {
       
        $txnId = trim($_GET['txn_id']);
        $sql = "SELECT s.* FROM uin_register_student s 
                INNER JOIN uin_student_payment p ON s.id = p.student_id 
                WHERE s.{$registrationColumn} = ? AND p.transaction_id = ? AND p.payment_status = 'success'";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ss", $regNo, $txnId);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
        
        if ($student) {
            
            if (isset($student['status']) && strtolower($student['status']) === 'completed') {
                $_SESSION['login_flash'] = 'Admission form already submitted for this Registration Number. Please do not fill it again.';
                $_SESSION['login_flash_timeout'] = 1000;
                header('Location: uin_reg_form.php?step=1');
                exit;
            } else {
               
                $_SESSION['uin_form_data'] = array_merge($_SESSION['uin_form_data'] ?? [], $student);
                $_SESSION['uin_form_data']['student_id'] = $student['id'];
                header('Location: uin_reg_form.php?step=3');
                exit;
            }
        } else {
            $loginError = 'Invalid Registration Number or Transaction ID.';
        }
    }
}

$college = get_college_settings(1);
$printHeader = get_college_settings(2);
if (!$printHeader) {
    $printHeader = $college;
}

if (isset($_GET['action']) && $_GET['action'] === 'print') {
    if (isset($_GET['student_id'])) {
        $studentId = (int)$_GET['student_id'];
        if ($studentId > 0) {
            $_SESSION['uin_form_data']['student_id'] = $studentId;
            header('Location: uin_reg_form.php?action=print');
            exit;
        }
    }
    
    $studentId = isset($_SESSION['uin_form_data']['student_id']) ? (int)$_SESSION['uin_form_data']['student_id'] : 0;

    if ($studentId <= 0) {
        echo 'Invalid student reference.';
        exit;
    }

    $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    $student = $studentResult->fetch_assoc();
    $stmt->close();

    if (!$student) {
        echo 'No record found for the provided student ID.';
        exit;
    }

    $qualifications = [];
    $qualStmt = $mysqli->prepare("SELECT * FROM uin_student_qualification WHERE student_id = ? ORDER BY id");
    if ($qualStmt) {
        $qualStmt->bind_param("i", $studentId);
        $qualStmt->execute();
        $qualResult = $qualStmt->get_result();
        while ($row = $qualResult->fetch_assoc()) {
            $qualifications[] = $row;
        }
        $qualStmt->close();
    }

    include __DIR__ . '/uin_steps/print_admission_form.php';
    exit;
}


$formData = $_SESSION['uin_form_data'] ?? [];
if (!empty($formData['dob'])) {
    $dobDate = DateTime::createFromFormat('Y-m-d', $formData['dob']);
    if ($dobDate) {
        if (!isset($formData['dob_day'])) {
            $formData['dob_day'] = (int)$dobDate->format('d');
        }
        if (!isset($formData['dob_month'])) {
            $formData['dob_month'] = (int)$dobDate->format('m');
        }
        if (!isset($formData['dob_year'])) {
            $formData['dob_year'] = (int)$dobDate->format('Y');
        }
        $_SESSION['uin_form_data'] = $formData;
    }
}

if ($step === 2 && isset($_GET['student_id'])) {
    $formData['student_id'] = (int)$_GET['student_id'];
    $_SESSION['uin_form_data'] = $formData;
}

$paymentAlreadyDone = false;
if ($step === 2 && isset($formData['student_id'])) {
    $studentId = (int)$formData['student_id'];
    $payCheck = $mysqli->prepare("SELECT 1 FROM uin_student_payment WHERE student_id = ? AND payment_status = 'success' LIMIT 1");
    if ($payCheck) {
        $payCheck->bind_param("i", $studentId);
        $payCheck->execute();
        $payResult = $payCheck->get_result();
        if ($payResult && $payResult->num_rows > 0) {
            // Payment already done, user should not be allowed to go back to Step 2
            $_SESSION['uin_form_data']['student_id'] = $studentId; // Ensure session is set
            header('Location: uin_reg_form.php?step=3');
            exit;
        }
        $paymentAlreadyDone = false;
        $payCheck->close();
    }
}

if ($step === 2 && isset($formData['student_id'])) {
    $studentId = (int)$formData['student_id'];
    $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $studentData = $result->fetch_assoc();
        $formData = array_merge($formData, $studentData);
        $_SESSION['uin_form_data'] = $formData;
    }
    $stmt->close();
}

if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_GET['student_id'])) {
    $step = 3;
    $formData['student_id'] = (int)$_GET['student_id'];
    $_SESSION['uin_form_data'] = $formData;
}

if ($step === 3) {
    $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : (isset($formData['student_id']) ? (int)$formData['student_id'] : 0);
    if ($studentId > 0) {
        $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $studentData = $result->fetch_assoc();
            $formData = array_merge($formData, $studentData);
            $formData['student_id'] = $studentId;
            $_SESSION['uin_form_data'] = $formData;
        }
        $stmt->close();
    }
}

if ($step === 4 && isset($_GET['student_id'])) {
    $studentId = (int)$_GET['student_id'];

    $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $formData = array_merge($formData, $result->fetch_assoc());
    }
    $stmt->close();
    $paymentData = null;
    $payStmt = $mysqli->prepare("SELECT * FROM uin_student_payment WHERE student_id = ? AND payment_status = 'success' ORDER BY id DESC LIMIT 1");
    if ($payStmt) {
        $payStmt->bind_param("i", $studentId);
        $payStmt->execute();
        $payResult = $payStmt->get_result();
        if ($payResult && $payResult->num_rows > 0) {
            $paymentData = $payResult->fetch_assoc();
            $formData['transaction_id'] = $paymentData['transaction_id'] ?? '';
            $formData['payment_amount'] = $paymentData['amount'] ?? '';
        }
        $payStmt->close();
    }

    if (isset($_GET['success']) && $_GET['success'] === '1') {
        $_SESSION['uin_form_data'] = [];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration 2025 | Unique Identification Number (UIN) - <?php echo htmlspecialchars($college['college_name'] ?? 'College'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/uin_form.css">
    <script>
    history.replaceState({ page: 'uin_reg_form' }, '', location.href);
    window.addEventListener('popstate', function () {
        window.location.href = 'index.php';
    });
    </script>
    <style>
        .hover-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15) !important;
        }
        .fixed-top-section {
    max-width: 1100px;   
    margin-left: auto;
    margin-right: auto;
}
        
       
        .process-steps-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .process-header {
            background: #1e3a8a;
            color: #fff;
            padding: 12px 20px;
            font-weight: bold;
            font-size: 1.1rem;
            text-align: center;
            width: 100%;
        }
        
        .process-steps-list {
            padding: 0;
            margin: 0;
            list-style: none;
            flex: 1;
            overflow-y: auto;
        }
        
        .process-step-item {
            padding: 10px 20px;
            margin: 0;
            border-bottom: 1px solid #d0d0d0;
            font-size: 0.9rem;
            color: #333;
            line-height: 1.6;
            cursor: default;
        }
        
        .process-step-item:last-child {
            border-bottom: none;
        }
        
        .step-odd {
            background: #f5f5f5;
        }
        
        .step-even {
            background: #e8e8e8;
        }
        
        .step-number {
            text-decoration: underline;
            font-weight: bold;
            color: #333;
        }
        

        .process-step-item:hover {
            background: inherit !important;
            transform: none !important;
            box-shadow: none !important;
        }
        
        .step-odd:hover {
            background: #f5f5f5 !important;
        }
        
        .step-even:hover {
            background: #e8e8e8 !important;
        }
        
        .btn {
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(30,58,138,0.25);
            border-color: #3b82f6;
        }
        
        .uin-slip-card, .payment-status-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
        }
        
        .uin-slip-card {
            background-blend-mode: overlay;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .uin-slip-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 15px 40px rgba(30,58,138,0.35) !important;
            background: linear-gradient(135deg, rgba(240,244,248,0.6) 0%, rgba(226,232,240,0.6) 100%), url('images/uin_slip.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-blend-mode: overlay !important;
        }
        
        .uin-slip-card:hover > div[style*="background: rgba(255,255,255,0.95)"] {
            background: rgba(255,255,255,1) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            transform: scale(1.05);
        }
        
        .uin-slip-card:hover i.fa-arrow-right {
            transform: translateX(8px);
            transition: transform 0.3s ease;
        }
        
        .payment-status-card {
            background-blend-mode: overlay;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .payment-status-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 15px 40px rgba(25,135,84,0.35) !important;
            background: linear-gradient(135deg, rgba(240,249,255,0.6) 0%, rgba(224,242,254,0.6) 100%), url('images/payment.jpg') !important;
            background-size: cover !important;
            background-position: center !important;
            background-blend-mode: overlay !important;
        }
        
        .payment-status-card:hover > div[style*="background: rgba(255,255,255,0.95)"] {
            background: rgba(255,255,255,1) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            transform: scale(1.05);
        }
        
        .payment-status-card:hover i.fa-arrow-right {
            transform: translateX(8px);
            transition: transform 0.3s ease;
        }
        
        .uin-slip-card i,
        .payment-status-card i {
            transition: all 0.3s ease;
        }
        
        .uin-slip-bg-pattern {
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(30,58,138,0.03) 10px, rgba(30,58,138,0.03) 20px),
                repeating-linear-gradient(-45deg, transparent, transparent 10px, rgba(30,58,138,0.03) 10px, rgba(30,58,138,0.03) 20px);
        }
        
        .payment-status-bg-pattern {
            background-image: 
                repeating-linear-gradient(90deg, transparent, transparent 15px, rgba(21,128,61,0.03) 15px, rgba(21,128,61,0.03) 30px),
                repeating-linear-gradient(0deg, transparent, transparent 15px, rgba(21,128,61,0.03) 15px, rgba(21,128,61,0.03) 30px);
        }
        
        .uin-slip-card:hover .uin-slip-bg-pattern {
            opacity: 0.25 !important;
            transition: opacity 0.3s ease;
        }
        
        .payment-status-card:hover .payment-status-bg-pattern {
            opacity: 0.25 !important;
            transition: opacity 0.3s ease;
        }
        
        .uin-slip-card:hover svg path {
            stroke: #1e40af;
            transition: stroke 0.3s ease;
        }
        
        .payment-status-card:hover svg path {
            stroke: #16a34a;
            transition: stroke 0.3s ease;
        }
        
        
        .panel {
            margin-bottom: 20px;
            background-color: #fff;
            border: 1px solid transparent;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.05);
            transition: all 0.3s ease;
        }
        .panel-default {
            border-color: #ddd;
        }
        .process-panel {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        
        .process-panel,
        .login-card {
            min-height: 480px;
            height: auto;
            max-height: none;
        }
        .process-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important;
            border-color: #0d6efd;
        }
        .process-header-box {
            transition: all 0.3s ease;
            padding: 12px 15px;
        }
        .process-panel:hover .process-header-box {
            background: linear-gradient(135deg, #0d6efd 0%, #3b82f6 100%) !important;
            box-shadow: 0 2px 8px rgba(13,110,253,0.3);
        }
        .panel-body {
            padding: 15px;
            overflow: visible;
        }
        .panel .table th {
            border: none;
            padding: 8px;
            transition: all 0.3s ease;
            cursor: default;
        }
        .panel .table tr {
            transition: all 0.3s ease;
        }
        .panel .table tr:hover {
            background-color: #fff3cd !important;
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(255,193,7,0.3);
        }
        .panel .table tr:hover th {
            color: #f97316 !important;
            font-weight: 700;
            padding-left: 12px;
        }
        .panel .table tr:hover th u {
            color: #f97316 !important;
            text-decoration: underline;
        }
        .bg-primary {
            background-color: #0d6efd !important;
        }
        .text-white {
            color: #fff !important;
        }
       
        .login-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(13,110,253,0.25) !important;
            border-left-width: 6px;
        }
        .login-card .card-body {
            transition: all 0.3s ease;
        }
        .login-card:hover .card-body {
            background: linear-gradient(to bottom, #ffffff 0%, #f8f9ff 100%);
        }
        .login-card .form-control {
            transition: all 0.3s ease;
        }
        .login-card .form-control:focus {
            transform: scale(1.02);
            box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
        }
        .login-card .btn {
            transition: all 0.3s ease;
        }
        .login-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13,110,253,0.4);
        }
        
        /* Title row layout */
        .form-title-section .title-row {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .form-title-section .title-row h1 {
            margin: 0;
            text-align: center;
            width: 100%;
        }
        .form-title-section .title-row .back-btn {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            white-space: nowrap;
        }
        @media (min-width: 992px) {
            .fixed-top-section {
                max-width: 1200px;
            }
            .process-panel,
            .login-card {
                min-height: 520px;
            }
        }
        
        /* Mobile tweaks */
        @media (max-width: 768px) {
            .fixed-top-section {
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
            }
            .form-title-section .d-flex {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            .form-title-section .back-btn {
                position: static;
                transform: none;
                width: 100%;
                text-align: center;
                margin-top: 8px;
            }
            .form-main-title {
                font-size: 1.25rem;
                text-align: left;
            }
            .process-panel,
            .login-card {
                height: auto !important;
                min-height: unset !important;
                max-height: none !important;
            }
            .process-panel .panel-body,
            .login-card .card-body {
                overflow: visible;
            }
            .process-panel {
                margin-bottom: 16px;
            }
            #container {
                height: auto !important;
            }
            #loginform {
                height: auto;
            }
            .card.shadow-sm.hover-card {
                height: auto;
            }
            .uin-slip-card,
            .payment-status-card {
                min-height: 120px;
            }
            .panel .table th {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <header class="institute-header py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php if (!empty($college['logo']) && file_exists($college['logo'])): ?>
                        <img src="<?php echo htmlspecialchars($college['logo']); ?>" alt="Logo" class="institute-logo-small">
                    <?php else: ?>
                        <!-- <div class="institute-logo-placeholder-small">
                            <i class="fa-solid fa-graduation-cap fa-2x"></i>
                        </div> -->
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h2 class="institute-name mb-1"><?php echo htmlspecialchars($college['college_name'] ?? 'Kamla Nehru Institute Of Physical And Social Sciences, Sultanpur'); ?></h2>
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
                        
                        <div class="form-title-section mb-4" style="margin-top: 20px; padding-top: 20px; padding-bottom: 20px;">
                            <div class="title-row">
                                <h1 class="form-main-title mb-1">Registration 2025 | Unique Identification Number (UIN)</h1>
                                <?php if ($step === 1): ?>
                                   
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="form-title-section mb-4">
                            <?php if ($step === 1 && !$showPreregistration): ?>

<div class="row mb-4 align-items-stretch fixed-top-section">
    <div class="col-md-6" style="padding-right: 8px;">
        <div class="panel panel-default process-panel" style="width:100%; font-size:18px; font-weight:bold; overflow-y: visible; line-height: 32px; color: #666;">
            <div class="bg-primary text-white process-header-box" style="padding: 12px 15px;"><a class="text-white">UIN Registration - Process</a></div>
            <div class="panel-body" style="padding: 15px;">
                <ul class="fa-ul"></ul>
                <table class="table table-condensed rounded" cellpadding="5" cellspacing="5" style="font-weight: bold; text-align:left; margin-bottom: 0;">
                    <tbody>
                        <tr style="margin-left:10px;">
                            <th style="padding: 8px 5px;"><u>Step 1</u> - Click on Pre-Registration For Fees Payment</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 2</u> - Online Fee Payment</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 3</u> - Fill complete Entrance Form with Transaction ID and Registration</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 4</u> - Fill Important Details</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 5</u> - Upload Photo and Signature</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 6</u> - Final Submission</th>
                        </tr>
                        <tr>
                            <th style="padding: 8px 5px;"><u>Step 7</u> - Take Print of Form for Future Reference</th>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div class="col-md-6" style="padding-left: 8px;">
        <div id="container" class="ltr" style="width:100%;">
        <form id="loginform" name="login" class="wufoo page" autocomplete="off" enctype="multipart/form-data" method="get" action="">
        <div class="card shadow-sm mb-4 hover-card login-card w-100" style="border-left: 4px solid #0d6efd; display: flex; flex-direction: column;">
            <div class="card-body d-flex flex-column" style="flex: 1;">
                <h3>Select or Click Here</h3>

                <?php if (!empty($loginError)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-3" id="loginErrorAlert">
                        <?php echo htmlspecialchars($loginError); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <script>
                        setTimeout(() => {
                            const alertEl = document.getElementById('loginErrorAlert');
                            if (alertEl) {
                                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                                bsAlert.close();
                            }
                        }, <?php echo (int)$loginErrorTimeout; ?>);
                    </script>
                <?php endif; ?>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="login_option" id="opt1" value="payment_due" checked>
                    <label class="form-check-label fw-semibold" for="opt1">
                        If Your Pre-Registration Is Complete 
                    </label>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="login_option" id="opt2" value="payment_done">
                    <label class="form-check-label fw-semibold text-danger" for="opt2">
                        If Your Pre-Registration And Payment Is Complete* (After Pre-Registration)
                    </label>
                </div>

                <form method="GET" action="" id="loginForm" class="flex-grow-1 d-flex flex-column">
                    <input type="hidden" name="login_option" id="loginOptionInput" value="payment_due">

                    <div class="mb-3">
                        <label class="form-label text-start w-100">Registration Number *</label>
                        <input type="text" required class="form-control" name="reg_no">
                    </div>

                    <div class="mb-3" id="dobField">
                        <label class="form-label text-start w-100">DOB *</label>
                        <input type="date" required class="form-control" name="dob">
                    </div>

                    <div class="mb-3 d-none" id="txnField">
                        <label class="form-label text-start w-100">Transaction ID *</label>
                        <input type="text" required class="form-control" name="txn_id">
                    </div>

                    <div class="d-flex gap-2 mt-auto">
                        <button type="submit" class="btn btn-primary flex-fill">
                            Login to Application Section
                        </button>
                        <a href="?step=1&action=preregistration&mode=new" class="btn btn-success flex-fill">
                            Pre-Registration For UIN
                        </a>
                    </div>
                </form>
            </div>
        </div>
        </form>
        </div>
    </div>
</div>
<div class="row mb-4">
    
    <div class="col-md-6">
        <a href="uin_slip_search.php" class="text-decoration-none">
            <div class="card shadow-sm mb-4 hover-card uin-slip-card" style="border-radius: 12px; overflow: hidden; position: relative; min-height: 150px; background: linear-gradient(135deg, rgba(240,244,248,0.8) 0%, rgba(226,232,240,0.8) 100%), url('images/uin_slip.jpg'); background-size: cover; background-position: center; background-blend-mode: overlay;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); pointer-events: none;"></div>
                <div style="position: relative; z-index: 1; background: rgba(255,255,255,0.95); margin: 60px 20px; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="font-weight: 700; color: #1e3a8a; text-decoration: underline;">
                            <i class="fa-solid fa-file-invoice me-2"></i> Receive Print Of UIN Slip
                        </h5>
                        <i class="fa-solid fa-arrow-right" style="color: #1e3a8a; font-size: 1.2rem;"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-6">
        <a href="payment_status_search.php" class="text-decoration-none">
            <div class="card shadow-sm mb-4 hover-card payment-status-card" style="border-radius: 12px; overflow: hidden; position: relative; min-height: 150px; background: linear-gradient(135deg, rgba(240,249,255,0.8) 0%, rgba(224,242,254,0.8) 100%), url('images/payment.jpg'); background-size: cover; background-position: center; background-blend-mode: overlay;">

                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.1); pointer-events: none;"></div>
                <div style="position: relative; z-index: 1; background: rgba(255,255,255,0.95); margin: 50px 20px; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0" style="font-weight: 700; color: #15803d; text-decoration: underline;">
                            <i class="fa-solid fa-credit-card me-2"></i> Search Application No./Payment Status
                        </h5>
                        <i class="fa-solid fa-arrow-right" style="color: #15803d; font-size: 1.2rem;"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="row mt-4">
    <div class="col-12 text-center">
        <a href="index.php" class="btn btn-danger btn-lg px-5 shadow">
            <i class="fa-solid fa-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<script>
function toggleLoginFields(selectedOption, suffix = '') {
    const dobField = document.getElementById("dobField" + suffix);
    const txnField = document.getElementById("txnField" + suffix);
    const dobInput = dobField.querySelector("input");
    const txnInput = txnField.querySelector("input");
    const loginOptionInput = document.getElementById("loginOptionInput" + suffix);

    if (selectedOption === "payment_due") {
        dobField.classList.remove("d-none");
        txnField.classList.add("d-none");
        dobInput.setAttribute("required", "required");
        txnInput.removeAttribute("required");
    } else {
        dobField.classList.add("d-none");
        txnField.classList.remove("d-none");
        txnInput.setAttribute("required", "required");
        dobInput.removeAttribute("required");
    }
    if (loginOptionInput) {
        loginOptionInput.value = selectedOption;
    }
}

document.querySelectorAll("input[name='login_option']").forEach(radio => {
    radio.addEventListener("change", (event) => {
        toggleLoginFields(event.target.value);
    });
});

const initialOption = document.querySelector("input[name='login_option']:checked");
if (initialOption) {
    toggleLoginFields(initialOption.value);
}

</script>

<?php endif; ?>

                            <?php if ($step === 1 && $showPreregistration): ?>
                                <h2 class="form-step-title">Step 1. Pre-Registration For U.I.N.-2025</h2>
                            <?php elseif ($step === 1 && !$showPreregistration): ?>
                                
                            <?php else: ?>
                                <?php if ($step === 2): ?>
                                    <h2 class="form-step-title">Step 2. Student Details For U.I.N.-2025</h2>
                                <?php elseif ($step === 3): ?>
                                    <h2 class="form-step-title">Step 3. Complete Admission Form (2024-25)</h2>
                                <?php elseif ($step === 4): ?>
                                    <h2 class="form-step-title">Registration Successful</h2>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        
                        <?php if (($step === 1 && $showPreregistration) || $step > 1): ?>
                        <div class="progress-indicator mb-4">
                            <div class="progress-steps">
                                <div class="step-item <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Pre-Registration</div>
                                </div>
                                <div class="step-connector <?php echo $step > 1 ? 'active' : ''; ?>"></div>
                                <div class="step-item <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Student details</div>
                                </div>
                                <div class="step-connector <?php echo $step > 2 ? 'active' : ''; ?>"></div>
                                <div class="step-item <?php echo $step >= 3 ? 'active' : ''; ?>">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Admission Form</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                       
                        <div id="alertContainer"></div>

                      
                        <?php if ($step === 1 && $showPreregistration): ?>
                            <?php include 'uin_steps/step1_preregistration.php'; ?>

                        
                        <?php elseif ($step === 2): ?>
                            <?php include 'uin_steps/step2_payment.php'; ?>

                       
                        <?php elseif ($step === 3): ?>
                            <?php include 'uin_steps/step3_admission_form.php'; ?>
                        
                       
                        <?php elseif ($step === 4): ?>
                            <?php include 'uin_steps/step4_success.php'; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="py-3 text-bg-primary text-center">
        <div class="container">
            <span class="text-white">Copyright © 2025</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/uin_form.js"></script>

    <script>
        (function() {
            <?php if ($step === 4 && isset($_GET['success']) && $_GET['success'] == '1'): ?>
           
            window.history.pushState(null, null, window.location.href);
            window.addEventListener('popstate', function(event) {
                
                window.history.pushState(null, null, window.location.href);
            });
            <?php else: ?>
            history.pushState(null, '', location.href);
            window.addEventListener('popstate', function () {
                window.location.href = 'index.php';
            });
            <?php endif; ?>
        })();
    </script>
</body>
</html>

