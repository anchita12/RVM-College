<?php
ob_start();
session_start();
require_once __DIR__ . '/script/settings.php';
$mysqli = $db;

if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();

function fetch_options($table, $sno, $name)
{
    global $mysqli;
    $res = $mysqli->query("SELECT $sno as sno, $name as name FROM $table ORDER BY $name");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

$categories = fetch_options('categories', 'categories_sno', 'category_name');
$religions = fetch_options('religions', 'religion_sno', 'religion_name');
$genders = fetch_options('genders', 'gender_sno', 'gender_name');
$domiciles = fetch_options('states', 'state_sno', 'state_name');
$motherTongues = fetch_options('mother_tongues', 'language_sno', 'language_name');
$weightages = fetch_options('weightages', 'weightage_sno', 'weightage_name');
$bloodGroups = fetch_options('blood_groups', 'blood_group_sno', 'blood_group_name');
$statuses = [['sno' => 1, 'name' => 'Regular'], ['sno' => 2, 'name' => 'Distance Learning']];
$examNames = fetch_options('exam_names', 'exam_sno', 'exam_name');

$registration_no = $_POST['registration_no'] ?? '';
$studentData = null;
$qualifications = [];

if (!empty($registration_no)) {
    $stmt = $mysqli->prepare("SELECT * FROM uin_register_student WHERE registration_no = ? OR uin = ?");
    $stmt->bind_param("ss", $registration_no, $registration_no);
    $stmt->execute();
    $studentData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($studentData) {
        $studentId = $studentData['id'];
        $q_stmt = $mysqli->prepare("SELECT * FROM uin_student_qualification WHERE student_id = ? ORDER BY id");
        $q_stmt->bind_param("i", $studentId);
        $q_stmt->execute();
        $res = $q_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $qualifications[] = $row;
        }
        $q_stmt->close();

        if (count($qualifications) < 2) {
            $qualifications = [
                ['exam_name' => 'High School', 'status' => 'Passed'],
                ['exam_name' => 'Intermediate', 'status' => 'Passed']
            ];
        }

        
        $courseAppliedFor = $studentData['course_applied_for'];
        if (is_numeric($courseAppliedFor)) {
            $cStmt = $mysqli->prepare("SELECT class_description FROM class_detail WHERE sno = ?");
            $cStmt->bind_param("i", $courseAppliedFor);
            $cStmt->execute();
            $cRes = $cStmt->get_result();
            if ($row = $cRes->fetch_assoc())
                $courseAppliedFor = $row['class_description'];
            $cStmt->close();
        }
    }
}



