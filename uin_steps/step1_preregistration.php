<?php
require_once __DIR__ . '/../scripts/settings_dbase.php';
$categories = get_dropdown_options('categories', 'categories_sno', 'category_name');
$states = get_states();
$courseTypes = get_course_types();
$processRegistrationUrl = 'scripts/api/process_preregistration.php';
$getCoursesUrl = 'scripts/api/get_courses.php';
?>

<form id="step1Form" method="POST" action="<?php echo htmlspecialchars($processRegistrationUrl); ?>">
    <div class="form-section">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="candidate_name" class="form-label">
                    CANDIDATE NAME <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="candidate_name" name="candidate_name" 
                       value="<?php echo htmlspecialchars($formData['candidate_name'] ?? ''); ?>" required>
                <small class="text-danger"></small>
            </div>

            <div class="col-md-6">
                <label for="fathers_name" class="form-label">
                    FATHER'S NAME <span class="text-danger">*</span>
                </label>
                <input type="text" class="form-control" id="fathers_name" name="fathers_name" 
                       value="<?php echo htmlspecialchars($formData['fathers_name'] ?? ''); ?>" required>
                <small class="text-danger"></small>
            </div>
            <div class="col-md-6">
                <label for="mothers_name" class="form-label">MOTHER'S NAME</label>
                <input type="text" class="form-control" id="mothers_name" name="mothers_name" 
                       value="<?php echo htmlspecialchars($formData['mothers_name'] ?? ''); ?>">
            </div>

            <div class="col-md-6">
                <label for="dob" class="form-label">
                    DATE OF BIRTH <span class="text-danger">*</span>
                </label>
                <div class="row g-2">
                    <div class="col-4">
                        <select class="form-select" id="dob_day" name="dob_day" required>
                            <option value="">Day</option>
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($formData['dob_day']) && $formData['dob_day'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="form-select" id="dob_month" name="dob_month" required>
                            <option value="">Month</option>
                            <?php 
                            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                            foreach ($months as $idx => $month): 
                                $monthNum = $idx + 1;
                            ?>
                                <option value="<?php echo $monthNum; ?>" <?php echo (isset($formData['dob_month']) && $formData['dob_month'] == $monthNum) ? 'selected' : ''; ?>>
                                    <?php echo $month; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <select class="form-select" id="dob_year" name="dob_year" required>
                            <option value="">Year</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($i = $currentYear - 5; $i >= $currentYear - 50; $i--): 
                            ?>
                                <option value="<?php echo $i; ?>" <?php echo (isset($formData['dob_year']) && $formData['dob_year'] == $i) ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <small class="text-danger"></small>
            </div>

            
            <div class="col-md-6">
                <label for="mobile" class="form-label">MOBILE <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="mobile" name="mobile" 
                       value="<?php echo htmlspecialchars($formData['mobile'] ?? ''); ?>" 
                       pattern="[0-9]{10}" maxlength="10" required>
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">E-MAIL <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
            </div>

            <div class="col-md-6">
                <label for="course_type" class="form-label">COURSE TYPE <span class="text-danger">*</span></label>
                <select class="form-select" id="course_type" name="course_type" required>
                    <option value="">---Select Your Course Type---</option>
                    <?php foreach ($courseTypes as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>" <?php echo (isset($formData['course_type']) && $formData['course_type'] == $category) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <div class="col-md-6">
                <label for="course_applied_for" class="form-label">COURSE APPLYING FOR <span class="text-danger">*</span></label>
                <select class="form-select" id="course_applied_for" name="course_applied_for" required data-selected="<?php echo htmlspecialchars($formData['course_applied_for'] ?? ''); ?>">
                    <option value="">---Select Course---</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="category" class="form-label">CATEGORY <span class="text-danger">*</span></label>
                <select class="form-select" id="category" name="category" required>
                    <option value="">---Select Your Category---</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['sno']); ?>" <?php echo (isset($formData['category']) && $formData['category'] == $cat['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            
            <div class="col-md-6">
                <label for="aadhaar" class="form-label">AADHAR <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="aadhaar" name="aadhaar" 
                       value="<?php echo htmlspecialchars($formData['aadhaar'] ?? ''); ?>" 
                       pattern="[0-9]{12}" maxlength="12" required>
            </div>
        </div>
    </div>

   
    <div class="address-section">
        <div class="section-header-bar">
            <h3 class="section-title">Permanent Address</h3>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label for="p_house" class="form-label">HOUSE NO./VILLAGE</label>
                <input type="text" class="form-control" id="p_house" name="p_house" 
                       value="<?php echo htmlspecialchars($formData['p_house'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="p_post" class="form-label">POST</label>
                <input type="text" class="form-control" id="p_post" name="p_post" 
                       value="<?php echo htmlspecialchars($formData['p_post'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="p_tehsil" class="form-label">TAHSIL</label>
                <input type="text" class="form-control" id="p_tehsil" name="p_tehsil" 
                       value="<?php echo htmlspecialchars($formData['p_tehsil'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="p_thana" class="form-label">THANA</label>
                <input type="text" class="form-control" id="p_thana" name="p_thana" 
                       value="<?php echo htmlspecialchars($formData['p_thana'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="p_district" class="form-label">DISTRICT</label>
                <input type="text" class="form-control" id="p_district" name="p_district" 
                       value="<?php echo htmlspecialchars($formData['p_district'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="p_state" class="form-label">STATE <span class="text-danger">*</span></label>
                <select class="form-select" id="p_state" name="p_state" required>
                    <option value="">---Select Your Domicile---</option>
                    <?php foreach ($states as $state): ?>
                        <option value="<?php echo htmlspecialchars($state['sno']); ?>" <?php echo (isset($formData['p_state']) && $formData['p_state'] == $state['sno']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($state['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="p_pin" class="form-label">PIN</label>
                <input type="text" class="form-control" id="p_pin" name="p_pin" 
                       value="<?php echo htmlspecialchars($formData['p_pin'] ?? ''); ?>" 
                       pattern="[0-9]{6}" maxlength="6">
            </div>
        </div>
    </div>

    <div class="fees-section mt-4" style="display:none;">
        <div class="row">
            <div class="col-md-6">
                <label for="fees" class="form-label">FEES OF UNIQUE IDENTIFICATION NUMBER</label>
                <input type="number" class="form-control" id="fees" name="fees" value="100" min="100" readonly>
            </div>
        </div>
    </div>

    
    <div class="form-actions mt-4 d-flex justify-content-between flex-wrap gap-3">
    <a href="index.php" class="btn btn-secondary btn-lg px-5">Back</a>
    <button type="submit" class="btn btn-primary btn-lg px-5">Continue</button>
</div>

</form>

<script>
const coursesEndpoint = '<?php echo htmlspecialchars($getCoursesUrl); ?>';
const courseTypeSelect = document.getElementById('course_type');
const courseAppliedSelect = document.getElementById('course_applied_for');

function populateCourses(courseType, selectedValue = '') {
    courseAppliedSelect.innerHTML = '<option value="">---Select Course---</option>';

    if (!courseType) return;

    fetch(`${coursesEndpoint}?course_type=${encodeURIComponent(courseType)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            data.courses.forEach(row => {
                const opt = document.createElement('option');
                opt.value = row.id;
                opt.textContent = row.name;

                if (selectedValue && selectedValue === row.id) {
                    opt.selected = true;
                }

                courseAppliedSelect.appendChild(opt);
            });
        })
        .catch(err => console.error(err));
}

/* change event */
courseTypeSelect.addEventListener('change', function () {
    populateCourses(this.value);
});

/* page reload ke time (edit mode) */
if (courseTypeSelect.value) {
    populateCourses(
        courseTypeSelect.value,
        courseAppliedSelect.dataset.selected || ''
    );
}
</script>


