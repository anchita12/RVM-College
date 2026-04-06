

document.addEventListener('DOMContentLoaded', function () {
    initializeValidation();
    const step1Form = document.getElementById('step1Form');
    const step2Form = document.getElementById('step2Form');
    const step3Form = document.getElementById('step3Form');

    if (step1Form) {
        step1Form.addEventListener('submit', handleStep1Submit);
    }

    if (step2Form) {
        step2Form.addEventListener('submit', handleStep2Submit);
    }

    if (step3Form) {
        step3Form.addEventListener('submit', handleStep3Submit);
    }

    setupRealTimeValidation();
});


function initializeValidation() {
    const mobileInputs = document.querySelectorAll('input[type="tel"][name="mobile"], input[name="whatsapp_mobile"], input[name="parents_mobile"]');
    mobileInputs.forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
            validateMobile(this);
        });
    });

    const aadhaarInput = document.querySelector('input[name="aadhaar"]');
    if (aadhaarInput) {
        aadhaarInput.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);
            validateAadhaar(this);
        });
    }

    const pinInputs = document.querySelectorAll('input[name="p_pin"], input[name="c_pin"]');
    pinInputs.forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    });

    const emailInput = document.querySelector('input[type="email"]');
    if (emailInput) {
        emailInput.addEventListener('blur', function () {
            validateEmail(this);
        });
    }
}
function setupRealTimeValidation() {
    const requiredFields = document.querySelectorAll('input[required], select[required], textarea[required]');
    requiredFields.forEach(field => {
        field.addEventListener('blur', function () {
            validateField(this);
        });
    });
}

function validateMobile(input) {
    const value = input.value.trim();
    const isValid = /^[0-9]{10}$/.test(value);

    if (value && !isValid) {
        showFieldError(input, 'Please enter a valid 10-digit mobile number');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function validateAadhaar(input) {
    const value = input.value.trim();
    const isValid = /^[0-9]{12}$/.test(value);

    if (value && !isValid) {
        showFieldError(input, 'Please enter a valid 12-digit Aadhaar number');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}


function validateEmail(input) {
    const value = input.value.trim();
    const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    if (value && !isValid) {
        showFieldError(input, 'Please enter a valid email address');
        return false;
    } else {
        clearFieldError(input);
        return true;
    }
}

function validateField(field) {
    const value = field.value.trim();
    const isRequired = field.hasAttribute('required');

    if (isRequired && !value) {
        showFieldError(field, 'This field is required');
        return false;
    }

    if (field.type === 'email' && value) {
        return validateEmail(field);
    }

    if (field.name === 'mobile' && value) {
        return validateMobile(field);
    }

    if (field.name === 'aadhaar' && value) {
        return validateAadhaar(field);
    }

    clearFieldError(field);
    return true;
}


function showFieldError(field, message) {
    clearFieldError(field);

    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('input[required], select[required], textarea[required]');

    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });


    const emailField = form.querySelector('input[type="email"]');
    if (emailField && emailField.value) {
        if (!validateEmail(emailField)) {
            isValid = false;
        }
    }

    const mobileField = form.querySelector('input[name="mobile"]');
    if (mobileField && mobileField.value) {
        if (!validateMobile(mobileField)) {
            isValid = false;
        }
    }

    const aadhaarField = form.querySelector('input[name="aadhaar"]');
    if (aadhaarField && aadhaarField.value) {
        if (!validateAadhaar(aadhaarField)) {
            isValid = false;
        }
    }

    return isValid;
}

function handleStep1Submit(e) {
    e.preventDefault();

    const form = e.target;

    const dobDay = document.getElementById('dob_day').value;
    const dobMonth = document.getElementById('dob_month').value;
    const dobYear = document.getElementById('dob_year').value;

    if (!dobDay || !dobMonth || !dobYear) {
        showAlert('Please select complete date of birth', 'danger');
        return;
    }

    if (!validateForm(form)) {
        showAlert('Please fill all mandatory fields correctly', 'danger');
        return;
    }


    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }

    const formData = new FormData(form);

    const requestUrl = form.action || 'api/process_preregistration.php';

    fetch(requestUrl, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            return response.text().then(text => {
                if (!text) {
                    if (!response.ok) {
                        throw new Error(`Server error (${response.status})`);
                    }
                    throw new Error('Empty response received from server. Please try again.');
                }

                try {
                    const data = JSON.parse(text);

                    if (!response.ok) {
                        const message = data && data.message ? data.message : `Server error (${response.status})`;
                        throw new Error(message);
                    }

                    return data;
                } catch (parseError) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Server returned an unexpected response. Please contact support.');
                }
            });
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Pre-registration successful!', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || 'uin_reg_form.php?step=2';
                }, 1000);
            } else {
                showAlert(data.message || 'Registration failed. Please try again.', 'danger');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'An error occurred. Please try again.', 'danger');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
}