$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    try {
        $mysqli->begin_transaction();
        $sid = $_POST['student_id'];

        function handle_upload($key, $fld, $sid, $old)
        {
            if (!isset($_FILES[$key]) || $_FILES[$key]['error'] != 0)
                return $old;
            $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png']))
                return $old;
                
            if (!empty($old)) {
            
                $clean_old = str_replace('../', '', $old);
                $target_file = __DIR__ . '/' . $clean_old;
                
                $dir = dirname($target_file);
                if (!file_exists($dir)) mkdir($dir, 0777, true);
                
                if (function_exists('handleImageUpload')) {
                    handleImageUpload($_FILES[$key], $target_file);
                } else {
                    move_uploaded_file($_FILES[$key]['tmp_name'], $target_file);
                }
                return $clean_old; // Return cleaned DB string without '../'
            } else {
                // If no old image, create new one
                $dir = __DIR__ . "/student_images/$fld/";
                if (!file_exists($dir)) mkdir($dir, 0777, true);
                
                $fn = $sid . "_" . time() . "." . $ext;
                $target_file = $dir . $fn;
                
                if (function_exists('handleImageUpload')) {
                    handleImageUpload($_FILES[$key], $target_file);
                } else {
                    move_uploaded_file($_FILES[$key]['tmp_name'], $target_file);
                }
                return "student_images/$fld/$fn";
            }
        }

        $photo = handle_upload('photo_upload', 'photo', $sid, $_POST['existing_photo']);
        $sign = handle_upload('signature_upload', 'signature', $sid, $_POST['existing_signature']);
        $aadhar = handle_upload('aadhar_file', 'aadhar_upload', $sid, $_POST['existing_aadhar']);
        $pan = handle_upload('pan_card_file', 'pancard', $sid, $_POST['existing_pan']);
        $m10 = handle_upload('marksheet_10_file', 'marksheet10', $sid, $_POST['existing_m10']);
        $m12 = handle_upload('marksheet_12_file', 'marksheet12', $sid, $_POST['existing_m12']);
        $mug = handle_upload('marksheet_ug_file', 'other_qualification', $sid, $_POST['existing_mug']);

        $sql = "UPDATE uin_register_student SET fathers_name=?, mothers_name=?, whatsapp_mobile=?, parents_mobile=?, p_house=?, p_post=?, p_tehsil=?, p_thana=?, p_district=?, p_state=?, p_pin=?, c_house=?, c_post=?, c_tehsil=?, c_thana=?, c_district=?, c_state=?, c_pin=?, photo_upload=?, signature_upload=?, aadhar_upload=?, pan_card=?, result_10=?, result_12=?, ug_marksheet=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssssssssssssssssssssssssi", $_POST['fathers_name'], $_POST['mothers_name'], $_POST['whatsapp_mobile'], $_POST['parents_mobile'], $_POST['p_house'], $_POST['p_post'], $_POST['p_tehsil'], $_POST['p_thana'], $_POST['p_district'], $_POST['p_state'], $_POST['p_pin'], $_POST['c_house'], $_POST['c_post'], $_POST['c_tehsil'], $_POST['c_thana'], $_POST['c_district'], $_POST['c_state'], $_POST['c_pin'], $photo, $sign, $aadhar, $pan, $m10, $m12, $mug, $sid);
        $stmt->execute();

        $mysqli->query("DELETE FROM uin_student_qualification WHERE student_id=$sid");
        if (isset($_POST['qualification'])) {
            $ins = $mysqli->prepare("INSERT INTO uin_student_qualification (student_id, exam_name, board, college_name, passing_year, roll_no, total_marks, marks_obtained, percentage, grade, status) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            foreach ($_POST['qualification'] as $q) {
                if (empty($q['exam_name']))
                    continue;
                $ins->bind_param("issssssssss", $sid, $q['exam_name'], $q['board'], $q['college_name'], $q['passing_year'], $q['roll_no'], $q['total_marks'], $q['marks_obtained'], $q['percentage'], $q['grade'], $q['status']);
                $ins->execute();
            }
        }

        if (!empty($studentData['uin'])) {
            $si = $mysqli->query("SELECT sno FROM student_info WHERE uin='{$studentData['uin']}'")->fetch_assoc();
            if ($si) {
                $sno = $si['sno'];
                $mysqli->query("UPDATE student_info SET father_name='{$_POST['fathers_name']}', mother_name='{$_POST['mothers_name']}', whatsapp_mobile='{$_POST['whatsapp_mobile']}', parent_mobile='{$_POST['parents_mobile']}', p_house='{$_POST['p_house']}', p_district='{$_POST['p_district']}', p_state='{$_POST['p_state']}', p_pin='{$_POST['p_pin']}', photo_id='$photo', signature_id='$sign' WHERE sno=$sno");

                // Update student_info2
                $mysqli->query("UPDATE student_info2 
                                SET  father_name='{$_POST['fathers_name']}', mother_name='{$_POST['mothers_name']}', 
                                whatsapp_mobile='{$_POST['whatsapp_mobile']}', parent_mobile='{$_POST['parents_mobile']}', 
                                p_house='{$_POST['p_house']}', p_district='{$_POST['p_district']}', 
                                p_state='{$_POST['p_state']}', p_pin='{$_POST['p_pin']}', 
                                photo_id='$photo', signature_id='$sign' 
                                WHERE student_info_sno=$sno");
            }
        }

        $mysqli->commit();
        $update_success = true;
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}

