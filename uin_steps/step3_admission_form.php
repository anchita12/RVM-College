<?php
$studentData = $formData;
if (isset($studentData['student_id'])) {
    $studentId = (int)$studentData['student_id'];
    $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $studentData = array_merge($studentData, $result->fetch_assoc());
    }
    $stmt->close();
}

if (!isset($studentData['weightage']) && isset($studentData['Weightage'])) {
    $studentData['weightage'] = $studentData['Weightage'];
}


$hasAdmissionData = false;
if (!empty($studentData['student_id'])) {
    $hasAdmissionData = !empty($studentData['photo_upload']) || 
                        !empty($studentData['signature_upload']) || 
                        !empty($studentData['gender']) || 
                        !empty($studentData['religion']) ||
                        !empty($studentData['c_house']) ||
                        !empty($studentData['c_district']);
}

if (empty($studentData['from_edit']) && !$hasAdmissionData) {
    $keepKeys = [
        
        'student_id', 'uin', 'registration_no', 'from_edit',
        
        'candidate_name', 'fathers_name', 'mothers_name',
        'dob', 'aadhaar', 'email', 'mobile', 'course_type',
        'course_applied_for', 'category', 'p_house', 'p_post',
        'p_tehsil', 'p_thana', 'p_district', 'p_state', 'p_pin',
        'parents_mobile', 'weightage', 'c_thana',
        'p_house', 'p_post', 'p_tehsil', 'p_thana', 'p_district', 'p_state', 'p_pin'
    ];
    foreach ($studentData as $key => $val) {
        if (!in_array($key, $keepKeys, true)) {
            $studentData[$key] = '';
        }
    }
}
$qualifications = [];
if (isset($studentData['student_id'])) {
    $stmt = $mysqli->prepare("SELECT * FROM uin_student_qualification WHERE student_id = ? ORDER BY id");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $qualifications[] = $row;
    }
    $stmt->close();
}

$courseType = $studentData['course_type'] ?? '';
$courseAppliedFor = $studentData['course_applied_for'] ?? '';
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
$isBEd = (stripos($courseAppliedFor, 'B.Ed') !== false);
// Treat B.Ed applicants same as PG for optional other-qualification rows
$isPG = ($courseType === 'PG' || $isBEd);

