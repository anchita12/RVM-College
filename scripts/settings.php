<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['uin_form_data'])) {
    $_SESSION['uin_form_data'] = [];
}
require_once __DIR__ . '/settings_dbase.php';


function get_college_settings($id = 1) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("
        SELECT 
            id, short_name, college_name, tagline, naac_text, 
            affiliated_text, ugc_text, iso_text, established, 
            email, phone, logo, background_image 
        FROM college_settings 
        WHERE id = ?
        LIMIT 1
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $settings = $result->fetch_assoc();
    $stmt->close();
    
    return $settings;
}

function get_dropdown_options($table_name, $sno_column = null, $name_column = null) {
    global $mysqli;
    
    if ($sno_column === null) {
        $sno_column = strtolower($table_name) . '_sno';
    }
    if ($name_column === null) {
        if ($table_name === 'categories') {
            $name_column = 'category_name';
        } else {
            $name_column = strtolower($table_name) . '_name';
        }
    }
    
    $query = "SELECT `$sno_column` as sno, `$name_column` as name FROM `$table_name` WHERE 1 ORDER BY `$name_column`";
    $result = $mysqli->query($query);
    
    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = [
                'sno' => $row['sno'],
                'name' => $row['name']
            ];
        }
    }
    
    return $options;
}

function get_course_types() {
    global $mysqli;
    
    $query = "SELECT DISTINCT `type` FROM `class_detail` WHERE 1 ORDER BY `type`";
    $result = $mysqli->query($query);
    
    $types = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $types[] = $row['type'];
        }
    }
    
    return $types;
}

function get_courses_by_type($course_type) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("
        SELECT DISTINCT group_name AS course_name
        FROM class_detail
        WHERE category = ?
        ORDER BY group_name
    ");
    
    $stmt->bind_param("s", $course_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row['course_name'];
    }
    
    $stmt->close();
    return $courses;
}

function get_states() {
    global $mysqli;
    
    $table_check = $mysqli->query("SHOW TABLES LIKE 'states'");
    
    if ($table_check && $table_check->num_rows > 0) {
        return get_dropdown_options('states', 'state_sno', 'state_name');
    } else {
        return [
            ['sno' => 1, 'name' => 'Uttar Pradesh'],
            ['sno' => 2, 'name' => 'Bihar'],
            ['sno' => 3, 'name' => 'Delhi'],
            ['sno' => 4, 'name' => 'Punjab'],
            ['sno' => 5, 'name' => 'Haryana'],
            ['sno' => 6, 'name' => 'Rajasthan'],
            ['sno' => 7, 'name' => 'Madhya Pradesh'],
            ['sno' => 8, 'name' => 'West Bengal'],
            ['sno' => 9, 'name' => 'Odisha'],
            ['sno' => 10, 'name' => 'Maharashtra'],
            ['sno' => 11, 'name' => 'Gujarat']
        ];
    }
}

function get_religions() {
    return get_dropdown_options('religions', 'religion_sno', 'religion_name');
}

function get_genders() {
    return get_dropdown_options('genders', 'gender_sno', 'gender_name');
}

function get_blood_groups() {
    return get_dropdown_options('blood_groups', 'blood_group_sno', 'blood_group_name');
}

function get_mother_tongues() {
    return get_dropdown_options('mother_tongues', 'language_sno', 'language_name');
}

function get_weightages() {
    return get_dropdown_options('weightages', 'weightage_sno', 'weightage_name');
}

function get_student_statuses() {
    return get_dropdown_options('student_statuses', 'status_sno', 'status_name');
}

function get_domiciles() {
    return get_states();
}

function get_exam_names() {
    return get_dropdown_options('exam_names', 'exam_sno', 'exam_name');
}

function get_exam_statuses() {
    return get_dropdown_options('exam_statuses', 'exam_status_sno', 'exam_status_name');
}

function get_name_from_sno($table, $sno, $sno_column = null, $name_column = null) {
    global $mysqli;
    
    if ($sno_column === null) {
        $sno_column = strtolower($table) . '_sno';
    }
    if ($name_column === null) {
        if ($table === 'categories') {
            $name_column = 'category_name';
        } else {
            $name_column = strtolower($table) . '_name';
        }
    }
    
    $stmt = $mysqli->prepare("SELECT `$name_column` as name FROM `$table` WHERE `$sno_column` = ? LIMIT 1");
    $stmt->bind_param("i", $sno);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? $row['name'] : '';
}

function get_category_name($sno) {
    return get_name_from_sno('categories', $sno, 'categories_sno', 'category_name');
}

function get_state_name($sno) {
    if (empty($sno)) return '';
    $states = get_states();
    foreach ($states as $state) {
        if ($state['sno'] == $sno) {
            return $state['name'];
        }
    }
    return '';
}

function get_religion_name($sno) {
    return get_name_from_sno('religions', $sno, 'religion_sno', 'religion_name');
}

function get_gender_name($sno) {
    return get_name_from_sno('genders', $sno, 'gender_sno', 'gender_name');
}

function get_mother_tongue_name($sno) {
    return get_name_from_sno('mother_tongues', $sno, 'language_sno', 'language_name');
}

function get_blood_group_name($sno) {
    return get_name_from_sno('blood_groups', $sno, 'blood_group_sno', 'blood_group_name');
}

function get_weightage_name($sno) {
    return get_name_from_sno('weightages', $sno, 'weightage_sno', 'weightage_name');
}

function get_student_status_name($sno) {
    return get_name_from_sno('student_statuses', $sno, 'status_sno', 'status_name');
}

// Fetch settings fresh from database (no caching for dynamic updates)
$COLLEGE_SETTINGS = get_college_settings();

