<?php
ob_start();
session_start();
include("script/settings.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================================================================================
   AJAX HANDLERS
   ========================================================================================= */
if (isset($_GET['action'])) {

    $link = $db;
    $data = [];
    $status = 'error';
    $message = '';

    if ($_GET['action'] == 'check_uin') {
        $uin = $link->real_escape_string($_GET['uin']);
        $uin = trim($uin);

        if ($uin != '') {
            $sql_info = "SELECT sno, stu_name, form_no FROM student_info WHERE uin='$uin' OR form_no='$uin'";
            $res_info = @$link->query($sql_info);

            if ($res_info && $res_info->num_rows > 0) {
                $row = $res_info->fetch_assoc();
                $status = 'exists_in_info';
                $data = $row;
                $message = 'Student already exists.';
            } else {
                $sql_reg = "SELECT * FROM uin_register_student WHERE uin='$uin' OR registration_no='$uin'";
                $res_reg = $link->query($sql_reg);
                if ($res_reg->num_rows > 0) {
                    $status = 'found_in_uin';
                    $data = $res_reg->fetch_assoc();
                } else {
                    $status = 'not_found';
                    $message = 'No record found in UIN database.';
                }
            }
        }
    } elseif ($_GET['action'] == 'fetch_student') {
        $id = (int) $_GET['id'];
        // Fetch Student Data using NEW Fee Tables
        $sql = "SELECT si.*, 
                (SELECT discount FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as invoice_discount,
                (SELECT amount_paid FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as invoice_paid,
                (SELECT mode_of_payment FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as mode_of_payment,
                (SELECT payment_method_type FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as payment_method_type,
                (SELECT utr_number FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as utr_number,
                (SELECT txn_date FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as txn_date,
                (SELECT chq_no FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as chq_no,
                (SELECT cheque_date FROM fee_invoice WHERE student_id=si.sno AND type='fees' ORDER BY sno DESC LIMIT 1) as cheque_date
                FROM student_info si WHERE si.sno='$id'";

        // Note: LEFT JOIN + LIMIT might behave oddly if multiple invoices. 
        // Better: Subqueries or proper join. Since we want *latest* invoice:
        $sql = "SELECT si.*, 
                si.discount as invoice_discount,
                (SELECT SUM(amount_paid) FROM fee_invoice WHERE student_id=si.sno AND status < 2) as invoice_paid,
                (SELECT mode_of_payment FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as mode_of_payment,
                (SELECT payment_method_type FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as payment_method_type,
                (SELECT utr_number FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as utr_number,
                (SELECT txn_date FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as txn_date,
                (SELECT chq_no FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as chq_no,
                (SELECT cheque_date FROM fee_invoice WHERE student_id=si.sno ORDER BY sno DESC LIMIT 1) as cheque_date
                FROM student_info si WHERE si.sno='$id'";

        $res = $link->query($sql);
        if ($res->num_rows > 0) {
            $status = 'success';
            $data = $res->fetch_assoc();
        }
    } elseif ($_GET['action'] == 'get_dropdowns') {
        // Fetch Subjects
        $sql_sub = "SELECT * FROM add_subject WHERE is_active=1 ORDER BY order_no ASC";
        $res_sub = $link->query($sql_sub);
        $subs_main = [];
        $subs_other = [];
        while ($row = $res_sub->fetch_assoc()) {
            if ($row['subject_type'] == 1)
                $subs_main[] = $row;
            else
                $subs_other[] = $row;
        }

        // Fetch Gender
        $genders = [];
        $res_gen = $link->query("SELECT gender_sno, gender_name FROM genders");
        if ($res_gen)
            while ($r = $res_gen->fetch_assoc())
                $genders[] = $r;

        // Fetch Categories
        $cats = [];
        $res_cat = $link->query("SELECT categories_sno, category_name FROM categories");
        if ($res_cat)
            while ($r = $res_cat->fetch_assoc())
                $cats[] = $r;

        $status = 'success';
        $data = [
            'subjects' => ['main' => $subs_main, 'other' => $subs_other],
            'genders' => $genders,
            'categories' => $cats
        ];
    } elseif ($_GET['action'] == 'get_class_fees') {
        $class_id = intval($_GET['class_id']);
        $doa = $_GET['doa'];
        $session = get_session_by_date($doa);

        // Criteria Params
        $gender_id = isset($_GET['gender']) ? $db->real_escape_string($_GET['gender']) : '';
        $category_id = isset($_GET['category']) ? $db->real_escape_string($_GET['category']) : '';
        // Using defaults matching user logic (if blank, treat as All? Or use params)
        $sub_type = isset($_GET['subject_type']) ? $db->real_escape_string($_GET['subject_type']) : 'Aided';
        $inc_group = isset($_GET['income_group']) ? $db->real_escape_string($_GET['income_group']) : 'General';

        // Resolve IDs to Names
        $gender = 'All';
        $category = 'All';
        if ($gender_id) {
            $gQ = $db->query("SELECT gender_name FROM genders WHERE gender_sno='$gender_id'");
            if ($gQ && $gQ->num_rows > 0)
                $gender = strtoupper($gQ->fetch_object()->gender_name);
        }
        if ($category_id) {
            $cQ = $db->query("SELECT category_name FROM categories WHERE categories_sno='$category_id'");
            if ($cQ && $cQ->num_rows > 0)
                $category = $cQ->fetch_object()->category_name;
        }

        $critWhere = " AND (criteria_gender = 'All' OR criteria_gender = '$gender')
                        AND (criteria_category = 'All' OR criteria_category = '$category')
                        AND (criteria_subject_type = 'All' OR criteria_subject_type = '$sub_type')
                        AND (criteria_income_group = 'All' OR criteria_income_group = '$inc_group')";

        // Fetch applicable fees
        $sql = "SELECT fs.id, fs.amount, fh.head_name, fh.is_mandatory 
                FROM fee_structure fs 
                JOIN fee_heads fh ON fs.fee_head_id = fh.id
                WHERE fs.class_id='$class_id' AND fs.academic_session='$session' AND fs.status=1 $critWhere";

        $res = $link->query($sql);
        $fees = [];
        $total = 0;

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $fees[] = $row;
                // Sum all (Simple Total)
                $total += floatval($row['amount']);
            }
        }

        $status = 'success';
        $data = ['fees' => $fees, 'total' => $total, 'session' => $session];
    }

    echo json_encode(['status' => $status, 'data' => $data, 'message' => $message]);
    exit;
}

$msg = '';
if (isset($_GET['msg'])) {
    $msg_type = $_GET['type'] ?? 'success';
    $msg_content = urldecode($_GET['msg']);
    if ($msg_type == 'danger')
        $msg = '<div class="alert alert-danger">' . $msg_content . '</div>';
    else
        $msg = '<div class="alert alert-success">' . $msg_content . '</div>';
}

/* =========================================================================================
   HANDLE FORM SUBMISSION
   ========================================================================================= */
if (isset($_POST['submit_admission'])) {

    $link = $db;
    $err_msg = '';

    if (empty($_POST['form_no']))
        $err_msg .= '<li>Form No is required.</li>';
    if (empty($_POST['s_name']))
        $err_msg .= '<li>Student Name is required.</li>';
    if (empty($_POST['s_class']))
        $err_msg .= '<li>Class is required.</li>';

    if ($err_msg == '') {
        $mode = $_POST['submission_mode'];
        $sno = $_POST['student_sno'] ?? 0;

        // Data Collection
        $s_name = strtoupper($_POST['s_name']);
        $f_name = strtoupper($_POST['f_name']);
        $m_name = strtoupper($_POST['m_name']);
        $dob = $_POST['dob'] ?: '2000-01-01';
        $doa = $_POST['doa'] ?: date('Y-m-d');

        $gender = $_POST['gen'];
        $category = $_POST['opt_cat'];

        $minority = $_POST['opt_minor'];
        $physical_handicapped = $_POST['physical_handicapped'] ?? 'NO';
        $annual_income = $_POST['annual_income'] ?? '';

        $class_id = $_POST['s_class'];
        $batch = $_POST['batch'] ?? '';
        $form_no = $_POST['form_no'];
        $enroll_no = $_POST['enroll_no'] ?? '';
        $uin = $_POST['uin'] ?? '';
        $roll_no = $_POST['roll_no'] ?? '';

        // Subjects
        $sub1 = $_POST['sub1'] ?? '';
        $sub2 = $_POST['sub2'] ?? '';
        $sub3 = $_POST['sub3'] ?? '';

        // Other Subjects
        $ot_sub1 = $_POST['other_sub_minor'] ?? '';
        $ot_sub2 = $_POST['other_sub_vocational'] ?? '';
        $ot_sub3 = $_POST['other_sub_cc'] ?? '';

        // Address / Contact
        $p_mobile = $_POST['p_mobile'];
        $parent_mobile = $_POST['parent_mobile'] ?? '';
        $whatsapp = $_POST['whatsapp_mobile'] ?? '';
        $aadhaar = $_POST['aadhaar'] ?? '';
        $email = $_POST['email'] ?? '';

        $p_house = $_POST['p_house'] ?? '';
        $p_post = $_POST['p_post'] ?? '';
        $p_district = $_POST['p_district'] ?? '';
        $p_state = $_POST['p_state'] ?? '';
        $p_pin = $_POST['p_pin'] ?? '';

        // Fee Details
        $total_fees = empty($_POST['fees_amount']) ? 0 : $_POST['fees_amount'];
        $discount = empty($_POST['fees_discount']) ? 0 : $_POST['fees_discount'];
        $amount_paid = empty($_POST['fees_deposit']) ? 0 : $_POST['fees_deposit'];

        $mode_of_payment = $_POST['mode_of_payment'] ?? 'cash';
        $payment_method_type = $_POST['payment_method_type'] ?? ''; // Added field

        // Payment Extra Details - FIXED DATES
        $utr_number = $_POST['utr_number'] ?? '';
        $txn_date = $_POST['txn_date'] ?? '';
        $chq_no = $_POST['chq_no'] ?? '';
        $cheque_date = $_POST['cheque_date'] ?? '';

        // Formatting Dates for SQL: Use NULL if empty
        // Formatting Dates for SQL: Keep raw or null. Helper function handles quoting.
        $txn_date_val = !empty($txn_date) ? $txn_date : null;
        $cheque_date_val = !empty($cheque_date) ? $cheque_date : null;

        $user_id = $_SESSION['username'] ?? 'admin';

        // AUTO-GENERATE UIN for NEW ADMISSION (Fixed: Uses Batch Year & Correct Increment)
        if ($mode == 'new') {
            $batch_year = date('Y');
            if (!empty($batch) && preg_match('/(\d{4})/', $batch, $matches)) {
                $batch_year = $matches[1]; // Extract 2025 from '2025-26'
            }

            $prefix = "RM";
            $search_like = $prefix . $batch_year . '%';

            // 1. Check latest UIN in student_info (Actual Admissions)
            $last_num_info = 0;
            $res_info = $db->query("SELECT uin FROM student_info WHERE uin LIKE '$search_like' ORDER BY uin DESC LIMIT 1");
            if ($res_info && $res_info->num_rows > 0) {
                $last_uin = $res_info->fetch_object()->uin;
                $last_num_info = (int) str_replace($prefix, '', $last_uin);
            }

            // 2. Check latest UIN in uin_register_student (Master Registry)
            $last_num_reg = 0;
            $res_reg = $db->query("SELECT uin FROM uin_register_student WHERE uin LIKE '$search_like' ORDER BY uin DESC LIMIT 1");
            if ($res_reg && $res_reg->num_rows > 0) {
                $last_uin = $res_reg->fetch_object()->uin;
                $last_num_reg = (int) str_replace($prefix, '', $last_uin);
            }

            // 3. Take Max and Increment
            $max_num = max($last_num_info, $last_num_reg);

            if ($max_num > 0) {
                $uin = $prefix . ($max_num + 1);
            } else {
                // First arrival for this year
                $uin = $prefix . $batch_year . '0001';
            }
        }

        // Image Handling logic
        // Directory INSIDE admin folder (per user request)
        $target_dir = __DIR__ . '/student_images/';
        // User requested NO mkdir logic ("folder banane ki jarurat nahi hai")


        // Default Filenames (Will be overwritten by specific logic below)
        $photo_filename = '';
        $sig_filename = '';

        // Check Existing logic for EDIT
        if ($mode == 'edit') {

            // Get Current Paths from DB
            $curr_photo = '';
            $curr_sig = '';
            $e_res = $db->query("SELECT photo_id, signature_id FROM student_info WHERE sno='$sno'");
            if ($e_res && $e_res->num_rows > 0) {
                $e_row = $e_res->fetch_assoc();
                $curr_photo = $e_row['photo_id'];
                $curr_sig = $e_row['signature_id'];
            }

            // PHOTO UPLOAD
            if (!empty($_FILES['photo_upload']['name'])) {
                if (!empty($curr_photo)) {

                    $target_file = __DIR__ . '/' . $curr_photo;
                    handleImageUpload($_FILES['photo_upload'], $target_file);
                    $photo_db_val = $curr_photo;
                } else {

                    $fname = 'A' . $sno . '.jpg';
                    $target_file = $target_dir . 'photo/' . $fname;
                    handleImageUpload($_FILES['photo_upload'], $target_file);
                    $photo_db_val = 'student_images/photo/' . $fname;
                }
            } else {
                $photo_db_val = $curr_photo;
            }

            // SIG UPLOAD
            if (!empty($_FILES['sig_upload']['name'])) {
                if (!empty($curr_sig)) {
                    $target_file = __DIR__ . '/' . $curr_sig;
                    handleImageUpload($_FILES['sig_upload'], $target_file);
                    $sig_db_val = $curr_sig;
                } else {
                    $fname = 'A' . $sno . '.jpg';
                    $target_file = $target_dir . 'signature/' . $fname;
                    handleImageUpload($_FILES['sig_upload'], $target_file);
                    $sig_db_val = 'student_images/signature/' . $fname;
                }
            } else {
                $sig_db_val = $curr_sig;
            }






            $reason_ids = [];
            $old_res = $db->query("SELECT * FROM student_info WHERE sno='$sno'");
            if ($old_res && $old_res->num_rows > 0) {
                $old_row = $old_res->fetch_assoc();

                // 1. Class
                if ($old_row['class'] != $class_id)
                    $reason_ids[] = 'Class Change';

                // 2. Category
                if ($old_row['category'] != $category)
                    $reason_ids[] = 'Category Change';

                // 3. Gender
                if ($old_row['gender'] != $gender)
                    $reason_ids[] = 'Gender Change';

                // 4. Fees

                if ((float) $old_row['fees'] != (float) $total_fees)
                    $reason_ids[] = 'Fees Change';

                // 5. Subject

                if (
                    $old_row['sub1'] != $sub1 || $old_row['sub2'] != $sub2 || $old_row['sub3'] != $sub3 ||
                    $old_row['ot_sub1'] != $ot_sub1 || $old_row['ot_sub2'] != $ot_sub2 || $old_row['ot_sub3'] != $ot_sub3
                ) {
                    $reason_ids[] = 'Subject Change';
                }
            }





            if (!empty($reason_ids)) {
                $reason_str = implode(', ', $reason_ids);



                $sql_copy = "INSERT INTO student_info2 
                    (stu_name, father_name, mother_name, class, batch, dob, date_of_admission, gender, photo_id, signature_id, form_no, enroll_no, category, sub1, sub2, sub3, ot_sub1, ot_sub2, ot_sub3, status, income_certificate, acc_no, annual_income, other_univ, p_mobile, user_id, minority, physical_handicapped, fees, p_house, p_district, p_state, p_pin, email, aadhaar, whatsapp_mobile, uin, student_info_sno, reason, parent_mobile)
                    SELECT stu_name, father_name, mother_name, class, batch, dob, date_of_admission, gender, photo_id, signature_id, form_no, enroll_no, category, sub1, sub2, sub3, ot_sub1, ot_sub2, ot_sub3, status, income_certificate, acc_no, annual_income, other_univ, p_mobile, user_id, minority, physical_handicapped, fees, p_house, p_district, p_state, p_pin, email, aadhaar, whatsapp_mobile, uin, sno, '$reason_str', parent_mobile
                    FROM student_info WHERE sno='$sno'";
                $db->query($sql_copy);
            }


            // 3. Update Fee Invoice (Sync Payment Details)

            // 3. Update Fee Invoice (Moved to after Student Update to ensure consistency)
            // Removed old direct update query.

            // UPDATE
            $sql_update = "UPDATE student_info SET 
                stu_name='$s_name', father_name='$f_name', mother_name='$m_name', class='$class_id', batch='$batch', dob='$dob', date_of_admission='$doa', 
                gender='$gender', photo_id='$photo_db_val', signature_id='$sig_db_val', form_no='$form_no', enroll_no='$enroll_no', category='$category', 
                sub1='$sub1', sub2='$sub2', sub3='$sub3', 
                ot_sub1='$ot_sub1', ot_sub2='$ot_sub2', ot_sub3='$ot_sub3',
                p_mobile='$p_mobile', whatsapp_mobile='$whatsapp', email='$email', aadhaar='$aadhaar',
                minority='$minority', physical_handicapped='$physical_handicapped', 
                fees='$total_fees', discount='$discount', annual_income='$annual_income', uin='$uin',
                p_house='$p_house', p_district='$p_district', p_state='$p_state', p_pin='$p_pin',
                parent_mobile='$parent_mobile'
                WHERE sno='$sno'";

            if ($db->query($sql_update)) {
                // --- UPDATE FEE INVOICE (Edit Mode) ---
                // Re-Assign Fees Logic (Ensure all heads exist)
                assignStudentFees($sno, $class_id, $doa);

                // Handle Payment Update (Incremental)
                // Fetch current total paid to calculate difference
                $cq = $db->query("SELECT SUM(amount_paid) as total_paid, SUM(discount) as total_disc FROM fee_invoice WHERE student_id='$sno' AND status < 2");
                $cres = $cq->fetch_assoc();
                $curr_paid = floatval($cres['total_paid'] ?? 0);
                $curr_disc = floatval($cres['total_disc'] ?? 0);

                $pay_diff = $amount_paid - $curr_paid; // New Total - Old Total
                $disc_diff = $discount - $curr_disc;

                if ($pay_diff > 0 || $disc_diff > 0) {
                    // Pass only positive differences to processor
                    $p_in = max(0, $pay_diff);
                    $d_in = max(0, $disc_diff);

                    if ($p_in > 0 || $d_in > 0) {
                        $pay_details = [
                            'payment_date' => $doa,
                            'mode' => $mode_of_payment,
                            'type' => $payment_method_type,
                            'utr' => $utr_number,
                            'chq_no' => $chq_no,
                            'cheque_date' => $cheque_date_val,
                            'txn_date' => $txn_date_val,
                            'remarks' => $remarks ?? 'Updated via Edit',
                            'session' => $session
                        ];

                        processStudentPayment($sno, $p_in, $pay_details);
                    }
                }
                // I've ensured Insert path is correct. Edit path assigns dues (updates structure).

                $final_msg = 'Student Updated Successfully!';
                header("Location: new_admission.php?msg=" . urlencode($final_msg));
                exit;
            } else {
                $final_msg = 'Update Error: ' . $db->error;
                header("Location: new_admission.php?msg=" . urlencode($final_msg) . "&type=danger");
                exit;
            }

        } else {
            // INSERT
            $insert_fields = "stu_name, father_name, mother_name, class, batch, dob, date_of_admission, 
                gender, form_no, enroll_no, category, sub1, sub2, sub3, ot_sub1, ot_sub2, ot_sub3, status, 
                p_mobile, whatsapp_mobile, email, aadhaar, uin,
                user_id, minority, physical_handicapped, fees, discount, photo_id, signature_id,
                p_house, p_district, p_state, p_pin, annual_income, parent_mobile";

            $insert_values = "'$s_name', '$f_name', '$m_name', '$class_id', '$batch', '$dob', '$doa', 
                '$gender', '$form_no', '$enroll_no', '$category', '$sub1', '$sub2', '$sub3', '$ot_sub1', '$ot_sub2', '$ot_sub3', 2, 
                '$p_mobile', '$whatsapp', '$email', '$aadhaar', '$uin',
                '$user_id', '$minority', '$physical_handicapped', '$total_fees', '$discount', '$photo_filename', '$sig_filename',
                '$p_house', '$p_district', '$p_state', '$p_pin', '$annual_income', '$parent_mobile'";

            $sql_student = "INSERT INTO student_info ($insert_fields) VALUES ($insert_values)";

            if ($db->query($sql_student)) {
                $new_sno = $db->insert_id;

                // ===============================================
                // IMAGE HANDLING FOR NEW / UIN
                // ===============================================

                $p_path_db = '';
                $s_path_db = '';

                // 1. PHOTO
                // Rules: 
                // - If UIN Path exists: USE IT (Don't create new file 'sno.jpg'). Link to same file.
                // - If New Upload with UIN Path: Overwrite that file.
                // - If No UIN Path: Create 'Asno.jpg'.

                if (!empty($_POST['uin_photo_path'])) {
                    // UIN Copy Handling (Re-use Path)

                    // Clean path: usually 'student_images/photo/X.jpg'
                    $clean_p = str_replace('../', '', $_POST['uin_photo_path']);
                    $p_path_db = $clean_p;

                    if (!empty($_FILES['photo_upload']['name'])) {


                        $target = __DIR__ . '/' . $clean_p;
                        handleImageUpload($_FILES['photo_upload'], $target);
                    }

                } else {
                    // Fresh New Admission (No UIN)
                    $fname = 'A' . $new_sno . '.jpg';
                    $target = $target_dir . 'photo/' . $fname;

                    if (!empty($_FILES['photo_upload']['name'])) {
                        handleImageUpload($_FILES['photo_upload'], $target);
                        $p_path_db = 'student_images/photo/' . $fname;
                    }
                }

                // 2. SIGNATURE
                if (!empty($_POST['uin_sig_path'])) {
                    $clean_s = str_replace('../', '', $_POST['uin_sig_path']);
                    $s_path_db = $clean_s;

                    if (!empty($_FILES['sig_upload']['name'])) {
                        $target = __DIR__ . '/' . $clean_s;
                        handleImageUpload($_FILES['sig_upload'], $target);
                    }
                } else {
                    // Fresh New Admission
                    $fname = 'A' . $new_sno . '.jpg';
                    $target = $target_dir . 'signature/' . $fname;

                    if (!empty($_FILES['sig_upload']['name'])) {
                        handleImageUpload($_FILES['sig_upload'], $target);
                        $s_path_db = 'student_images/signature/' . $fname;
                    }
                }

                // UPDATE DB if paths set
                if ($p_path_db || $s_path_db) {
                    $db->query("UPDATE student_info SET photo_id='$p_path_db', signature_id='$s_path_db' WHERE sno='$new_sno'");
                }

                // --- 1. RE-ASSIGN FEES (Update/Insert Dues in fee_invoice) ---
                if ($new_sno > 0) {
                    assignStudentFees($new_sno, $class_id, $doa); // Logic in settings.php inserts into fee_invoice
                } else {
                    // Fallback check: If new_sno is 0, fetch by Form No? 
                    // Or just log error?
                    // Let's try to fetch if 0.
                    $chk = $db->query("SELECT sno FROM student_info WHERE form_no='$form_no'");
                    if ($chk && $chk->num_rows > 0) {
                        $new_sno = $chk->fetch_object()->sno;
                        assignStudentFees($new_sno, $class_id, $doa);
                    }
                }

                // --- 2. UPDATE PAYMENT in FEE INVOICE ---
                // We update PAID AMOUNT in the INVOICE directly (Single Table Transaction)
                // Use sno instead of id for fee_invoice
                // --- 2. UPDATE PAYMENT using Centralized Function ---
                if ($amount_paid > 0 || $discount > 0) {
                    $pay_details = [
                        'payment_date' => $doa,
                        'mode' => $mode_of_payment,
                        'type' => $payment_method_type,
                        'utr' => $utr_number,
                        'chq_no' => $chq_no,
                        'cheque_date' => $cheque_date_val,
                        'txn_date' => $txn_date_val,
                        'remarks' => $remarks ?? '',
                        'session' => $session
                    ];

                    // Helper function now handles calculation, status update, and metadata replication
                    processStudentPayment($new_sno, $amount_paid, $pay_details);
                }

                $_SESSION['msg'] = 'Admission Successful! Form No: ' . $form_no;
                header("Location: new_admission.php");
                exit;
            } else {
                $_SESSION['msg'] = 'Insertion Error: ' . $db->error;
                $_SESSION['msg_type'] = 'danger'; // Optional: for styling
                header("Location: new_admission.php");
                exit;
            }
        }
    } else {
        $msg = '<div class="alert alert-danger"><ul>' . $err_msg . '</ul></div>';
    }
}




if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();
?>

<!-- Custom Styles -->
<style>
    .axe-heading {
        position: relative;
        font-size: 20px;
        font-weight: 700;
        color: #0d6efd;
        margin-bottom: 20px;
        padding-left: 18px;
    }

    .axe-heading::before {
        content: '';
        position: absolute;
        left: 0;
        top: 4px;
        width: 5px;
        height: 90%;
        background: #0d6efd;
        border-radius: 4px;
    }

    .form-box {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
        margin-bottom: 30px;
        max-width: 1300px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Top Section: Left inputs, Right Photo */
    .columns-container {
        display: grid;
        grid-template-columns: 1fr 250px;
        gap: 20px;
        align-items: start;
    }

    .left-column {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .right-column {
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }

    .form-row.four-col {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
    }

    .form-row.three-col {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 10px;
    }

    label {
        font-weight: 600;
        margin-bottom: 6px;
        font-size: 14px;
        color: #333;
    }

    input,
    select {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        color: #333 !important;
        background-color: #fff !important;
        width: 100%;
    }

    input::placeholder {
        color: #999 !important;
        opacity: 1;
    }

    input:focus,
    select:focus {
        border-color: #0d6efd;
        outline: none;
    }

    .sc-btn {
        background: #0d6efd;
        color: #fff;
        border: none;
        padding: 10px 24px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 500;
    }

    .sc-btn:hover {
        background: #084298;
    }

    .sc-btn-outline {
        background: transparent;
        color: #0d6efd;
        border: 1px solid #0d6efd;
        padding: 8px 20px;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #555;
        margin: 20px 0 15px;
        text-transform: uppercase;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
    }

    .mode-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        background: #e9ecef;
        padding: 5px;
        border-radius: 8px;
        width: fit-content;
    }

    .mode-tab {
        padding: 10px 20px;
        cursor: pointer;
        border-radius: 6px;
        font-weight: 600;
        color: #555;
        transition: 0.3s;
    }

    .mode-tab.active {
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .search-panel {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #90caf9;
    }

    /* Image Box Fixes */
    .img-box {
        border: 1px solid #ccc;
        border-radius: 4px;
        margin-bottom: 5px;
        background: #f9f9f9;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
        width: 100%;
        max-width: 180px;
        /* Reduced width */
    }

    .img-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        /* Fix zoom */
    }

    .alert {
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @media(max-width:900px) {
        .columns-container {
            grid-template-columns: 1fr;
        }

        .form-row.four-col,
        .form-row.three-col {
            grid-template-columns: repeat(2, 1fr);
        }

        .right-column {
            align-items: flex-start;
        }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    if (typeof setup === 'undefined') {
        window.setup = () => { /* Same Alpine fallbacks */
            return { isDark: false, color: 'cyan' }
        }
    }
</script>

<div style="padding: 20px;">

    <div class="form-box">

        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 class="axe-heading">Admission Management</h2>

            <div class="mode-tabs">
                <div class="mode-tab active" onclick="setMode('new', this)">New Admission</div>
                <div class="mode-tab" onclick="setMode('uin', this)">UIN Copy</div>
                <div class="mode-tab" onclick="setMode('edit', this)">Edit Admission</div>
            </div>
        </div>

    </div>

    <?php
    if (isset($_SESSION['msg'])) {
        echo '<div class="alert alert-success">' . $_SESSION['msg'] . '</div>';
        unset($_SESSION['msg']);
    }
    if ($msg && !isset($_SESSION['msg'])) {
        // If msg is set from POST error flow above
        echo $msg;
    }
    ?>

    <div id="search_panel" class="search-panel" style="display:none;">
        <div class="form-row" style="grid-template-columns: 1fr auto; display:grid; gap:10px;">
            <div class="form-group">
                <input type="text" id="search_input" placeholder="Enter UIN / Form No"
                    style="color:#000 !important; font-weight:500;">
            </div>
            <button type="button" class="sc-btn" id="search_btn" onclick="performSearch()">Search</button>
        </div>
        <div id="search_status" style="margin-top:10px; font-weight:bold;"></div>
    </div>

    <form method="post" enctype="multipart/form-data" id="admission_form">
        <input type="hidden" name="submission_mode" id="submission_mode" value="new">
        <input type="hidden" name="student_sno" id="student_sno">
        <input type="hidden" name="uin" id="uin_val">
        <input type="hidden" name="uin_photo_path" id="uin_photo_path">
        <input type="hidden" name="uin_sig_path" id="uin_sig_path">

        <div class="columns-container">
            <!-- LEFT COLUMN (Basic Details) -->
            <div class="left-column">

                <!-- Row 1: Form No, Enroll No, DOA, Class -->
                <div class="form-row four-col">
                    <div class="form-group">
                        <label>Form No <span style="color:red">*</span></label>
                        <input type="text" name="form_no" id="form_no" required>
                    </div>
                    <div class="form-group">
                        <label>Enroll No</label>
                        <input type="text" name="enroll_no" id="enroll_no">
                    </div>
                    <div class="form-group">
                        <label>Date of Admission <span style="color:red">*</span></label>
                        <input type="date" name="doa" id="doa" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Class <span style="color:red">*</span></label>
                        <select name="s_class" id="s_class" required>
                            <option value="">Select Class</option>
                            <?php
                            $res = $db->query("select * from class_detail order by sort_no, abs(year), sno");
                            while ($row = $res->fetch_assoc()) {
                                echo '<option value="' . $row['sno'] . '">' . $row['class_description'] . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="section-title">Personal Details</div>

                <!-- Row 2: Name, Father, Mother -->
                <div class="form-row three-col">
                    <div class="form-group">
                        <label>Candidate Name <span style="color:red">*</span></label>
                        <input type="text" name="s_name" id="s_name" required>
                    </div>
                    <div class="form-group">
                        <label>Father's Name</label>
                        <input type="text" name="f_name" id="f_name">
                    </div>
                    <div class="form-group">
                        <label>Mother's Name</label>
                        <input type="text" name="m_name" id="m_name">
                    </div>
                </div>

                <!-- Row 3: DOB, Gender, Category -->
                <div class="form-row three-col">
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" id="dob">
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gen" id="gen">
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="opt_cat" id="opt_cat">
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>

                <!-- Row 4: Batch, Income, PH (Moved to balance height) -->
                <div class="form-row three-col">
                    <div class="form-group">
                        <label>Batch / Year</label>
                        <input type="text" name="batch" id="batch" placeholder="Ex: 2025-26">
                    </div>
                    <div class="form-group">
                        <label>Annual Income</label>
                        <select name="annual_income" id="annual_income">
                            <option value="">Select</option>
                            <option value="Below 1 Lakh">Below 1 Lakh</option>
                            <option value="Below 3 Lakh">Below 3 Lakh</option>
                            <option value="Below 5 Lakh">Below 5 Lakh</option>
                            <option value="Above 5 Lakh">Above 5 Lakh</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Physical Handicapped</label>
                        <select name="physical_handicapped" id="physical_handicapped">
                            <option value="NO">No</option>
                            <option value="YES">Yes</option>
                        </select>
                    </div>
                </div>

                <!-- Row 5: Minority, Aadhaar, Mobile -->
                <div class="form-row three-col">
                    <div class="form-group">
                        <label>Minority</label>
                        <select name="opt_minor" id="opt_minor">
                            <option value="NO">No</option>
                            <option value="YES">Yes</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Aadhaar</label>
                        <input type="text" name="aadhaar" id="aadhaar">
                    </div>
                    <div class="form-group">
                        <label>Mobile <span style="color:red">*</span></label>
                        <input type="text" name="p_mobile" id="p_mobile" maxlength="10"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN (Photo/Sig) -->
            <div class="right-column">
                <!-- Photo -->
                <div class="img-box" style="height:200px;">
                    <img id="photoPreview"
                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 150 200' width='150' height='200'%3E%3Crect width='150' height='200' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3EStudent Photo%3C/text%3E%3C/svg%3E"
                        alt="Student Photo">
                </div>
                <div style="text-align:center;">
                    <label for="photo_upload" class="sc-btn-outline"
                        style="cursor:pointer; display:inline-block; font-size:12px; padding:5px 10px;">Select
                        Photo</label>
                    <input type="file" name="photo_upload" id="photo_upload" style="display:none;"
                        onchange="previewImage(this, '#photoPreview')">
                </div>

                <!-- Signature -->
                <div class="img-box" style="height:80px; margin-top:10px;">
                    <img id="sigPreview"
                        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 80' width='300' height='80'%3E%3Crect width='300' height='80' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3EStudent Signature%3C/text%3E%3C/svg%3E"
                        alt="Signature">
                </div>
                <div style="text-align:center;">
                    <label for="sig_upload" class="sc-btn-outline"
                        style="cursor:pointer; display:inline-block; font-size:12px; padding:5px 10px;">Select
                        Signature</label>
                    <input type="file" name="sig_upload" id="sig_upload" style="display:none;"
                        onchange="previewImage(this, '#sigPreview')">
                </div>
            </div>
        </div>

        <!-- BOTTOM SECTION (Rest of the fields) -->
        <div style="margin-top:20px;">

            <div class="section-title">Contact & Address Details</div>

            <!-- Row 1: Parent Mob, WhatsApp, Email, House -->
            <div class="form-row four-col">
                <div class="form-group">
                    <label>Parent Mobile <span style="color:red">*</span></label>
                    <input type="text" name="parent_mobile" id="parent_mobile" maxlength="10"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                </div>
                <div class="form-group">
                    <label>WhatsApp</label>
                    <input type="text" name="whatsapp_mobile" id="whatsapp_mobile" maxlength="10"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email">
                </div>
                <div class="form-group">
                    <label>House/Village</label>
                    <input type="text" name="p_house" id="p_house">
                </div>
            </div>

            <!-- Row 2: District, State, Pin -->
            <div class="form-row four-col">
                <div class="form-group">
                    <label>District</label>
                    <input type="text" name="p_district" id="p_district">
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="p_state" id="p_state">
                </div>
                <div class="form-group">
                    <label>Pin Code</label>
                    <input type="text" name="p_pin" id="p_pin">
                </div>
            </div>



            <div class="section-title" id="main_subjects_title">Main Subjects</div>
            <div class="form-row three-col" id="main_subjects_row">
                <div class="form-group">
                    <label>Subject 1</label>
                    <select name="sub1" id="sub1">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject 2</label>
                    <select name="sub2" id="sub2">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subject 3</label>
                    <select name="sub3" id="sub3">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>

            <div class="section-title">Other Subjects</div>
            <div class="form-row three-col">
                <div class="form-group">
                    <label>Co-Curricular</label>
                    <select name="other_sub_minor" id="other_sub_minor">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Vocational Subject</label>
                    <select name="other_sub_vocational" id="other_sub_vocational">
                        <option value="">Select</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Other</label>
                    <select name="other_sub_cc" id="other_sub_cc">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>

            <div class="section-title">Fees Details</div>
            <div class="form-row four-col">
                <div class="form-group">
                    <label>Mode</label>
                    <select name="mode_of_payment" id="mode_of_payment" onchange="togglePaymentFields()">
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Fees</label>
                    <input type="number" name="fees_amount" id="fees_amount" step="0.01">
                </div>
                <div class="form-group">
                    <label>Discount</label>
                    <input type="number" name="fees_discount" id="fees_discount" step="0.01">
                </div>
                <div class="form-group">
                    <label>Amount Paid</label>
                    <input type="number" name="fees_deposit" id="fees_deposit" step="0.01">
                </div>
            </div>

            <!-- DYNAMIC PAYMENT FIELDS -->
            <div id="online_fields" class="form-row three-col"
                style="display:none; background:#f0f8ff; padding:10px; border-radius:6px; margin-top:10px;">
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method_type" id="payment_method_type">
                        <option value="">Select</option>
                        <option value="UPI">UPI</option>
                        <option value="NEFT">NEFT</option>
                        <option value="RTGS">RTGS</option>
                        <option value="CARD">Card</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>UTR / Txn ID</label>
                    <input type="text" name="utr_number" id="utr_number">
                </div>
                <div class="form-group">
                    <label>Txn Date</label>
                    <input type="date" name="txn_date" id="txn_date">
                </div>
            </div>

            <div id="cheque_fields" class="form-row three-col"
                style="display:none; background:#fff0f5; padding:10px; border-radius:6px; margin-top:10px;">
                <div class="form-group">
                    <label>Cheque No</label>
                    <input type="text" name="chq_no" id="chq_no">
                </div>
                <div class="form-group">
                    <label>Cheque Date</label>
                    <input type="date" name="cheque_date" id="cheque_date">
                </div>
            </div>

        </div>




        <div style="margin-top:30px; text-align:center;">
            <button type="submit" name="submit_admission" class="sc-btn" style="width:200px;">Submit</button>
        </div>
    </form>
</div>
</div>

<script>
    var currentMode = 'new';
    var globalData = {};

    var photoPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 150 200' width='150' height='200'%3E%3Crect width='150' height='200' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3EStudent Photo%3C/text%3E%3C/svg%3E";
    var sigPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 80' width='300' height='80'%3E%3Crect width='300' height='80' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3EStudent Signature%3C/text%3E%3C/svg%3E";

    $(document).ready(function () {

        $('#admission_form').on('submit', function (e) {
            // Mobile Validation
            var s_mob = $('#p_mobile').val();
            var p_mob = $('#parent_mobile').val();
            var w_mob = $('#whatsapp_mobile').val();

            if (s_mob == p_mob) {
                alert('Mobile (Student) cannot be the same as Parent Mobile.');
                e.preventDefault(); return false;
            }
            if (w_mob && w_mob == p_mob) {
                alert('WhatsApp Number cannot be the same as Parent Mobile.');
                e.preventDefault(); return false;
            }

            // Payment Validation
            var pay_mode = $('#mode_of_payment').val();
            if (pay_mode == 'online') {
                if (!$('#payment_method_type').val() || !$('#utr_number').val() || !$('#txn_date').val()) {
                    alert('Please fill all Online Payment details (Method, UTR, Date).');
                    e.preventDefault(); return false;
                }
            } else if (pay_mode == 'cheque') {
                if (!$('#chq_no').val() || !$('#cheque_date').val()) {
                    alert('Please fill Cheque No and Date.');
                    e.preventDefault(); return false;
                }
            }

            // Photo/Sig Validation for NEW
            // Only if submission mode is 'new' (which currentMode tracks)
            if (currentMode == 'new') {
                if ($('#photo_upload').get(0).files.length === 0 && !$('#uin_photo_path').val()) {
                    alert('Student Photo is required for New Admission.');
                    e.preventDefault(); return false;
                }
                if ($('#sig_upload').get(0).files.length === 0 && !$('#uin_sig_path').val()) {
                    alert('Student Signature is required for New Admission.');
                    e.preventDefault(); return false;
                }
            }
        });

        // LOAD DROPDOWNS: Subjects, Gender, Categories, Payment methods
        $.getJSON('new_admission.php?action=get_dropdowns', function (resp) {
            if (resp.status == 'success') {
                globalData = resp.data;
                populateDropdowns();
            }
        });
    });

    function populateDropdowns() {
        // Subjects
        var mainOpts = '<option value="">Select</option>';
        globalData.subjects.main.forEach(function (s) {
            mainOpts += '<option value="' + s.sno + '">' + s.subject + '</option>';
        });

        var otherOpts = '<option value="">Select</option>';
        globalData.subjects.other.forEach(function (s) {
            otherOpts += '<option value="' + s.sno + '">' + s.subject + '</option>';
        });

        $('#sub1, #sub2, #sub3').html(mainOpts);
        $('#other_sub_minor, #other_sub_vocational, #other_sub_cc').html(otherOpts);

        // Genders
        var genOpts = '<option value="">Select</option>';
        var genders = globalData.genders;

        // Fallback if DB is empty or fetch failed
        if (!genders || genders.length === 0) {
            genders = [
                { gender_name: 'MALE', gender_sno: '1' },
                { gender_name: 'FEMALE', gender_sno: '2' },
                { gender_name: 'OTHER', gender_sno: '3' }
            ];
        }

        genders.forEach(function (g) {
            genOpts += '<option value="' + g.gender_sno + '">' + g.gender_name + '</option>';
        });
        $('#gen').html(genOpts);

        // Categories
        var catOpts = '<option value="">Select</option>';
        globalData.categories.forEach(function (c) {
            catOpts += '<option value="' + c.categories_sno + '">' + c.category_name + '</option>';
        });
        $('#opt_cat').html(catOpts);

        // Payment Methods
        if (globalData.payment_methods) {
            var payOpts = '<option value="">Select</option>';
            globalData.payment_methods.forEach(function (p) {
                payOpts += '<option value="' + p.method_name + '">' + p.method_name + '</option>';
            });
            $('#payment_method_type').html(payOpts);
        }
    }

    function togglePaymentFields() {
        var mode = $('#mode_of_payment').val();
        $('#online_fields').hide();
        $('#cheque_fields').hide();

        if (mode == 'online') $('#online_fields').show();
        if (mode == 'cheque') $('#cheque_fields').show();
    }

    function setMode(mode, btn) {
        currentMode = mode;
        $('#submission_mode').val(mode);

        $('.mode-tab').removeClass('active');
        $(btn).addClass('active');

        // Clear Messages
        $('.alert').remove();

        $('#admission_form')[0].reset();
        $('#photoPreview').attr('src', photoPlaceholder);
        $('#sigPreview').attr('src', sigPlaceholder);
        $('#search_status').text('');
        togglePaymentFields();

        // Explicitly clear photo inputs and hidden paths
        $('#uin_photo_path').val('');
        $('#uin_sig_path').val('');
        $('#fees_deposit').val('');
        $('#fees_discount').val('');

        if (mode == 'new') {
            $('#search_panel').slideUp();
            $('#form_no').prop('readonly', false);
        } else {
            $('#search_panel').slideDown();
            if (mode == 'uin') $('#search_input').attr('placeholder', 'Enter UIN');
            if (mode == 'edit') $('#search_input').attr('placeholder', 'Enter UIN or Form No');
        }
    }

    function performSearch() {
        var val = $('#search_input').val();
        if (!val) return;

        $.getJSON('new_admission.php?action=check_uin&uin=' + val, function (resp) {
            if (currentMode == 'uin') {
                if (resp.status == 'exists_in_info') {
                    if (confirm(resp.message + ' Do you want to edit this student?')) {
                        setMode('edit', $('.mode-tab:contains("Edit")'));
                        loadStudentForEdit(resp.data.sno);
                    } else {
                        $('#search_status').text(resp.message).css('color', 'red');
                    }
                } else if (resp.status == 'found_in_uin') {
                    fillFormFromUIN(resp.data);
                } else {
                    $('#search_status').text(resp.message).css('color', 'red');
                }
            }
            else if (currentMode == 'edit') {
                if (resp.status == 'exists_in_info') {
                    loadStudentForEdit(resp.data.sno);
                } else {
                    $('#search_status').text('Student not found.').css('color', 'red');
                }
            }
        });
    }

    function loadStudentForEdit(sno) {
        $.getJSON('new_admission.php?action=fetch_student&id=' + sno, function (resp) {
            if (resp.status == 'success') {
                var d = resp.data;
                $('#student_sno').val(d.sno);
                $('#form_no').val(d.form_no);
                $('#enroll_no').val(d.enroll_no || '');
                $('#s_name').val(d.stu_name);
                $('#f_name').val(d.father_name);
                $('#m_name').val(d.mother_name);
                $('#doa').val(d.date_of_admission);
                $('#dob').val(d.dob);
                $('#s_class').val(d.class);
                $('#batch').val(d.batch);

                // Populate Dropdowns again incase not loaded? No usually loaded by now.
                $('#sub1').val(d.sub1);
                $('#sub2').val(d.sub2);
                $('#sub3').val(d.sub3);

                $('#other_sub_minor').val(d.ot_sub1 || '');
                $('#other_sub_vocational').val(d.ot_sub2 || '');
                $('#other_sub_cc').val(d.ot_sub3 || '');

                $('#fees_amount').val(d.fees);
                $('#fees_discount').val(d.invoice_discount);
                $('#fees_deposit').val(d.invoice_paid);

                // Payment Data
                if (d.mode_of_payment) {
                    $('#mode_of_payment').val(d.mode_of_payment.toLowerCase());
                    togglePaymentFields();

                    if (d.mode_of_payment.toLowerCase() == 'online') {
                        $('#payment_method_type').val(d.payment_method_type);
                        $('#utr_number').val(d.utr_number);
                        $('#txn_date').val(d.txn_date);
                    } else if (d.mode_of_payment.toLowerCase() == 'cheque') {
                        $('#chq_no').val(d.chq_no);
                        $('#cheque_date').val(d.cheque_date);
                    }
                } else {
                    $('#mode_of_payment').val('cash');
                    togglePaymentFields();
                }

                if (d.photo_id) {
                    var pPath = d.photo_id.replace('../', '');
                    $('#photoPreview').attr('src', pPath + '?t=' + new Date().getTime());
                } else {
                    $('#photoPreview').attr('src', photoPlaceholder);
                }

                if (d.signature_id) {
                    var sPath = d.signature_id.replace('../', '');
                    $('#sigPreview').attr('src', sPath + '?t=' + new Date().getTime());
                } else {
                    $('#sigPreview').attr('src', sigPlaceholder);
                }

                $('#p_mobile').val(d.p_mobile);
                $('#parent_mobile').val(d.parent_mobile || '');
                $('#whatsapp_mobile').val(d.whatsapp_mobile || '');
                $('#email').val(d.email || '');
                $('#aadhaar').val(d.aadhaar || '');
                $('#uin_val').val(d.uin || ''); // Update UIN hidden

                $('#opt_cat').val(d.category);

                // Gender Mapping (F->2, M->1) if value is text
                var gVal = d.gender;
                if (isNaN(gVal)) { // IF text like F, M, Male
                    var upp = gVal.toUpperCase();
                    if (upp.startsWith('M')) gVal = 1;
                    else if (upp.startsWith('F')) gVal = 2;
                    else if (upp.startsWith('O')) gVal = 3;
                }
                $('#gen').val(gVal);

                $('#annual_income').val(d.annual_income);
                $('#opt_minor').val(d.minority);
                $('#physical_handicapped').val(d.physical_handicapped || 'NO');

                $('#p_house').val(d.p_house || '');
                $('#p_district').val(d.p_district || '');
                $('#p_state').val(d.p_state || '');
                $('#p_pin').val(d.p_pin || '');
            }
        });
    }

    function fillFormFromUIN(d) {
        $('#uin_val').val(d.uin);
        // Do NOT set form_no (Manual)
        $('#enroll_no').val(d.registration_no || ''); // In UIN Registry it might be registration_no
        $('#s_name').val(d.candidate_name);
        $('#f_name').val(d.fathers_name);
        $('#m_name').val(d.mothers_name);
        $('#dob').val(d.dob);
        $('#aadhaar').val(d.aadhaar || '');
        $('#email').val(d.email || '');
        $('#p_mobile').val(d.mobile || '');
        $('#whatsapp_mobile').val(d.whatsapp_mobile || '');

        $('#p_house').val(d.p_house || '');
        $('#p_district').val(d.p_district || '');
        $('#p_state').val(d.p_state || '');
        $('#p_pin').val(d.p_pin || '');

        // Parent Mobile from UIN
        $('#parent_mobile').val(d.parents_mobile || '');

        // Gender
        var gVal = d.gender;
        // UIN usually has '1', '2'. If text, map it.
        if (isNaN(gVal)) {
            var upp = gVal.toUpperCase();
            if (upp.startsWith('M')) gVal = 1;
            else if (upp.startsWith('F')) gVal = 2;
            else if (upp.startsWith('O')) gVal = 3;
        }
        $('#gen').val(gVal);

        $('#opt_cat').val(d.category);
        $('#opt_minor').val(d.minority || 'NO');

        if (d.photo_upload) {
            var pPath = d.photo_upload.replace('../', '');
            $('#uin_photo_path').val(pPath);
            $('#photoPreview').attr('src', pPath);
        }
        if (d.signature_upload) {
            var sPath = d.signature_upload.replace('../', '');
            $('#uin_sig_path').val(sPath);
            $('#sigPreview').attr('src', sPath);
        }
    }

    function fetchFees() {
        var cls = $('#s_class').val();
        var doa = $('#doa').val();

        if (!cls || !doa) return;

        // Use current selection for criteria
        var gen = $('#gen').val();
        // Assuming category and others are available or use defaults
        var cat = $('#opt_cat').val();

        var url = 'new_admission.php?action=get_class_fees&class_id=' + cls + '&doa=' + doa;
        if (gen) url += '&gender=' + gen;
        if (cat) url += '&category=' + cat;

        // Simplification: We just want the Total mostly.
        $.getJSON(url, function (resp) {
            if (resp.status == 'success') {
                // If manual edit is allowed, we might overwrite, so check if user edited? 
                // Ideally we show "Standard Fees" vs "Final Fees".
                // For now, auto-fill.
                $('#fees_amount').val(resp.data.total.toFixed(2));
            }
        });
    }

    // Bind events
    $(document).ready(function () {
        $('#s_class, #doa, #gen, #opt_cat').on('change', fetchFees);
        $('#s_class').on('change', toggleSubjectFields);

        // Initial check (in case of edit or reload)
        // We might need to wait for dropdowns or at least the class text
        setTimeout(toggleSubjectFields, 500);
    });

    function toggleSubjectFields() {
        var classText = $('#s_class option:selected').text().toUpperCase();
        var isPG = false;

        // Define keywords for PG / B.Ed courses where subjects are NOT required/visible
        // "B.ED" or "M.A", "M.SC", "M.COM" etc.
        // User Update: ONLY B.Ed and ADCA
        var pgKeywords = ["B.ED", "ADCA"];

        for (var i = 0; i < pgKeywords.length; i++) {
            if (classText.indexOf(pgKeywords[i]) !== -1) {
                isPG = true;
                break;
            }
        }

        if (isPG) {
            // Hide and Make Optional
            $('#main_subjects_title').hide();
            $('#main_subjects_row').hide();

            // Clear values so they don't get submitted if hidden
            $('#sub1, #sub2, #sub3').val('');
            $('#sub1, #sub2, #sub3').prop('required', false);
        } else {
            // Show and Make Compulsory (if logic dictates they should be compulsory)
            $('#main_subjects_title').show();
            $('#main_subjects_row').show();

            // If you want them strictly required for UG:
            // $('#sub1').prop('required', true); 
            // Keeping them optional by default as per current code, unless user asked for "compulsory nahi rahega" implies they were compulsory?
            // The user said: "bed ya aisa pg course select hoga to subject compulsory nahi rahega"
            // This implies for OTHERS it MIGHT be compulsory.
            // But currently HTML doesn't have 'required'. 
            // I will adhere to "compulsory nahi rahega" for PG. 
        }
    }

    function previewImage(input, selector) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $(selector).attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>