<?php
require_once __DIR__ . '/scripts/settings.php';

if (isset($_REQUEST['student_id'])) {
    $_SESSION['print_student_id'] = (int)$_REQUEST['student_id'];
}

$studentId = (int)($_SESSION['print_student_id'] ?? 0);

if ($studentId <= 0) {
    die('Invalid access. Please submit admission form first.');
}

$printHeader = get_college_settings(2);
if (!$printHeader) {
    // Fallback to id=1 if id=2 is missing, or handle error
    $printHeader = get_college_settings(1);
}

$formData = [];
$stmt = $mysqli->prepare('SELECT * FROM uin_register_student WHERE id = ?');
$stmt->bind_param('i', $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $formData = $result->fetch_assoc();
} else {
    die('Student record not found');
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit']) && $_POST['final_submit'] == '1') {
    if (empty($formData['uin']) || !str_starts_with($formData['uin'], 'RM'))  {
        $year = date('Y');
        $prefix = 'RM' . $year;
        
        $cntRes = $mysqli->query("SELECT COUNT(*) as count FROM uin_register_student WHERE uin LIKE '{$prefix}%'");
        if ($cntRes) {
            $cntRow = $cntRes->fetch_assoc();
            $cntRes->free();
            $count = ($cntRow['count'] ?? 0) + 1;
            $uin = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
            
            $updateStmt = $mysqli->prepare("UPDATE uin_register_student SET uin = ?, status = 'completed' WHERE id = ?");
            $updateStmt->bind_param("si", $uin, $studentId);
            $updateStmt->execute();
            $updateStmt->close();
            
            $formData['uin'] = $uin;
            $formData['status'] = 'completed';
        }
    }
}