function page_header($page_title = '', $include_bootstrap = true) {
    global $COLLEGE_SETTINGS;
    
    if (!$COLLEGE_SETTINGS) {
        echo "<div style='color: red; padding: 20px;'>Error: College settings not found in database</div>";
        return;
    }
    
    $college_name = $COLLEGE_SETTINGS['college_name'] ?? 'College Name';
    $short_name = $COLLEGE_SETTINGS['short_name'] ?? '';
    $tagline = $COLLEGE_SETTINGS['tagline'] ?? '';
    $naac_text = $COLLEGE_SETTINGS['naac_text'] ?? '';
    $affiliated_text = $COLLEGE_SETTINGS['affiliated_text'] ?? '';
    $ugc_text = $COLLEGE_SETTINGS['ugc_text'] ?? '';
    $iso_text = $COLLEGE_SETTINGS['iso_text'] ?? '';
    $established = $COLLEGE_SETTINGS['established'] ?? '';
    $email = $COLLEGE_SETTINGS['email'] ?? '';
    $phone = $COLLEGE_SETTINGS['phone'] ?? '';
    $logo = $COLLEGE_SETTINGS['logo'] ?? '';
    $background_image = $COLLEGE_SETTINGS['background_image'] ?? '';
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page_title ? $page_title . ' - ' . $short_name : $college_name); ?></title>
        
        <?php if ($include_bootstrap): ?>
        <?php endif; ?>
        
        <link rel="stylesheet" href="assets/global.css">
        <link rel="stylesheet" href="assets/header.css">
    </head>
    <body>
        <header class="college-header">
            <div class="container">
                <div class="college-header-content">
                    <?php if ($logo): ?>
                    <div class="college-logo-container">
                        <img src="<?php echo htmlspecialchars($logo); ?>" alt="College Logo" class="college-logo">
                    </div>
                    <?php endif; ?>
                    
                    <div class="college-info">
                        <h1 class="college-name"><?php echo htmlspecialchars($college_name); ?></h1>
                        
                        <?php if ($tagline): ?>
                        <p class="college-tagline"><?php echo htmlspecialchars($tagline); ?></p>
                        <?php endif; ?>
                        
                        <div class="college-badges">
                            <?php if ($naac_text): ?>
                            <span class="college-badge"><?php echo htmlspecialchars($naac_text); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($affiliated_text): ?>
                            <span class="college-badge"><?php echo htmlspecialchars($affiliated_text); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($ugc_text): ?>
                            <span class="college-badge"><?php echo htmlspecialchars($ugc_text); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($iso_text): ?>
                            <span class="college-badge"><?php echo htmlspecialchars($iso_text); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($established): ?>
                            <span class="college-badge">Est. <?php echo htmlspecialchars($established); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="college-contact">
                        <?php if ($email): ?>
                        <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($email); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($phone): ?>
                        <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($phone); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        
        <main class="container">
    <?php
}

function print_header($label = '') {
    global $mysqli;
    
    // Fetch settings from id=1 (single source of truth)
    $stmt = $mysqli->prepare("SELECT * FROM college_settings WHERE id = 1 LIMIT 1");
    $stmt->execute();
    $settings = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$settings) return;

    $college_name = $settings['college_name'] ?? '';
    $tagline = $settings['tagline'] ?? '';
    
    // STRICT: Use ONLY p_logo column (no fallback to logo column)
    $logo = $settings['p_logo'] ?? '';
    
    // STRICT: Use ONLY p_background column (no fallback to background_image column)
    $background = $settings['p_background'] ?? '';

    // Watermark uses p_background if available, otherwise no watermark
    $watermark = !empty($background) ? $background : '';

    ?>
    <div class="container-fluid border" style="position: relative;">
        <!-- Watermark Overlay (only if p_background is set) -->
        <?php if (!empty($watermark)): ?>
        <img src="<?php echo htmlspecialchars($watermark); ?>" 
             id="overlays" 
             style="z-index:-1; opacity:0.08; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width:30%; max-width:700px; pointer-events: none;" 
             alt="watermark" >
        <?php endif; ?>
        
        <table width="100%" style="margin:0px; position: relative; z-index: 1;">
            <tr>
                <th width="20%" rowspan="2">
                    <?php if (!empty($logo)): ?>
                        <img style="padding:15px; height:85px; width:85px;" 
                             src="<?php echo htmlspecialchars($logo); ?>" 
                             alt="logo" class="img-fluid d-block m-auto" /> 
                    <?php else: ?>
                        <div style="padding:15px; height:65px; width:65px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; font-size:10px; color:#999; margin:auto;">No Logo</div>
                    <?php endif; ?>
                </th>
                <th width="80%">
                    <h4 style="text-align: center; margin:0px; color:#1e3a8a;">
                        <span style="font-size:17px;"><b><?php echo htmlspecialchars($college_name); ?></b></span>
                        <br><?php echo htmlspecialchars($tagline); ?>
                    </h4>
                </th>
            </tr>
        </table>

        <?php if (!empty($label)): ?>
        <div class="row">
            <div class="container d-flex justify-content-center">
                <p style="text-decoration: underline; text-align:center; color:#1e3a8a; font-weight: bold;">
                    UNIQUE IDENTIFICATION NUMBER REGISTRATION - <?php echo date('Y'); ?> (<?php echo htmlspecialchars($label); ?>)
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

function page_footer() {
    global $COLLEGE_SETTINGS;
    $college_name = $COLLEGE_SETTINGS['college_name'] ?? 'College';
    $short_name = $COLLEGE_SETTINGS['short_name'] ?? '';
    ?>
        </main>
        
        <footer class="mt-5 py-4 bg-light">
            <div class="container text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($short_name ?: $college_name); ?>. All rights reserved.</p>
            </div>
        </footer>
      
        
    </body>
    </html>
    <?php
}