if (empty($studentData['from_edit']) && !$hasAdmissionData) {
    $qualifications = [
        ['exam_name' => 'High School', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'],
        ['exam_name' => 'Intermediate', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed']
    ];
    // Add PG rows if course type is PG or B.Ed
    if ($courseType === 'PG' || $isBEd) {
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
    }
} elseif (empty($qualifications) && empty($studentData['from_edit'])) {
    $qualifications = [
        ['exam_name' => 'High School', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'],
        ['exam_name' => 'Intermediate', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed']
    ];
    // Add PG rows if course type is PG or B.Ed
    if ($courseType === 'PG' || $isBEd) {
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
    }
} elseif (empty($qualifications)) {
    $qualifications = [
        ['exam_name' => 'High School', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'],
        ['exam_name' => 'Intermediate', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed']
    ];
    // Add PG rows if course type is PG or B.Ed
    if ($courseType === 'PG' || $isBEd) {
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
        $qualifications[] = ['exam_name' => '', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'];
    }
}

if (count($qualifications) < 2) {
    $qualifications = [
        ['exam_name' => 'High School', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed'],
        ['exam_name' => 'Intermediate', 'board' => '', 'college_name' => '', 'passing_year' => '', 'roll_no' => '', 'total_marks' => '', 'marks_obtained' => '', 'percentage' => '', 'grade' => '', 'status' => 'Passed']
    ];
}

$categories = get_dropdown_options('categories', 'categories_sno', 'category_name');
$religions = get_religions();
$genders = get_genders();
$domiciles = get_domiciles();
$motherTongues = get_mother_tongues();
$bloodGroups = get_blood_groups();
$weightages = get_weightages();
$statuses = get_student_statuses();
$examStatuses = get_exam_statuses();
$examNames = get_exam_names();
?>


<form id="step3Form" method="POST" action="scripts/api/process_admission.php" enctype="multipart/form-data">
    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($studentData['student_id'] ?? ''); ?>">
    <input type="hidden" name="uin" value="<?php echo htmlspecialchars($studentData['uin'] ?? ''); ?>">
    <?php if (isset($studentData['from_edit']) && $studentData['from_edit']): ?>
        <input type="hidden" name="from_edit" value="1">
    <?php endif; ?>

    <div class="form-section mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="candidate_name" class="form-label">
                    Candidate Name <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="candidate_name" name="candidate_name" 
                       value="<?php echo htmlspecialchars($studentData['candidate_name'] ?? ''); ?>" required readonly>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="fathers_name" class="form-label">
                    Father's Name <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="fathers_name" name="fathers_name" 
                       value="<?php echo htmlspecialchars($studentData['fathers_name'] ?? ''); ?>" required readonly>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="mothers_name" class="form-label">Mother's Name</label>
                <input type="text" class="form-control" id="mothers_name" name="mothers_name" 
                       value="<?php echo htmlspecialchars($studentData['mothers_name'] ?? ''); ?>" readonly>
            </div>

            <div class="col-md-6">
                <label for="dob" class="form-label">
                    Date of Birth <span class="text-danger">*</span>
                </label>
                <input type="date" class="form-control" id="dob" name="dob" 
                       value="<?php echo htmlspecialchars($studentData['dob'] ?? ''); ?>" required readonly>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="aadhaar" class="form-label">Aadhar <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="aadhaar" name="aadhaar" 
                       value="<?php echo htmlspecialchars($studentData['aadhaar'] ?? ''); ?>" 
                       pattern="[0-9]{12}" maxlength="12" required readonly>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">E-Mail <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($studentData['email'] ?? ''); ?>" required readonly>
            </div>

            <div class="col-md-6">
                <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="mobile" name="mobile" 
                       value="<?php echo htmlspecialchars($studentData['mobile'] ?? ''); ?>" 
                       pattern="[0-9]{10}" maxlength="10" required readonly>
            </div>

            <div class="col-md-6">
                <label for="course_type" class="form-label">Course Type</label>
                <input type="text" class="form-control" id="course_type_display" value="<?php echo htmlspecialchars($studentData['course_type'] ?? ''); ?>" readonly>
                <input type="hidden" id="course_type" value="<?php echo htmlspecialchars($studentData['course_type'] ?? ''); ?>">
            </div>

            <div class="col-md-6">
                <label for="course_applied_for" class="form-label">Course Applying For <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="course_applied_for_display" value="<?php echo htmlspecialchars($courseAppliedFor); ?>" readonly>
                <input type="hidden" name="course_applied_for" value="<?php echo htmlspecialchars($studentData['course_applied_for'] ?? ''); ?>">
            </div>

            <div class="col-md-6">
                <label for="category_display" class="form-label">Category <span class="text-danger">*</span></label>
                <?php $categoryName = ''; foreach ($categories as $cat) { if (isset($studentData['category']) && $studentData['category'] == $cat['sno']) { $categoryName = $cat['name']; break; } } ?>
                <input type="text" class="form-control" id="category_display" value="<?php echo htmlspecialchars($categoryName); ?>" readonly>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($studentData['category'] ?? ''); ?>">
            </div>

            <div class="col-md-6">
                <label for="religion" class="form-label">Religion <span class="text-danger">*</span></label>
                <select class="form-select" id="religion" name="religion" required>
                    <option value="">---Select---</option>
                    <?php foreach ($religions as $rel): ?>
                        <option value="<?php echo htmlspecialchars($rel['sno']); ?>" <?php echo (isset($studentData['religion']) && $studentData['religion'] == $rel['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rel['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="">---Select---</option>
                    <?php foreach ($genders as $gen): ?>
                        <option value="<?php echo htmlspecialchars($gen['sno']); ?>" <?php echo (isset($studentData['gender']) && $studentData['gender'] == $gen['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($gen['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="whatsapp_mobile" class="form-label">WhatsApp Mobile No. <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="whatsapp_mobile" name="whatsapp_mobile" 
                       value="<?php echo htmlspecialchars($studentData['whatsapp_mobile'] ?? $studentData['mobile'] ?? ''); ?>" 
                       pattern="[0-9]{10}" maxlength="10" required>
            </div>

            <div class="col-md-6">
                <label for="parents_mobile" class="form-label">Parents Mobile No. <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="parents_mobile" name="parents_mobile" 
                       value="<?php echo htmlspecialchars($studentData['parents_mobile'] ?? ''); ?>" 
                       pattern="[0-9]{10}" maxlength="10" required
                       onblur="validateParentsMobile()">
                <small class="text-muted d-block mt-1">Must be different from your mobile number</small>
                <small class="text-danger" id="parents_mobile_error" style="display:none;"></small>
            </div>

            <div class="col-md-6">
                <label for="domicile" class="form-label">Domicile <span class="text-danger">*</span></label>
                <select class="form-select" id="domicile" name="domicile" required>
                    <option value="">---Select---</option>
                    <?php foreach ($domiciles as $dom): ?>
                        <option value="<?php echo htmlspecialchars($dom['sno']); ?>" <?php echo (isset($studentData['domicile']) && $studentData['domicile'] == $dom['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dom['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="mother_tongue" class="form-label">Mother Tongue <span class="text-danger">*</span></label>
                <select class="form-select" id="mother_tongue" name="mother_tongue" required>
                    <option value="">---Select---</option>
                    <?php foreach ($motherTongues as $mt): ?>
                        <option value="<?php echo htmlspecialchars($mt['sno']); ?>" <?php echo (isset($studentData['mother_tongue']) && $studentData['mother_tongue'] == $mt['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="weightage" class="form-label">Weightage <span class="text-danger">*</span></label>
                <select class="form-select" id="weightage" name="weightage" required>
                    <option value="">---Select---</option>
                    <?php foreach ($weightages as $wt): ?>
                        <option value="<?php echo htmlspecialchars($wt['sno']); ?>" <?php echo (isset($studentData['weightage']) && $studentData['weightage'] == $wt['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($wt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="blood_group" class="form-label">Blood Group <span class="text-danger">*</span></label>
                <select class="form-select" id="blood_group" name="blood_group" required>
                    <option value="">---Select---</option>
                    <?php foreach ($bloodGroups as $bg): ?>
                        <option value="<?php echo htmlspecialchars($bg['sno']); ?>" <?php echo (isset($studentData['blood_group']) && $studentData['blood_group'] == $bg['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bg['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?php echo htmlspecialchars($st['sno']); ?>" <?php echo (isset($studentData['status']) && $studentData['status'] == $st['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($st['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="upload-section mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="photo_upload" class="form-label">
                    PHOTO UPLOAD <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control" id="photo_upload" name="photo_upload" 
                       accept="image/*" onchange="previewImage(this, 'photoPreview')" 
                       <?php echo empty($studentData['photo_upload']) ? 'required' : ''; ?>>
                <input type="hidden" name="existing_photo" value="<?php echo htmlspecialchars($studentData['photo_upload'] ?? ''); ?>">
                <div class="mt-2">
                    <?php if (!empty($studentData['photo_upload'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['photo_upload']); ?>" 
                             alt="Photo" class="preview-image" id="photoPreview">
                    <?php else: ?>
                        <img src="assets/placeholder-person.jpg" alt="Photo Preview" 
                             class="preview-image" id="photoPreview" style="display:none;">
                    <?php endif; ?>
                </div>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="signature_upload" class="form-label">
                    SIGNATURE UPLOAD <span class="text-danger">*</span>
                </label>
                <input type="file" class="form-control" id="signature_upload" name="signature_upload" 
                       accept="image/*" onchange="previewImage(this, 'signaturePreview')" 
                       <?php echo empty($studentData['signature_upload']) ? 'required' : ''; ?>>
                <input type="hidden" name="existing_signature" value="<?php echo htmlspecialchars($studentData['signature_upload'] ?? ''); ?>">
                <div class="mt-2">
                    <?php if (!empty($studentData['signature_upload'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['signature_upload']); ?>" 
                             alt="Signature" class="preview-image" id="signaturePreview">
                    <?php else: ?>
                        <img src="assets/placeholder-person.jpg" alt="Signature Preview" 
                             class="preview-image" id="signaturePreview" style="display:none;">
                    <?php endif; ?>
                </div>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="aadhar_file" class="form-label">
                    Aadhaar Card Image (JPG/JPEG/PNG)
                </label>
                <input type="file" class="form-control" id="aadhar_file" name="aadhar_file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       onchange="previewDocImage(this, 'aadharPreview')">
                <div class="mt-2">
                    <?php if (!empty($studentData['aadhar_upload'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['aadhar_upload']); ?>"
                             alt="Aadhaar" class="preview-image" id="aadharPreview">
                    <?php else: ?>
                        <img src="" alt="Aadhaar Preview" class="preview-image" id="aadharPreview" style="display:none; max-height:120px;">
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentData['aadhar_upload'])): ?>
                    <small class="d-block mt-1">
                        Already uploaded:
                        <a href="<?php echo htmlspecialchars($studentData['aadhar_upload']); ?>" target="_blank">View</a>
                    </small>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="pan_card_file" class="form-label">
                    PAN Card Image (JPG/JPEG/PNG)
                </label>
                <input type="file" class="form-control" id="pan_card_file" name="pan_card_file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       onchange="previewDocImage(this, 'panPreview')">
                <div class="mt-2">
                    <?php if (!empty($studentData['pan_card'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['pan_card']); ?>"
                             alt="PAN" class="preview-image" id="panPreview">
                    <?php else: ?>
                        <img src="" alt="PAN Preview" class="preview-image" id="panPreview" style="display:none; max-height:120px;">
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentData['pan_card'])): ?>
                    <small class="d-block mt-1">
                        Already uploaded:
                        <a href="<?php echo htmlspecialchars($studentData['pan_card']); ?>" target="_blank">View</a>
                    </small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="marksheet_10_file" class="form-label">
                    High School (10th) Marksheet Image (JPG/JPEG/PNG)
                </label>
                <input type="file" class="form-control" id="marksheet_10_file" name="marksheet_10_file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       onchange="previewDocImage(this, 'marksheet10Preview')">
                <div class="mt-2">
                    <?php if (!empty($studentData['result_10'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['result_10']); ?>"
                             alt="10th Marksheet" class="preview-image" id="marksheet10Preview">
                    <?php else: ?>
                        <img src="" alt="10th Marksheet Preview" class="preview-image" id="marksheet10Preview" style="display:none; max-height:120px;">
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentData['result_10'])): ?>
                    <small class="d-block mt-1">
                        Already uploaded:
                        <a href="<?php echo htmlspecialchars($studentData['result_10']); ?>" target="_blank">View</a>
                    </small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="marksheet_12_file" class="form-label">
                    Intermediate (12th) Marksheet Image (JPG/JPEG/PNG)
                </label>
                <input type="file" class="form-control" id="marksheet_12_file" name="marksheet_12_file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       onchange="previewDocImage(this, 'marksheet12Preview')">
                <div class="mt-2">
                    <?php if (!empty($studentData['result_12'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['result_12']); ?>"
                             alt="12th Marksheet" class="preview-image" id="marksheet12Preview">
                    <?php else: ?>
                        <img src="" alt="12th Marksheet Preview" class="preview-image" id="marksheet12Preview" style="display:none; max-height:120px;">
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentData['result_12'])): ?>
                    <small class="d-block mt-1">
                        Already uploaded:
                        <a href="<?php echo htmlspecialchars($studentData['result_12']); ?>" target="_blank">View</a>
                    </small>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label for="marksheet_ug_file" class="form-label">
                    Other Qualification Image (JPG/JPEG/PNG)
                </label>
                <input type="file" class="form-control" id="marksheet_ug_file" name="marksheet_ug_file"
                       accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                       onchange="previewDocImage(this, 'other_qualificationPreview')">
                <div class="mt-2">
                    <?php if (!empty($studentData['ug_marksheet'])): ?>
                        <img src="<?php echo htmlspecialchars($studentData['ug_marksheet']); ?>"
                             alt="UG Marksheet" class="preview-image" id="other_qualificationPreview">
                    <?php else: ?>
                        <img src="" alt="UG Marksheet Preview" class="preview-image" id="other_qualificationPreview" style="display:none; max-height:120px;">
                    <?php endif; ?>
                </div>
                <?php if (!empty($studentData['ug_marksheet'])): ?>
                    <small class="d-block mt-1">
                        Already uploaded:
                        <a href="<?php echo htmlspecialchars($studentData['ug_marksheet']); ?>" target="_blank">View</a>
                    </small>
                <?php endif; ?>
            </div>

            
        </div>
    </div>


    <div class="education-section mb-4">
        <div class="section-header-bar">
            <h3 class="section-title">Education Details</h3>
        </div>
        
        <div class="table-responsive mt-3">
            <table class="table table-bordered" id="educationTable">
                <thead>
                    <tr>
                        <th>S.NO</th>
                        <th>NAME OF EXAMINATION</th>
                        <th>BOARD/UNIVERSITY NAME</th>
                        <th>COLLEGE NAME</th>
                        <th>YEAR OF PASSING</th>
                        <th>ROLL NO</th>
                        <th>SELECT</th>
                        <th>OBT. MARKS</th>
                        <th>TOTAL MARKS</th>
                        <th>PERCENTAGE</th>
                        <th>CGPA</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qualifications as $idx => $qual): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td>
                                <?php if ($idx < 2): ?>
                                    <!-- High School and Intermediate are read-only -->
                                    <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][exam_name]" 
                                           value="<?php echo htmlspecialchars($qual['exam_name'] ?? ''); ?>" readonly required>
                                <?php else: ?>
                                    <!-- Other rows have dropdown for PG options -->
                                    <select class="form-select" name="qualification[<?php echo $idx; ?>][exam_name]" 
                                            id="exam_name_<?php echo $idx; ?>" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                                        <option value="">---Select---</option>
                                        <?php foreach ($examNames as $exam): ?>
                                            <option value="<?php echo htmlspecialchars($exam['name']); ?>" 
                                                <?php echo (isset($qual['exam_name']) && $qual['exam_name'] == $exam['name']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($exam['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][board]" 
                                       value="<?php echo htmlspecialchars($qual['board'] ?? ''); ?>" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][college_name]" 
                                       value="<?php echo htmlspecialchars($qual['college_name'] ?? ''); ?>" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][passing_year]" 
                                       value="<?php echo htmlspecialchars($qual['passing_year'] ?? ''); ?>" 
                                       pattern="[0-9]{4}" maxlength="4" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][roll_no]" 
                                       value="<?php echo htmlspecialchars($qual['roll_no'] ?? ''); ?>" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                            </td>
                            <td>
                                <?php
                                    // Determine which marks type should be pre-selected for this row
                                    $hasCgpa = !empty($qual['grade']);
                                    $hasPercentage = !$hasCgpa && (
                                        !empty($qual['percentage']) ||
                                        (!empty($qual['marks_obtained']) && !empty($qual['total_marks']))
                                    );
                                ?>
                                <select class="form-select"
                                        name="qualification[<?php echo $idx; ?>][select_type]" 
                                        id="select_type_<?php echo $idx; ?>"
                                        onchange="toggleMarksType(this, <?php echo $idx; ?>)"
                                        <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                                    <option value="">---Select---</option>
                                    <option value="percentage" <?php echo $hasPercentage ? 'selected' : ''; ?>>
                                        Percentage
                                    </option>
                                    <option value="cgpa" <?php echo $hasCgpa ? 'selected' : ''; ?>>
                                        CGPA
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input type="text" class="form-control marks-input" name="qualification[<?php echo $idx; ?>][marks_obtained]" 
                                       value="<?php echo htmlspecialchars($qual['marks_obtained'] ?? ''); ?>" 
                                       id="marks_obtained_<?php echo $idx; ?>"
                                       oninput="calculatePercentage(<?php echo $idx; ?>)">
                            </td>
                            <td>
                                <input type="text" class="form-control marks-input" name="qualification[<?php echo $idx; ?>][total_marks]" 
                                       value="<?php echo htmlspecialchars($qual['total_marks'] ?? ''); ?>" 
                                       id="total_marks_<?php echo $idx; ?>"
                                       oninput="calculatePercentage(<?php echo $idx; ?>)">
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][percentage]" 
                                       value="<?php echo htmlspecialchars($qual['percentage'] ?? ''); ?>" 
                                       id="percentage_<?php echo $idx; ?>" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control" name="qualification[<?php echo $idx; ?>][grade]" 
                                       value="<?php echo htmlspecialchars($qual['grade'] ?? ''); ?>" 
                                       id="cgpa_<?php echo $idx; ?>">
                            </td>
                            <td>
                                <select class="form-select" name="qualification[<?php echo $idx; ?>][status]" <?php echo ((!$isPG) || ($idx == 2)) ? 'required' : ''; ?>>
                                    <option value="Passed" <?php echo (isset($qual['status']) && $qual['status'] == 'Passed') ? 'selected' : ''; ?>>Passed</option>
                                    <option value="Failed" <?php echo (isset($qual['status']) && $qual['status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="address-section mb-4">
        <div class="section-header-bar">
            <h3 class="section-title">Permanent Address</h3>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label for="p_house" class="form-label">House No./Village</label>
                <input type="text" class="form-control" id="p_house" name="p_house" 
                       value="<?php echo htmlspecialchars($studentData['p_house'] ?? ''); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_post" class="form-label">Post</label>
                <input type="text" class="form-control" id="p_post" name="p_post" 
                       value="<?php echo htmlspecialchars($studentData['p_post'] ?? ''); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_tehsil" class="form-label">Tehsil</label>
                <input type="text" class="form-control" id="p_tehsil" name="p_tehsil" 
                       value="<?php echo htmlspecialchars($studentData['p_tehsil'] ?? ''); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_thana" class="form-label">Thana</label>
                <input type="text" class="form-control" id="p_thana" name="p_thana" 
                       value="<?php echo htmlspecialchars($studentData['p_thana'] ?? ''); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_district" class="form-label">District</label>
                <input type="text" class="form-control" id="p_district" name="p_district" 
                       value="<?php echo htmlspecialchars($studentData['p_district'] ?? ''); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_state" class="form-label">State</label>
                <input type="text" class="form-control" id="p_state" name="p_state" 
                       value="<?php echo htmlspecialchars((isset($studentData['p_state']) && is_numeric($studentData['p_state'])) ? get_state_name($studentData['p_state']) : ($studentData['p_state'] ?? '')); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="p_pin" class="form-label">Pin</label>
                <input type="text" class="form-control" id="p_pin" name="p_pin" 
                       value="<?php echo htmlspecialchars($studentData['p_pin'] ?? ''); ?>" 
                       pattern="[0-9]{6}" maxlength="6" readonly>
            </div>
        </div>
    </div>


    <div class="address-section mb-4">
        <div class="section-header-bar d-flex justify-content-between align-items-center">
            <h3 class="section-title mb-0">Correspondence Address</h3>
            <button type="button" class="btn btn-sm btn-primary" onclick="copyPermanentAddress()">
                Click Here to Copy
            </button>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label for="c_house" class="form-label">House No./Village</label>
                <input type="text" class="form-control" id="c_house" name="c_house" 
                       value="<?php echo htmlspecialchars($studentData['c_house'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_post" class="form-label">Post</label>
                <input type="text" class="form-control" id="c_post" name="c_post" 
                       value="<?php echo htmlspecialchars($studentData['c_post'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_tehsil" class="form-label">Tehsil</label>
                <input type="text" class="form-control" id="c_tehsil" name="c_tehsil" 
                       value="<?php echo htmlspecialchars($studentData['c_tehsil'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_thana" class="form-label">Thana</label>
                <input type="text" class="form-control" id="c_thana" name="c_thana" 
                       value="<?php echo htmlspecialchars($studentData['c_thana'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_district" class="form-label">District</label>
                <input type="text" class="form-control" id="c_district" name="c_district" 
                       value="<?php echo htmlspecialchars($studentData['c_district'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_state" class="form-label">State</label>
                <input type="text" class="form-control" id="c_state" name="c_state" 
                       value="<?php echo htmlspecialchars((isset($studentData['c_state']) && is_numeric($studentData['c_state'])) ? get_state_name($studentData['c_state']) : ($studentData['c_state'] ?? '')); ?>">
            </div>
            <div class="col-md-6">
                <label for="c_pin" class="form-label">Pin</label>
                <input type="text" class="form-control" id="c_pin" name="c_pin" 
                       value="<?php echo htmlspecialchars($studentData['c_pin'] ?? ''); ?>" 
                       pattern="[0-9]{6}" maxlength="6">
            </div>
        </div>
    </div>


    <div class="form-actions mt-4 d-flex justify-content-between flex-wrap gap-3">
        <?php if (!empty($studentData['from_edit'])): ?>
            <a href="uin_edit.php" class="btn btn-secondary btn-lg px-5">Back to Search</a>
            <button type="submit" class="btn btn-warning btn-lg px-5">Submit</button>
        <?php else: ?>
            <div></div>
            <button type="submit" class="btn btn-warning btn-lg px-5">Submit</button>
        <?php endif; ?>
    </div>
</form>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewDocImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function calculatePercentage(index) {
    const selectType = document.getElementById('select_type_' + index).value;
    
    // If CGPA is selected or no option selected yet, skip percentage calculation
    if (selectType === 'cgpa' || !selectType) {
        return; 
    }
    
    const marksObtained = parseFloat(document.getElementById('marks_obtained_' + index).value) || 0;
    const totalMarks = parseFloat(document.getElementById('total_marks_' + index).value) || 0;
    const percentageField = document.getElementById('percentage_' + index);
    
    if (totalMarks > 0 && marksObtained >= 0) {
        const percentage = ((marksObtained / totalMarks) * 100).toFixed(2);
        percentageField.value = percentage;
    } else {
        percentageField.value = '';
    }
}

function toggleMarksType(select, index) {
    const marksType = select.value;
    const percentageField = document.getElementById('percentage_' + index);
    const cgpaField = document.getElementById('cgpa_' + index);
    const marksObtainedField = document.getElementById('marks_obtained_' + index);
    const totalMarksField = document.getElementById('total_marks_' + index);
    const courseTypeDom = document.getElementById('course_type')?.value || '';
    const courseAppliedForDisplay = document.getElementById('course_applied_for_display')?.value || '';
    const isPG = (courseTypeDom === 'PG' || /B\.Ed/i.test(courseAppliedForDisplay));
    const rowOptional = isPG && parseInt(index, 10) >= 3;
    
    if (marksType === 'cgpa') {
        // Show only CGPA field
        marksObtainedField.style.display = 'none';
        totalMarksField.style.display = 'none';
        percentageField.style.display = 'none';
        
        cgpaField.style.display = 'block';
        cgpaField.disabled = false;
        cgpaField.required = !rowOptional;
    
        percentageField.value = '';
        marksObtainedField.value = '';
        totalMarksField.value = '';
        marksObtainedField.disabled = true;
        totalMarksField.disabled = true;
        marksObtainedField.required = false;
        totalMarksField.required = false;
        percentageField.required = false;
    } else if (marksType === 'percentage') {
        // Show marks and percentage fields
        marksObtainedField.style.display = 'block';
        totalMarksField.style.display = 'block';
        percentageField.style.display = 'block';
        cgpaField.style.display = 'none';
        cgpaField.disabled = true;
        cgpaField.required = false;
        cgpaField.value = '';
        percentageField.disabled = false;
        marksObtainedField.disabled = false;
        totalMarksField.disabled = false;
        marksObtainedField.required = !rowOptional;
        totalMarksField.required = !rowOptional;
        percentageField.required = false; // readonly
        calculatePercentage(index);
    } else {
        // No selection: hide both types and clear values
        marksObtainedField.style.display = 'none';
        totalMarksField.style.display = 'none';
        percentageField.style.display = 'none';
        cgpaField.style.display = 'none';

        marksObtainedField.value = '';
        totalMarksField.value = '';
        percentageField.value = '';
        cgpaField.value = '';

        marksObtainedField.disabled = true;
        totalMarksField.disabled = true;
        percentageField.disabled = true;
        cgpaField.disabled = true;

        marksObtainedField.required = false;
        totalMarksField.required = false;
        cgpaField.required = false;
    }
}

function validateParentsMobile() {
    const mobile = document.getElementById('mobile').value.trim();
    const parentsMobile = document.getElementById('parents_mobile').value.trim();
    const errorElement = document.getElementById('parents_mobile_error');
    const parentsMobileInput = document.getElementById('parents_mobile');
    
    if (parentsMobile && mobile && parentsMobile === mobile) {
        errorElement.textContent = 'Parents mobile number must be different from your mobile number';
        errorElement.style.display = 'block';
        parentsMobileInput.classList.add('is-invalid');
        return false;
    } else {
        errorElement.style.display = 'none';
        parentsMobileInput.classList.remove('is-invalid');
        return true;
    }
}

function copyPermanentAddress() {
    document.getElementById('c_house').value = document.getElementById('p_house').value;
    document.getElementById('c_post').value = document.getElementById('p_post').value;
    document.getElementById('c_tehsil').value = document.getElementById('p_tehsil').value;
    document.getElementById('c_thana').value = document.getElementById('p_thana').value;
    document.getElementById('c_district').value = document.getElementById('p_district').value;
    document.getElementById('c_state').value = document.getElementById('p_state').value;
    document.getElementById('c_pin').value = document.getElementById('p_pin').value;
}
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide additional education rows for PG / B.Ed
    const courseType = document.getElementById('course_type')?.value || '';
    const courseAppliedForDisplay = document.getElementById('course_applied_for_display')?.value || '';
    const isPGlike = (courseType === 'PG' || /B\.Ed/i.test(courseAppliedForDisplay));
    const educationTable = document.getElementById('educationTable');
    if (educationTable && isPGlike) {
        const tbody = educationTable.querySelector('tbody');
        const currentRows = tbody.querySelectorAll('tr').length;
        // Ensure we have at least 4 rows for PG (High School, Intermediate, and 2 PG options)
        if (currentRows < 4) {
            for (let i = currentRows; i < 4; i++) {
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td>${i + 1}</td>
                    <td>
                        <select class="form-select" name="qualification[${i}][exam_name]">
                            <option value="">---Select---</option>
                            <?php foreach ($examNames as $exam): ?>
                            <option value="<?php echo htmlspecialchars($exam['name']); ?>"><?php echo htmlspecialchars($exam['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" class="form-control" name="qualification[${i}][board]"></td>
                    <td><input type="text" class="form-control" name="qualification[${i}][college_name]"></td>
                    <td><input type="text" class="form-control" name="qualification[${i}][passing_year]" pattern="[0-9]{4}" maxlength="4"></td>
                    <td><input type="text" class="form-control" name="qualification[${i}][roll_no]"></td>
                    <td>
                        <select class="form-select"
                                name="qualification[${i}][select_type]"
                                id="select_type_${i}"
                                onchange="toggleMarksType(this, ${i})">
                            <option value="">---Select---</option>
                            <option value="percentage">Percentage</option>
                            <option value="cgpa">CGPA</option>
                        </select>
                    </td>
                    <td><input type="text" class="form-control marks-input" name="qualification[${i}][marks_obtained]" id="marks_obtained_${i}" oninput="calculatePercentage(${i})"></td>
                    <td><input type="text" class="form-control marks-input" name="qualification[${i}][total_marks]" id="total_marks_${i}" oninput="calculatePercentage(${i})"></td>
                    <td><input type="text" class="form-control" name="qualification[${i}][percentage]" id="percentage_${i}" readonly></td>
                    <td><input type="text" class="form-control" name="qualification[${i}][grade]" id="cgpa_${i}" style="display:none;"></td>
                    <td>
                        <select class="form-select" name="qualification[${i}][status]">
                            <option value="Passed" selected>Passed</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </td>
                `;
                tbody.appendChild(newRow);
                // For PG/B.Ed pages, make the 3rd row (index 2) required while leaving 4th optional
                if (isPGlike && i === 2) {
                    const newTr = tbody.querySelectorAll('tr')[i];
                    if (newTr) {
                        const examSel = newTr.querySelector('select[name="qualification['+i+'][exam_name]"]');
                        if (examSel) examSel.required = true;
                        ['board','college_name','passing_year','roll_no'].forEach(function(n) {
                            const el = newTr.querySelector('[name="qualification['+i+']['+n+']"]');
                            if (el) el.required = true;
                        });
                        const selType = newTr.querySelector('[name="qualification['+i+'][select_type]"]');
                        if (selType) selType.required = true;
                        const statusEl = newTr.querySelector('[name="qualification['+i+'][status]"]');
                        if (statusEl) statusEl.required = true;
                    }
                }
            }
        }
    }
    
    const selectElements = document.querySelectorAll('[id^="select_type_"]');
    selectElements.forEach(function(select) {
        const index = select.id.replace('select_type_', '');
        toggleMarksType(select, index);
    });

    const form = document.getElementById('step3Form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const photoInput = document.getElementById('photo_upload');
            const signatureInput = document.getElementById('signature_upload');
            const existingPhoto = document.querySelector('input[name="existing_photo"]');
            const existingSignature = document.querySelector('input[name="existing_signature"]');
            if (!photoInput.files || photoInput.files.length === 0) {
                if (!existingPhoto || !existingPhoto.value) {
                    alert('Please upload a photo');
                    photoInput.focus();
                    return false;
                }
            }
        
            if (!signatureInput.files || signatureInput.files.length === 0) {
                if (!existingSignature || !existingSignature.value) {
                    alert('Please upload a signature');
                    signatureInput.focus();
                    return false;
                }
            }

            if (!validateParentsMobile()) {
                alert('Parents mobile number must be different from your mobile number');
                document.getElementById('parents_mobile').focus();
                return false;
            }
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Submitting...';
            const formData = new FormData(form);
            fetch('scripts/api/process_admission.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON response:', text);
                        throw new Error('Invalid response from server.');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                } else {
                    alert(data.message || 'Submission failed. Please try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'An error occurred. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
});
</script>

