<?php
$studentData = $student;
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

if (!isset($studentData['weightage']) && isset($studentData['Weightage'])) {
    $studentData['weightage'] = $studentData['Weightage'];
}

$categories = get_dropdown_options('categories', 'categories_sno', 'category_name');
$religions = get_religions();
$genders = get_genders();
$domiciles = get_domiciles();
$motherTongues = get_mother_tongues();
$bloodGroups = get_blood_groups();
$weightages = get_weightages();
$statuses = get_student_statuses();

function get_display_value($sno, $array) {
    if (empty($sno)) return 'Not provided';
    if (!is_numeric($sno)) return $sno;
    foreach ($array as $item) {
        if ($item['sno'] == $sno) {
            return $item['name'];
        }
    }
    return $sno;
}

$categoryDisplay = get_display_value($studentData['category'] ?? '', $categories);
$religionDisplay = get_display_value($studentData['religion'] ?? '', $religions);
$genderDisplay = is_numeric($studentData['gender'] ?? '') ? get_gender_name($studentData['gender']) : ($studentData['gender'] ?? 'Not provided');
$domicileDisplay = is_numeric($studentData['domicile'] ?? '') ? get_state_name($studentData['domicile']) : ($studentData['domicile'] ?? 'Not provided');
$motherTongueDisplay = is_numeric($studentData['mother_tongue'] ?? '') ? get_mother_tongue_name($studentData['mother_tongue']) : ($studentData['mother_tongue'] ?? 'Not provided');
$bloodGroupDisplay = is_numeric($studentData['blood_group'] ?? '') ? get_blood_group_name($studentData['blood_group']) : ($studentData['blood_group'] ?? 'Not provided');
$weightageDisplay = is_numeric($studentData['weightage'] ?? '') ? get_weightage_name($studentData['weightage']) : ($studentData['weightage'] ?? 'Not provided');
$statusDisplay = is_numeric($studentData['status'] ?? '') ? get_student_status_name($studentData['status']) : ($studentData['status'] ?? 'Not provided');

$p_stateDisplay = '';
if (!empty($studentData['p_state'])) {
    $p_stateDisplay = is_numeric($studentData['p_state']) 
        ? get_state_name($studentData['p_state']) 
        : $studentData['p_state'];
}