function handleStep2Submit(e) {
    e.preventDefault();

    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    if (!confirm('Are you sure you want to proceed with payment?')) {
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing Payment...';

    const formData = new FormData(form);

    // Log form data for debugging
    console.log('Submitting payment with data:', {
        student_id: formData.get('student_id'),
        uin: formData.get('uin'),
        amount: formData.get('amount')
    });

    fetch('api/process_payment.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            console.log('Response status:', response.status, response.statusText);
            return response.text().then(text => {
                console.log('Response text:', text);

                if (!text || text.trim() === '') {
                    if (!response.ok) {
                        throw new Error(`Server error (${response.status}): ${response.statusText}`);
                    }
                    throw new Error('Empty response received from server. Please try again.');
                }

                // Try to parse JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    console.error('Response text was:', text);
                    throw new Error('Server returned invalid response. Response: ' + text.substring(0, 200));
                }

                if (!response.ok) {
                    const message = data && data.message ? data.message : `Server error (${response.status})`;
                    throw new Error(message);
                }

                return data;
            });
        })
        .then(data => {
            console.log('Payment response data:', data);

            if (data.success) {
                showAlert(data.message || 'Payment successful!', 'success');
                const redirectUrl = data.redirect || ('uin_reg_form.php?step=3&payment=success&student_id=' + (data.student_id || formData.get('student_id') || ''));
                console.log('Redirecting to:', redirectUrl);
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                const errorMsg = data.message || 'Payment failed. Please try again.';
                console.error('Payment failed:', errorMsg);
                showAlert(errorMsg, 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Payment Error Details:', error);
            console.error('Error stack:', error.stack);
            const errorMsg = error.message || 'An error occurred. Please try again.';
            showAlert(errorMsg, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

function handleStep3Submit(e) {
    e.preventDefault();

    const form = e.target;

    if (!validateForm(form)) {
        showAlert('Please fill all mandatory fields correctly', 'danger');
        return;
    }

    const qualifications = form.querySelectorAll('[name^="qualification"]');
    let hasQualification = false;
    qualifications.forEach(q => {
        if (q.value && q.name.includes('[exam_name]')) {
            hasQualification = true;
        }
    });

    if (!hasQualification) {
        showAlert('Please enter at least one qualification', 'danger');
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

    const formData = new FormData(form);

    fetch('api/process_admission.php', {
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
                    throw new Error('Invalid response from server. Please check console for details.');
                }
            });
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message || 'Admission form submitted successfully!', 'success');
                setTimeout(() => {
                    window.location.href = data.redirect || 'uin_reg_form.php?step=4&success=1';
                }, 1500);
            } else {
                showAlert(data.message || 'Submission failed. Please try again.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'An error occurred. Please try again.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) return;

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);


    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.type.startsWith('image/')) {
            showAlert('Please select a valid image file', 'danger');
            input.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            showAlert('Image size should be less than 2MB', 'danger');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
        };
        reader.readAsDataURL(file);
    }
    preview.style.width = '120px';
    preview.style.height = '150px';
    preview.style.objectFit = 'cover';

}