?>

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

    .form-row.two-col {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
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
        box-sizing: border-box;
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
        border-radius: 4px;
        cursor: pointer;
    }

    .section-title {
        font-size: 16px;
        font-weight: 700;
        color: #555;
        margin: 20px 0 15px;
        text-transform: uppercase;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-panel {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #90caf9;
        max-width: 1300px;
        margin-left: auto;
        margin-right: auto;
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
    }

    .img-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    @media(max-width:900px) {
        .columns-container {
            grid-template-columns: 1fr;
        }

        .form-row.four-col,
        .form-row.three-col,
        .form-row.two-col {
            grid-template-columns: repeat(2, 1fr);
        }

        .right-column {
            align-items: flex-start;
        }
    }
</style>

<div style="padding: 20px;">

    <div class="search-panel">
        <form method="POST" class="form-row" style="grid-template-columns: 1fr auto; display:grid; gap:10px;">
            <div class="form-group">
                <input type="text" name="registration_no" value="<?= htmlspecialchars($registration_no) ?>"
                    placeholder="Enter Registration Number or UIN" style="color:#000 !important; font-weight:500;"
                    required>
            </div>
            <button type="submit" class="sc-btn">FETCH DATA</button>
        </form>
    </div>

    <?php if ($studentData): ?>
        <div class="form-box">
            <div class="axe-heading">Edit Student Details</div>

            <form id="step3Form" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="student_id" value="<?= $studentData['id'] ?>">
                <input type="hidden" name="registration_no" value="<?= htmlspecialchars($registration_no) ?>">
                <input type="hidden" name="existing_photo" value="<?= $studentData['photo_upload'] ?>">
                <input type="hidden" name="existing_signature" value="<?= $studentData['signature_upload'] ?>">
                <input type="hidden" name="existing_aadhar" value="<?= $studentData['aadhar_upload'] ?>">
                <input type="hidden" name="existing_pan" value="<?= $studentData['pan_card'] ?>">
                <input type="hidden" name="existing_m10" value="<?= $studentData['result_10'] ?>">
                <input type="hidden" name="existing_m12" value="<?= $studentData['result_12'] ?>">
                <input type="hidden" name="existing_mug" value="<?= $studentData['ug_marksheet'] ?>">

                <div class="columns-container">
                    <!-- LEFT COLUMN -->
                    <div class="left-column">
                        <div class="section-title" style="margin-top:0;">Personal Details</div>
                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Candidate Name <span style="color:red">*</span></label>
                                <input type="text" value="<?= htmlspecialchars($studentData['candidate_name']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Father's Name <span style="color:red">*</span></label>
                                <input type="text" name="fathers_name"
                                    value="<?= htmlspecialchars($studentData['fathers_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Mother's Name</label>
                                <input type="text" name="mothers_name"
                                    value="<?= htmlspecialchars($studentData['mothers_name']) ?>">
                            </div>
                        </div>

                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Date of Birth <span style="color:red">*</span></label>
                                <input type="date" value="<?= htmlspecialchars($studentData['dob']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Course Applying For <span style="color:red">*</span></label>
                                <input type="text" value="<?= htmlspecialchars($courseAppliedFor ?? '') ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Course Type</label>
                                <input type="text" value="<?= htmlspecialchars($studentData['course_type'] ?? '') ?>"
                                    readonly>
                            </div>
                        </div>

                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Category <span style="color:red">*</span></label>
                                <?php
                                $catName = '';
                                foreach ($categories as $c)
                                    if ($c['sno'] == $studentData['category'])
                                        $catName = $c['name'];
                                ?>
                                <input type="text" value="<?= htmlspecialchars($catName) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Religion <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($religions as $r): ?>
                                        <option value="<?= $r['sno'] ?>" <?= $r['sno'] == $studentData['religion'] ? 'selected' : '' ?>>
                                            <?= $r['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Gender <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($genders as $g): ?>
                                        <option value="<?= $g['sno'] ?>" <?= $g['sno'] == $studentData['gender'] ? 'selected' : '' ?>>
                                            <?= $g['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Domicile <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($domiciles as $d): ?>
                                        <option value="<?= $d['sno'] ?>" <?= $d['sno'] == $studentData['domicile'] ? 'selected' : '' ?>>
                                            <?= $d['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Mother Tongue <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($motherTongues as $m): ?>
                                        <option value="<?= $m['sno'] ?>"
                                            <?= $m['sno'] == $studentData['mother_tongue'] ? 'selected' : '' ?>><?= $m['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Weightage <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($weightages as $w): ?>
                                        <option value="<?= $w['sno'] ?>"
                                            <?= $w['sno'] == ($studentData['weightage'] ?? '') ? 'selected' : '' ?>><?= $w['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row three-col">
                            <div class="form-group">
                                <label>Blood Group <span style="color:red">*</span></label>
                                <select disabled>
                                    <option value="">---Select---</option>
                                    <?php foreach ($bloodGroups as $b): ?>
                                        <option value="<?= $b['sno'] ?>"
                                            <?= $b['sno'] == ($studentData['blood_group'] ?? '') ? 'selected' : '' ?>><?= $b['name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select disabled>
                                    <option value="1" selected>Regular</option>
                                    <option value="2">Distance Learning</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Aadhaar <span style="color:red">*</span></label>
                                <input type="text" value="<?= htmlspecialchars($studentData['aadhaar']) ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="right-column">
                        <div class="img-box" style="height:200px;">
                            <img id="pPhoto"
                                src="<?= !empty($studentData['photo_upload']) ? str_replace('../', '', $studentData['photo_upload']) . '?t=' . time() : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 150 200' width='150' height='200'%3E%3Crect width='150' height='200' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3EStudent Photo%3C/text%3E%3C/svg%3E" ?>"
                                alt="Photo">
                        </div>
                        <div style="text-align:center;">
                            <label for="photo_upload" class="sc-btn-outline"
                                style="cursor:pointer; display:inline-block; font-size:12px; padding:5px 10px;">Select
                                Photo</label>
                            <input type="file" name="photo_upload" id="photo_upload" style="display:none;"
                                onchange="prev(this, 'pPhoto')">
                        </div>

                        <div class="img-box" style="height:80px; margin-top:10px;">
                            <img id="pSign"
                                src="<?= !empty($studentData['signature_upload']) ? str_replace('../', '', $studentData['signature_upload']) . '?t=' . time() : "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 80' width='300' height='80'%3E%3Crect width='300' height='80' fill='%23eee'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='%23aaa' font-family='Arial' font-size='14'%3ESignature%3C/text%3E%3C/svg%3E" ?>"
                                alt="Signature">
                        </div>
                        <div style="text-align:center;">
                            <label for="signature_upload" class="sc-btn-outline"
                                style="cursor:pointer; display:inline-block; font-size:12px; padding:5px 10px;">Select
                                Signature</label>
                            <input type="file" name="signature_upload" id="signature_upload" style="display:none;"
                                onchange="prev(this, 'pSign')">
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px;">
                    <div class="section-title">Contact Details</div>
                    <div class="form-row four-col">
                        <div class="form-group">
                            <label>Student Mobile <span style="color:red">*</span></label>
                            <input type="tel" value="<?= htmlspecialchars($studentData['mobile']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Parents Mobile No. <span style="color:red">*</span></label>
                            <input type="tel" name="parents_mobile"
                                value="<?= htmlspecialchars($studentData['parents_mobile'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>WhatsApp Mobile No. <span style="color:red">*</span></label>
                            <input type="tel" name="whatsapp_mobile"
                                value="<?= htmlspecialchars($studentData['whatsapp_mobile'] ?: $studentData['mobile']) ?>"
                                required>
                        </div>
                        <div class="form-group">
                            <label>E-Mail <span style="color:red">*</span></label>
                            <input type="email" value="<?= htmlspecialchars($studentData['email']) ?>" readonly>
                        </div>
                    </div>

                    <div class="section-title">Permanent Address</div>
                    <div class="form-row four-col">
                        <div class="form-group">
                            <label>House No./Village</label>
                            <input type="text" name="p_house" value="<?= htmlspecialchars($studentData['p_house']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Post</label>
                            <input type="text" name="p_post" value="<?= htmlspecialchars($studentData['p_post']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tehsil</label>
                            <input type="text" name="p_tehsil" value="<?= htmlspecialchars($studentData['p_tehsil']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Thana</label>
                            <input type="text" name="p_thana" value="<?= htmlspecialchars($studentData['p_thana']) ?>">
                        </div>
                    </div>
                    <div class="form-row three-col">
                        <div class="form-group">
                            <label>District</label>
                            <input type="text" name="p_district" value="<?= htmlspecialchars($studentData['p_district']) ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="p_state" value="<?= htmlspecialchars($studentData['p_state']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Pin</label>
                            <input type="text" name="p_pin" value="<?= htmlspecialchars($studentData['p_pin']) ?>">
                        </div>
                    </div>

                    <div class="section-title">
                        Correspondence Address
                        <button type="button" class="sc-btn-outline" style="font-size:12px; padding:4px 10px;"
                            onclick="copyA()">Copy Permanent</button>
                    </div>
                    <div class="form-row four-col">
                        <div class="form-group">
                            <label>House No./Village</label>
                            <input type="text" name="c_house" value="<?= htmlspecialchars($studentData['c_house']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Post</label>
                            <input type="text" name="c_post" value="<?= htmlspecialchars($studentData['c_post']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Tehsil</label>
                            <input type="text" name="c_tehsil" value="<?= htmlspecialchars($studentData['c_tehsil']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Thana</label>
                            <input type="text" name="c_thana" value="<?= htmlspecialchars($studentData['c_thana']) ?>">
                        </div>
                    </div>
                    <div class="form-row three-col">
                        <div class="form-group">
                            <label>District</label>
                            <input type="text" name="c_district" value="<?= htmlspecialchars($studentData['c_district']) ?>">
                        </div>
                        <div class="form-group">
                            <label>State</label>
                            <input type="text" name="c_state" value="<?= htmlspecialchars($studentData['c_state']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Pin</label>
                            <input type="text" name="c_pin" value="<?= htmlspecialchars($studentData['c_pin']) ?>">
                        </div>
                    </div>

                    <div class="section-title">Document Uploads</div>
                    <div class="form-row three-col">
                        <div class="form-group">
                            <label>Aadhaar Card Image</label>
                            <input type="file" name="aadhar_file" onchange="prev(this, 'pAadhar')">
                            <?php if (!empty($studentData['aadhar_upload'])): ?>
                                <div class="img-box" style="margin-top:10px; height:100px; max-width:100%;"><img
                                        src="<?= str_replace('../', '', $studentData['aadhar_upload']) . '?t=' . time() ?>" id="pAadhar"></div><?php else: ?><img
                                    id="pAadhar" style="display:none; margin-top:10px; max-height:100px;"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>PAN Card Image</label>
                            <input type="file" name="pan_card_file" onchange="prev(this, 'pPan')">
                            <?php if (!empty($studentData['pan_card'])): ?>
                                <div class="img-box" style="margin-top:10px; height:100px; max-width:100%;"><img
                                        src="<?= str_replace('../', '', $studentData['pan_card']) . '?t=' . time() ?>" id="pPan"></div><?php else: ?><img id="pPan"
                                    style="display:none; margin-top:10px; max-height:100px;"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Other Qualification Image</label>
                            <input type="file" name="marksheet_ug_file" onchange="prev(this, 'pUG')">
                            <?php if (!empty($studentData['ug_marksheet'])): ?>
                                <div class="img-box" style="margin-top:10px; height:100px; max-width:100%;"><img
                                        src="<?= str_replace('../', '', $studentData['ug_marksheet']) . '?t=' . time() ?>" id="pUG"></div><?php else: ?><img id="pUG"
                                    style="display:none; margin-top:10px; max-height:100px;"><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label>High School (10th) Marksheet Image</label>
                            <input type="file" name="marksheet_10_file" onchange="prev(this, 'p10')">
                            <?php if (!empty($studentData['result_10'])): ?>
                                <div class="img-box" style="margin-top:10px; height:100px; max-width:100%;"><img
                                        src="<?= str_replace('../', '', $studentData['result_10']) . '?t=' . time() ?>" id="p10"></div><?php else: ?><img id="p10"
                                    style="display:none; margin-top:10px; max-height:100px;"><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label>Intermediate (12th) Marksheet Image</label>
                            <input type="file" name="marksheet_12_file" onchange="prev(this, 'p12')">
                            <?php if (!empty($studentData['result_12'])): ?>
                                <div class="img-box" style="margin-top:10px; height:100px; max-width:100%;"><img
                                        src="<?= str_replace('../', '', $studentData['result_12']) . '?t=' . time() ?>" id="p12"></div><?php else: ?><img id="p12"
                                    style="display:none; margin-top:10px; max-height:100px;"><?php endif; ?>
                        </div>
                    </div>

                    <div class="section-title">Education Details</div>
                    <div style="overflow-x:auto;">
                        <table style="width:100%; border-collapse: collapse; margin-top: 10px;" border="1"
                            bordercolor="#ddd" cellpadding="8">
                            <thead style="background:#f8f9fa;">
                                <tr>
                                    <th>S.NO</th>
                                    <th>EXAMINATION</th>
                                    <th>BOARD</th>
                                    <th>COLLEGE</th>
                                    <th>YEAR</th>
                                    <th>ROLL NO</th>
                                    <th>SELECT</th>
                                    <th>OBT.</th>
                                    <th>TOTAL</th>
                                    <th>Percentage</th>
                                    <th>CGPA</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($qualifications as $i => $q): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][exam_name]"
                                                value="<?= $q['exam_name'] ?>" <?= $i < 2 ? 'readonly' : '' ?>></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][board]" value="<?= $q['board'] ?>">
                                        </td>
                                        <td><input type="text" name="qualification[<?= $i ?>][college_name]"
                                                value="<?= $q['college_name'] ?>"></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][passing_year]"
                                                value="<?= $q['passing_year'] ?>" maxlength="4"></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][roll_no]"
                                                value="<?= $q['roll_no'] ?>"></td>
                                        <td><select>
                                                <option>Percentage</option>
                                            </select></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][marks_obtained]"
                                                value="<?= $q['marks_obtained'] ?>" oninput="calc(<?= $i ?>)" id="obt_<?= $i ?>"></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][total_marks]"
                                                value="<?= $q['total_marks'] ?>" oninput="calc(<?= $i ?>)" id="tot_<?= $i ?>"></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][percentage]"
                                                value="<?= $q['percentage'] ?>" readonly id="per_<?= $i ?>"></td>
                                        <td><input type="text" name="qualification[<?= $i ?>][grade]" value="<?= $q['grade'] ?>">
                                        </td>
                                        <td><select name="qualification[<?= $i ?>][status]">
                                                <option value="Passed">Passed</option>
                                            </select></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                </div>

                <div style="margin-top:30px; text-align:center;">
                    <button type="submit" name="update_student" class="sc-btn" style="width:200px;">Submit</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
    function copyA() {
        ['house', 'post', 'tehsil', 'thana', 'district', 'state', 'pin'].forEach(k => {
            let el_c = document.querySelector('[name="c_' + k + '"]');
            let el_p = document.querySelector('[name="p_' + k + '"]');
            if (el_c && el_p) {
                el_c.value = el_p.value;
            }
        });
    }
    function prev(input, id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                let img = document.getElementById(id);
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function calc(i) {
        const obt = parseFloat(document.getElementById('obt_' + i).value) || 0;
        const tot = parseFloat(document.getElementById('tot_' + i).value) || 0;
        if (tot > 0) document.getElementById('per_' + i).value = ((obt / tot) * 100).toFixed(2);
    }
    <?php if ($update_success): ?>
        alert("Profile Updated Successfully!");
        let f = document.createElement("form");
        f.method = "POST";
        f.action = "student_edit.php";
        let i = document.createElement("input");
        i.type = "hidden";
        i.name = "registration_no";
        i.value = "<?= htmlspecialchars($registration_no) ?>";
        f.appendChild(i);
        document.body.appendChild(f);
        f.submit();
    <?php endif; ?>
</script>

<?php if (function_exists('page_footer'))
    page_footer(); ?>