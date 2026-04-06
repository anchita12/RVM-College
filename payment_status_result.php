<?php
require_once __DIR__ . '/scripts/settings.php';

$college = get_college_settings(1);

$registrationColumn = 'uin';
$colCheckReg = $mysqli->query("SHOW COLUMNS FROM uin_register_student LIKE 'registration_no'");
if ($colCheckReg && $colCheckReg->num_rows > 0) {
    $registrationColumn = 'registration_no';
}
if ($colCheckReg) {
    $colCheckReg->free();
}

$error = '';
$paymentStatusData = null;

if (isset($_GET['candidate_name']) && !empty($_GET['candidate_name']) && 
    isset($_GET['status_dob']) && !empty($_GET['status_dob']) && 
    isset($_GET['mobile']) && !empty($_GET['mobile'])) {
    
    $candidateName = trim($_GET['candidate_name']);
    $dob = trim($_GET['status_dob']);
    $mobile = trim($_GET['mobile']);
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    $sql = "SELECT s.*, s.{$registrationColumn} AS registration_no 
            FROM uin_register_student s 
            WHERE (UPPER(TRIM(s.candidate_name)) = UPPER(?) OR UPPER(s.candidate_name) LIKE UPPER(?)) 
            AND s.dob = ? 
            AND (s.mobile = ? OR REPLACE(REPLACE(s.mobile, ' ', ''), '-', '') = ?)
            LIMIT 1";
    $searchName = '%' . $candidateName . '%';
    $mobileClean = preg_replace('/[^0-9]/', '', $mobile);
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssss", $candidateName, $searchName, $dob, $mobile, $mobileClean);
        $stmt->execute();
        $result = $stmt->get_result();
        $paymentStatusData = $result->fetch_assoc();
        $stmt->close();
    }
    
    if ($paymentStatusData) {
        if (!isset($paymentStatusData['registration_no']) || empty($paymentStatusData['registration_no'])) {
            $paymentStatusData['registration_no'] = $paymentStatusData[$registrationColumn] ?? '';
        }
        
        $studentId = $paymentStatusData['id'];
        $payStmt = $mysqli->prepare("SELECT transaction_id, payment_status, amount 
                                     FROM uin_student_payment 
                                     WHERE student_id = ? AND payment_status = 'success' 
                                     ORDER BY id DESC LIMIT 1");
        if ($payStmt) {
            $payStmt->bind_param("i", $studentId);
            $payStmt->execute();
            $payResult = $payStmt->get_result();
            if ($payResult && $payResult->num_rows > 0) {
                $paymentInfo = $payResult->fetch_assoc();
                $paymentStatusData['transaction_id'] = $paymentInfo['transaction_id'] ?? '';
                $paymentStatusData['payment_status'] = $paymentInfo['payment_status'] ?? '';
                $paymentStatusData['amount'] = $paymentInfo['amount'] ?? '';
            } else {
                $paymentStatusData['transaction_id'] = '';
                $paymentStatusData['payment_status'] = '';
                $paymentStatusData['amount'] = '';
            }
            $payStmt->close();
        }
    } else {
        $error = 'No record found with the provided details. Please check your information and try again.';
    }
} else {
    $error = 'Please provide all required information to search.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status Result - <?php echo htmlspecialchars($college['college_name'] ?? 'College'); ?></title>
    
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/uin_form.css">
    <style>
        .result-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .result-container {
            background: #FFFFFF;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .result-card {
            background: #E5E4E2;
            border-radius: 4px;
            padding: 20px;
            margin-top: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .result-main-title {
            font-size: 1.8rem;
            font-weight: bold;
            text-decoration: underline;
            color: #000;
            margin-bottom: 15px;
        }
        
        .result-table {
            width: 100%;
            font-size: 0.9rem;
            border-collapse: collapse;
        }
        
        .result-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #d0d0d0;
            vertical-align: top;
        }
        
        .result-table td:first-child {
            font-weight: 600;
            color: #000;
            width: 35%;
            font-size: 0.9rem;
        }
        
        .result-table td:last-child {
            color: #000;
            font-size: 0.9rem;
        }
        
        .result-table tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            padding: 4px 10px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .back-btn {
            background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100,116,139,0.4);
            color: white;
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
                        <div class="institute-logo-placeholder-small">
                            <i class="fa-solid fa-graduation-cap fa-2x"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h2 class="institute-name mb-1"><?php echo htmlspecialchars($college['college_name'] ?? 'Kamla Nehru Institute Of Physical And Social Sciences, Sultanpur'); ?></h2>
                    <p class="institute-tagline-small mb-0">An Autonomous Institute And Accredited "A" Grade By NAAC</p>
                </div>
            </div>
        </div>
    </header>

    <main class="main-form-container py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="result-container">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <div class="text-center mt-4">
                                <a href="payment_status_search.php" class="btn back-btn">
                                    <i class="fa-solid fa-arrow-left"></i> Back to Search
                                </a>
                            </div>
                        <?php elseif ($paymentStatusData): ?>
                            <div class="result-card">
                                <div class="result-main-title">Registration 2025 | Unique Identification Number (UIN)</div>
                                <table class="result-table">
                                    <tr>
                                        <td><strong>Registration No. :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['registration_no'] ?? $paymentStatusData[$registrationColumn] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Transaction No :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['transaction_id'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Payment Status :</strong></td>
                                        <td>
                                            <?php 
                                            $paymentStatus = $paymentStatusData['payment_status'] ?? '';
                                            if (empty($paymentStatus)) {
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Pending';
                                            } else {
                                                $statusClass = ($paymentStatus === 'success') ? 'bg-success' : 'bg-warning';
                                                $statusText = ($paymentStatus === 'success') ? 'Success' : 'Pending';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Applicant's Name :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['candidate_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Father Name :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['fathers_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Mobile No :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['mobile'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Email ID :</strong></td>
                                        <td><?php echo htmlspecialchars($paymentStatusData['email'] ?? 'N/A'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="text-center mt-4">
                                <a href="payment_status_search.php" class="btn back-btn">
                                    <i class="fa-solid fa-arrow-left"></i> Back to Search
                                </a>
                            </div>
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

   
</body>
</html>