$c_stateDisplay = '';
if (!empty($studentData['c_state'])) {
    $c_stateDisplay = is_numeric($studentData['c_state']) 
        ? get_state_name($studentData['c_state']) 
        : $studentData['c_state'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Form Preview - <?php echo htmlspecialchars($studentData['candidate_name'] ?? 'N/A'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/uin_form.css">
    <style>
        .preview-field {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem 0.75rem;
            min-height: 38px;
            display: flex;
            align-items: center;
        }
        .preview-field.empty {
            color: #6c757d;
            font-style: italic;
        }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 5px;
            background: #fff;
        }
        @media print {
            .form-actions {
                display: none !important;
            }
            .preview-field {
                background-color: #fff;
                border: 1px solid #000;
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
                            <h1 class="form-main-title">Registration 2025 | Unique Identification Number (UIN)</h1>
                            <h2 class="form-step-title">Admission Form Preview</h2>
                        </div>

                        <div class="form-section mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Candidate Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="preview-field <?php echo empty($studentData['candidate_name']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['candidate_name'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Father's Name <span class="text-danger">*</span>
                                    </label>
                                    <div class="preview-field <?php echo empty($studentData['fathers_name']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['fathers_name'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mother's Name</label>
                                    <div class="preview-field <?php echo empty($studentData['mothers_name']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['mothers_name'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Date of Birth <span class="text-danger">*</span>
                                    </label>
                                    <div class="preview-field <?php echo empty($studentData['dob']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['dob'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Aadhar <span class="text-danger">*</span></label>
                                    <div class="preview-field <?php echo empty($studentData['aadhaar']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['aadhaar'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">E-Mail <span class="text-danger">*</span></label>
                                    <div class="preview-field <?php echo empty($studentData['email']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['email'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mobile <span class="text-danger">*</span></label>
                                    <div class="preview-field <?php echo empty($studentData['mobile']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['mobile'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Course Type</label>
                                    <div class="preview-field <?php echo empty($studentData['course_type']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['course_type'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Course Applying For <span class="text-danger">*</span></label>
                                    <div class="preview-field <?php echo empty($courseAppliedFor) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($courseAppliedFor ?: 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                    <div class="preview-field <?php echo empty($categoryDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($categoryDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Religion</label>
                                    <div class="preview-field <?php echo empty($religionDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($religionDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <div class="preview-field <?php echo empty($genderDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($genderDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp Mobile No.</label>
                                    <div class="preview-field <?php echo empty($studentData['whatsapp_mobile']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['whatsapp_mobile'] ?? ($studentData['mobile'] ?? 'Not provided')); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Parents Mobile No.</label>
                                    <div class="preview-field <?php echo empty($studentData['parents_mobile']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['parents_mobile'] ?? 'Not provided'); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Domicile</label>
                                    <div class="preview-field <?php echo empty($domicileDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($domicileDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mother Tongue</label>
                                    <div class="preview-field <?php echo empty($motherTongueDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($motherTongueDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Weightage</label>
                                    <div class="preview-field <?php echo empty($weightageDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($weightageDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Blood Group</label>
                                    <div class="preview-field <?php echo empty($bloodGroupDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($bloodGroupDisplay); ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <div class="preview-field <?php echo empty($statusDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($statusDisplay); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="upload-section mb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        PHOTO UPLOAD <span class="text-danger">*</span>
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['photo_upload'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['photo_upload']); ?>" 
                                                 alt="Photo" class="preview-image" id="photoPreview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No photo uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        SIGNATURE UPLOAD <span class="text-danger">*</span>
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['signature_upload'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['signature_upload']); ?>" 
                                                 alt="Signature" class="preview-image" id="signaturePreview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No signature uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Aadhaar Card Image (JPG/JPEG/PNG)
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['aadhar_upload'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['aadhar_upload']); ?>"
                                                 alt="Aadhaar" class="preview-image" id="aadharPreview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No Aadhaar card uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">
                                        PAN Card Image (JPG/JPEG/PNG)
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['pan_card'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['pan_card']); ?>"
                                                 alt="PAN" class="preview-image" id="panPreview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No PAN card uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        High School (10th) Marksheet Image (JPG/JPEG/PNG)
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['result_10'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['result_10']); ?>"
                                                 alt="10th Marksheet" class="preview-image" id="marksheet10Preview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No 10th marksheet uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                        Intermediate (12th) Marksheet Image (JPG/JPEG/PNG)
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['result_12'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['result_12']); ?>"
                                                 alt="12th Marksheet" class="preview-image" id="marksheet12Preview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No 12th marksheet uploaded</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">
                                    Other Qualification Image (JPG/JPEG/PNG)
                                    </label>
                                    <div class="mt-2">
                                        <?php if (!empty($studentData['ug_marksheet'])): ?>
                                            <img src="<?php echo htmlspecialchars($studentData['ug_marksheet']); ?>"
                                                 alt="UG Marksheet" class="preview-image" id="marksheetUGPreview">
                                        <?php else: ?>
                                            <div class="preview-field empty">No UG marksheet uploaded</div>
                                        <?php endif; ?>
                                    </div>
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
                                        <?php if (!empty($qualifications)): ?>
                                            <?php foreach ($qualifications as $idx => $qual): ?>
                                                <tr>
                                                    <td><?php echo $idx + 1; ?></td>
                                                    <td><?php echo htmlspecialchars($qual['exam_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['board'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['college_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['passing_year'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['roll_no'] ?? ''); ?></td>
                                                    <td>
                                                        <?php
                                                            $hasCgpa = !empty($qual['grade']);
                                                            $hasPercentage = !$hasCgpa && (
                                                                !empty($qual['percentage']) ||
                                                                (!empty($qual['marks_obtained']) && !empty($qual['total_marks']))
                                                            );
                                                            
                                                            if ($hasCgpa) {
                                                                echo 'CGPA';
                                                            } elseif ($hasPercentage) {
                                                                echo 'Percentage';
                                                            } else {
                                                                echo 'Select';
                                                            }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($qual['marks_obtained'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['total_marks'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['percentage'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['grade'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($qual['status'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="12" class="text-center">No qualification records found.</td>
                                            </tr>
                                        <?php endif; ?>
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
                                    <label class="form-label">House No./Village</label>
                                    <div class="preview-field <?php echo empty($studentData['p_house']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_house'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Post</label>
                                    <div class="preview-field <?php echo empty($studentData['p_post']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_post'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tehsil</label>
                                    <div class="preview-field <?php echo empty($studentData['p_tehsil']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_tehsil'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Thana</label>
                                    <div class="preview-field <?php echo empty($studentData['p_thana']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_thana'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">District</label>
                                    <div class="preview-field <?php echo empty($studentData['p_district']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_district'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <div class="preview-field <?php echo empty($p_stateDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($p_stateDisplay ?: 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pin</label>
                                    <div class="preview-field <?php echo empty($studentData['p_pin']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['p_pin'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="address-section mb-4">
                            <div class="section-header-bar">
                                <h3 class="section-title">Correspondence Address</h3>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">House No./Village</label>
                                    <div class="preview-field <?php echo empty($studentData['c_house']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_house'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Post</label>
                                    <div class="preview-field <?php echo empty($studentData['c_post']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_post'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tehsil</label>
                                    <div class="preview-field <?php echo empty($studentData['c_tehsil']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_tehsil'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Thana</label>
                                    <div class="preview-field <?php echo empty($studentData['c_thana']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_thana'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">District</label>
                                    <div class="preview-field <?php echo empty($studentData['c_district']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_district'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <div class="preview-field <?php echo empty($c_stateDisplay) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($c_stateDisplay ?: 'Not provided'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pin</label>
                                    <div class="preview-field <?php echo empty($studentData['c_pin']) ? 'empty' : ''; ?>">
                                        <?php echo htmlspecialchars($studentData['c_pin'] ?? 'Not provided'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions mt-4 d-flex justify-content-between flex-wrap gap-3">
                            <a href="uin_reg_form.php?step=3" class="btn btn-secondary btn-lg px-5">Back to Admission Form</a>
                            
                            <form method="POST" action="uin_print.php" id="finalSubmitForm" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo (int)($studentData['student_id'] ?? $studentData['id']); ?>">
                                <input type="hidden" name="final_submit" value="1">
                                <button type="submit" class="btn btn-warning btn-lg px-5">Final Submit</button>
                            </form>
                        </div>
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
</body>
</html>
