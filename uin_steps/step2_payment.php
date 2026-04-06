<?php

$studentData = $formData;

$registrationNo = $studentData['registration_no'] ?? $studentData['uin'] ?? '';
$uinNumber = $studentData['uin'] ?? '';

$feeAmount = 100;

$courseAppliedFor = $studentData['course_applied_for'] ?? 'N/A';
if (is_numeric($courseAppliedFor)) {
    $cStmt = $mysqli->prepare("SELECT class_description FROM class_detail WHERE sno = ?");
    if ($cStmt) {
        $cStmt->bind_param("i", $courseAppliedFor);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRes && $courseRow = $cRes->fetch_assoc()) {
            $courseAppliedFor = $courseRow['class_description'] ?? $courseAppliedFor;
        }
        $cStmt->close();
    }
}
?>

<div class="payment-page">
    <?php if (!empty($paymentAlreadyDone)): ?>
        <div class="alert alert-info">
            Payment for this registration is already marked as <strong>success</strong>.
            You can proceed to Step 3 or contact admin if you need to retry payment.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
            <strong>Warning:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['warning'])): ?>
        <div class="alert alert-warning">
            <?php echo htmlspecialchars($_GET['warning']); ?>
        </div>
    <?php endif; ?>

    <form id="step2Form" method="POST" action="scripts/api/process_payment.php">
        <?php 
        $studentId = $studentData['student_id'] ?? $studentData['id'] ?? '';
        if (empty($studentId) && isset($studentData['id'])) {
            $studentId = $studentData['id'];
        }
        ?>
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentId); ?>">
        <input type="hidden" name="uin" value="<?php echo htmlspecialchars($uinNumber); ?>">
        
        <div class="registration-details">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Registration No.</span>
                        <span class="detail-value"><?php echo htmlspecialchars($registrationNo ?: 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Course Applied For</span>
                        <span class="detail-value"><?php echo htmlspecialchars($courseAppliedFor); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Applicant's Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($studentData['candidate_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Father Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($studentData['fathers_name'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Mobile No.</span>
                        <span class="detail-value"><?php echo htmlspecialchars($studentData['mobile'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-line">
                        <span class="detail-label">Email ID</span>
                        <span class="detail-value"><?php echo htmlspecialchars($studentData['email'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-12" style="display:none;">
                    <div class="detail-line detail-fee">
                        <span class="detail-label">Fees For Unique Identification Number</span>
                        <span class="detail-value text-danger">₹ <?php echo number_format($feeAmount, 2); ?></span>
                        <input type="hidden" name="amount" value="<?php echo $feeAmount; ?>">
                    </div>
                </div>
            </div>
        </div>

       
        <div class="payment-note mt-4" style="display:none;">
            <div class="alert alert-danger">
                <strong>NOTE:</strong> DEAR APPLICANT, PLEASE BE PATIENT AS THE FEE PAYMENT MAY TAKE FEW MINUTES OF YOUR TIME. 
                PLEASE DON'T DISCONNECT THE SESSION OR CLOSE THE PROCESSING WINDOW.
            </div>
        </div>

        
        <div class="form-actions mt-4 d-flex justify-content-end" style="display:none;">
            <!-- <button type="submit" class="btn btn-danger btn-lg px-5" id="paymentBtn">
                <i class="fa-solid fa-credit-card me-2"></i> Make Payment
            </button> -->
        </div>
    </form>
    
    <div class="form-actions mt-4 d-flex justify-content-end">
        <a href="uin_reg_form.php?step=3" class="btn btn-warning btn-lg px-5">
            Proceed to Admission Form
        </a>
    </div>
</div>

<script>
document.getElementById('step2Form').addEventListener('submit', function(e) {
    const btn = document.getElementById('paymentBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    
   
});
</script>

