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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status Search - <?php echo htmlspecialchars($college['college_name'] ?? 'College'); ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/uin_form.css">
    <style>
        .search-container {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .search-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #60a5fa 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .search-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }
        
        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .form-control-lg {
            font-size: 0.9rem;
            padding: 8px 12px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(30,58,138,0.25);
            border-color: #3b82f6;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30,58,138,0.4);
        }
        
        .back-btn {
            background: linear-gradient(135deg, #64748b 0%, #94a3b8 100%);
            border: none;
            color: white;
            font-size: 0.9rem;
            padding: 10px 25px;
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
                    <p class="institute-tagline-small mb-0"><?php echo htmlspecialchars($college['tagline'] ?? 'Autonomous Post Graduate College'); ?>   || <?php echo htmlspecialchars($college['naac_text'] ?? 'NAAC Accredited B++'); ?></p>
                </div>
            </div>
        </div>
    </header>

    <main class="main-form-container py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="search-container">
                        <div class="search-header">
                            <h3 class="mb-0">
                                <i class=""></i> Search Application No./Payment Status
                            </h3>
                        </div>
                        

                        <form method="GET" action="payment_status_result.php">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                     Candidate Name <span class="text-danger"></span>
                                </label>
                                <input type="text" class="form-control form-control-lg" name="candidate_name" required 
                                       placeholder="Enter Candidate Name" 
                                       value="<?php echo htmlspecialchars($_GET['candidate_name'] ?? ''); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    Date of Birth <span class="text-danger"></span>
                                </label>
                                <input type="date" class="form-control form-control-lg" name="status_dob" required 
                                       value="<?php echo htmlspecialchars($_GET['status_dob'] ?? ''); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                     Mobile No. <span class="text-danger"></span>
                                </label>
                                <input type="tel" class="form-control form-control-lg" name="mobile" required 
                                       pattern="[0-9]{10}" maxlength="10" placeholder="Enter 10 digit mobile number"
                                       value="<?php echo htmlspecialchars($_GET['mobile'] ?? ''); ?>">
                            </div>

                            <div class="d-flex gap-3">
                                <a href="uin_reg_form.php" class="btn back-btn flex-fill">
                                    <i class="fa-solid fa-arrow-left"></i> Back
                                </a>
                                <button type="submit" class="btn btn-success flex-fill">
                                     Submit
                                </button>
                            </div>
                        </form>
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