$payStmt = $mysqli->prepare("SELECT * FROM uin_student_payment WHERE student_id = ? AND payment_status = 'success' ORDER BY id DESC LIMIT 1");
if ($payStmt) {
    $payStmt->bind_param('i', $studentId);
    $payStmt->execute();
    $payResult = $payStmt->get_result();
    if ($payResult && $payResult->num_rows > 0) {
        $paymentData = $payResult->fetch_assoc();
        $formData['transaction_id'] = $paymentData['transaction_id'] ?? ($paymentData['payment_id'] ?? '');
    }
    $payStmt->close();
}
$courseName = $formData['course_applied_for'] ?? ($formData['course_applying_for'] ?? '');
if (is_numeric($courseName)) {
    $cStmt = $mysqli->prepare('SELECT class_description FROM class_detail WHERE sno = ?');
    if ($cStmt) {
        $cStmt->bind_param('i', $courseName);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        if ($cRes && $cRes->num_rows > 0) {
            $courseRow = $cRes->fetch_assoc();
            $courseName = $courseRow['class_description'] ?? $courseName;
        }
        $cStmt->close();
    }
}
if (!empty($formData['p_state'])) {
    if (is_numeric($formData['p_state'])) {
        $stateName = get_state_name($formData['p_state']) ?: $formData['p_state'];
    } else {
        $stateName = $formData['p_state'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Confirmation Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <style>
      body {
        font-family: "Roboto", sans-serif;
        font-size: .8rem;
      
      }
      h1{
        font-size: 1.8rem !important;
      }
      h2{
        font-size: 1.5rem !important;
      }
      h3{
        font-size: 1.3rem !important;
      }
      h4{
        font-size: 1rem !important;
      }
      p{
        font-size: .8rem !important;
      }
      td{
        font-size: .8rem !important;
        padding: 8px 10px !important;
        vertical-align: middle !important;
      }
      th{
        font-size: .8rem !important;
        padding: 8px 10px !important;
        vertical-align: middle !important;
      }
      
      @media print {
        *{
          margin: 0 !important;
          padding: 0 !important;
          box-sizing: border-box !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
          color-adjust: exact !important;
        }
       body{
        padding:3rem!important;
       }
       
        td{
          padding: 8px !important;
          /* margin: 10px !important; */
        }
        table th[style*="font-size: 0.7rem"] {
          font-size: 0.7rem !important;
          white-space: nowrap !important;
          word-break: keep-all !important;
          overflow: visible !important;
        }
        .print_no{
          display:none !important;
        }
        .testing{
          margin-top: 55px !important;
          /* white-space: nowrap; */
        }
        .testing2{
          display:block !important;
          width:100% !important;
          text-align: center !important;
          /* margin:0 auto !important; */
          margin-left: 200px !important;
          
        }
        .btn-print{
          display: none;
        }
        #overlays{
          width:60%!important;
          top: 40%!important;
          left: 50%!important;
          transform: translate(-50%, -50%)!important;
          z-index: -1 !important;
          pointer-events: none !important;
          position: fixed !important;
          max-width:800px !important;
          opacity: 0.08 !important;
          display: block !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        #overlays2{
          width:30%!important;
          top: 50%!important;
          left: 50%!important;
          transform: translate(-50%, -50%)!important;
          z-index: -1 !important;
          pointer-events: none !important;
          position: fixed !important;
          max-width:800px !important;
          opacity: 0.08 !important;
          display: block !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        .container-fluid.border {
          position: relative !important;
          z-index: 1 !important;
          background: #fff !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        .container-fluid.border img#overlays,
        .container-fluid.border img#overlays2 {
          display: block !important;
          opacity: 0.08 !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        .table-striped > tbody > tr:nth-of-type(odd) {
          background-color: #f8f9fa !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
          color-adjust: exact !important;
        }
        .table-striped > tbody > tr:nth-of-type(even) {
          background-color: transparent !important;
        }
        .table-striped > tbody > tr:nth-of-type(odd) > td,
        .table-striped > tbody > tr:nth-of-type(odd) > th {
          background-color: #f8f9fa !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        table[width="100%"][style*="margin:0px"] h4 {
          color: #1e3a8a !important;
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
        .container-fluid.m-auto.cont {
          position: relative !important;
          z-index: 1 !important;
          page-break-inside: avoid !important;
        }
        .container-fluid.m-auto.cont[style*="page-break-before"] {
          page-break-before: always !important;
          margin-top: 0 !important;
          padding-top: 0 !important;
        }
        table td[scope="row"] b {
          white-space: nowrap !important;
        }
        table th:first-child {
          white-space: nowrap !important;
        }
        table th[style*="white-space: nowrap"] {
          white-space: nowrap !important;
          overflow: visible !important;
          word-break: keep-all !important;
          line-height: 1.2 !important;
          max-width: none !important;
        }
        table th[style*="font-size: 0.7rem"] {
          font-size: 0.7rem !important;
          white-space: nowrap !important;
          word-break: keep-all !important;
          overflow: visible !important;
          max-width: none !important;
        }
        img {
          -webkit-print-color-adjust: exact !important;
          print-color-adjust: exact !important;
        }
		
      }

      @page{
        size: A4;
        margin-inline:0;
        padding: 0;
      }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,700&display=swap"
      rel="stylesheet"
    />
</head>
<body class="w-70 m-auto ">
<script>
history.pushState(null, '', location.href);
window.addEventListener('popstate', function () {
    history.pushState(null, '', location.href);
});
</script>
  
    <div class="" style="display:flex; justify-content: center; gap:10px;">
      <button class="btn btn-secondary btn-print" style="width: 5%;" onclick="print()">Print</button>
      <button class="btn btn-danger btn-print" style="width: 8%;" onclick="window.location.href='index.php'">Go to Home</button>
    </div>

        <?php
        function renderCopy($label, $formData, $courseName) {
            $currentDate   = date('d-m-Y');
            $dobFormatted  = !empty($formData['dob']) ? date('d-m-Y', strtotime($formData['dob'])) : 'N/A';
            $emailMasked   = !empty($formData['email']) ? '******' . substr($formData['email'], -12) : 'N/A';
            $mobileMasked  = !empty($formData['mobile']) ? '*******' . substr($formData['mobile'], -3) : 'N/A';
            
            $photoPath     = $formData['photo_upload'] ?? $formData['photo'] ?? '';
            $signaturePath = $formData['signature_upload'] ?? $formData['signature'] ?? '';
            
            if (!empty($photoPath) && !file_exists($photoPath)) {
                if (file_exists('uploads/' . basename($photoPath))) {
                    $photoPath = 'uploads/' . basename($photoPath);
                } elseif (file_exists('student_images/' . basename($photoPath))) {
                    $photoPath = 'student_images/' . basename($photoPath);
                }
            }
            if (!empty($signaturePath) && !file_exists($signaturePath)) {
                if (file_exists('uploads/' . basename($signaturePath))) {
                    $signaturePath = 'uploads/' . basename($signaturePath);
                } elseif (file_exists('student_images/' . basename($signaturePath))) {
                    $signaturePath = 'student_images/' . basename($signaturePath);
                }
            }
            
            $photoExists = !empty($photoPath) && file_exists($photoPath);
            $signatureExists = !empty($signaturePath) && file_exists($signaturePath);

            // Resolve Category, Gender, Religion
            $categoryName = '';
            if (!empty($formData['category'])) {
                $categoryName = is_numeric($formData['category']) ? (get_category_name($formData['category']) ?: $formData['category']) : $formData['category'];
            }
            $genderName = '';
            if (!empty($formData['gender'])) {
                $genderName = is_numeric($formData['gender']) ? (get_gender_name($formData['gender']) ?: $formData['gender']) : $formData['gender'];
            }
            $religionName = '';
            if (!empty($formData['religion'])) {
                $religionName = is_numeric($formData['religion']) ? (get_religion_name($formData['religion']) ?: $formData['religion']) : $formData['religion'];
            }
        ?>
        <div class="container-fluid m-auto cont col-md-6" style="page-break-after: always;">
            <?php print_header($label); ?>
        <br>
        <!--  -->
        <div class="table-responsive m-2">
          <table  width="100%" class="table table-striped table-hover rounded">
        
              <tr>
                <th scope="col" width="18%">UIN NO.:</th>
                <th scope="col" width="18%" style="white-space: nowrap;">
                    <?php echo htmlspecialchars((!empty($formData['uin']) && str_starts_with($formData['uin'], 'RM')) ? $formData['uin'] : ''); ?>
                </th>
                <th scope="col" width="16%" style="white-space: nowrap;"></th>
                <th scope="col" style="white-space: nowrap; font-size: 0.7rem; padding: 4px 2px !important;"><?php echo htmlspecialchars($formData['transaction_id'] ?? ''); ?></th>
				<th rowspan="3" width="20%">
                    <?php if ($photoExists): ?>
                        <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                             alt="Person Pic"
                             class="img-fluid"
                             style="
                                width:132px;
                                height:125px;
                                object-fit: contain;
                                border: 1px solid #000;
                                float: right;
                                margin-right: 10px;
                                background: #fff;
                             ">
                    <?php else: ?>
                        <div style="
                            width:132px;
                            height:125px;
                            border:1px solid #ccc;
                            float:right;
                            margin-right:10px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                        ">
                            Photo
                        </div>
                    <?php endif; ?>
                </th>

              </tr>
              <tr>
                <td scope="row"><b>COURSE NAME:</b></td>
                <td colspan="3"><?php echo htmlspecialchars($courseName); ?></td>
              </tr>
              <tr>
                <th scope="row">APPLICANT NAME</th>
                <td ><?php echo htmlspecialchars($formData['candidate_name'] ?? ''); ?></td>
				<td scope="row"><b>DATE OF BIRTH</b></td>
                <td><?php echo htmlspecialchars($dobFormatted); ?></td>
              </tr>
              <tr>
                <th scope="row">FATHER'S NAME</th>
                <td colspan="2" ><?php echo htmlspecialchars($formData['fathers_name'] ?? ''); ?></td>
                <th>Gender</th>
                <td colspan="2"> <?php echo htmlspecialchars($genderName ?: 'N/A'); ?></td>
                
              </tr>
                <tr>
				<th scope="row">MOTHER'S NAME</th>
                <td colspan="2"><?php echo htmlspecialchars($formData['mothers_name'] ?? ''); ?></td>
                <th>RELIGION : </th>
                <td ><?php echo htmlspecialchars($religionName ?: 'N/A'); ?></td>
              </tr>
              <tr>
				<th scope="row">EMAIL</th>
				<td colspan="2"><?php echo htmlspecialchars($emailMasked); ?></td>
				<th>MOBILE :</th>
				<td ><?php echo htmlspecialchars($mobileMasked); ?></td>
			</tr>

			  <tr>
                <th >CATEGORY :</th>
				<td colspan="2"> <?php echo htmlspecialchars($categoryName ?: 'N/A'); ?></td>
                 <th></th>
                 <th></th>
			  </tr>
            
           
          </table>
        </div>
	<table  width="100% " style="margin:10px">
		<tr >
			<td>
			  <br>
			  <h4 align="center"><b>DECLARATION</b></h4>
			  
			  <p>I, <strong><?php echo htmlspecialchars($formData['candidate_name'] ?? ''); ?></strong> HEREBY DECLARE THAT ALL STATEMENTS MADE IN THIS APPLICATION ARE TRUE AND CORRECT TO THE BEST OF MY KNOWLEDGE AND BELIEF. IN CASE IF ANY INFORMATION IS BEING FOUND FALSE OR INCORRECT OR ANY IRREGULARITY IS BEING DETECTED ANY STAGE, MY CANDIDATURE IS LIABLE TO BE CANCELLED AND ACTION MAY BE INITIATED AGAINST ME. I UNDERSTAND THAT, IF MY APPLICATION IS FOUND INCOMPLETE IN ANY MANNER, THE SAME WILL BE REJECTED.</p>
			</td>

			
		</tr>
		<tr>
			<th style="text-align:right; margi-right:10px;">
                <?php if ($signatureExists): ?>
                    <img src="<?php echo htmlspecialchars($signaturePath); ?>" alt="signature" class="img-fluid" style="width: 150px; height: 60px; object-fit: contain; border: 1px solid #ccc;"><br>
                <?php else: ?>
                    <div style="width:150px; height:60px; border:1px solid #ccc; display:inline-block;"></div><br>
                <?php endif; ?>
                SIGNATURE OF APPLICANT
            </th>
		</tr>
		<tr>
			<th>PRINTED ON: <?php echo htmlspecialchars($currentDate); ?></th>
		</tr>
		<tr>
			<td><br><h4 align="center"><b>DISCLAIMER</b></h4>
		
		<h6>It is mandatory to submit UIN registration along with admission fee receipt (Duplicate Copy) in the Account Section.</h6></td>
		</tr>
	</table >
    </div>
    <?php
    }
    renderCopy('COLLEGE COPY', $formData, $courseName);
    renderCopy('STUDENT COPY', $formData, $courseName);
    ?>
</body>
</html>

