<?php
// admin/export_uin_students.php
ob_start();
session_start();
include("script/settings.php");

// Set headers to force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=uin_students_mapped_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// --- PRELOAD MAPS (ID -> Name) ---
$gender_names = [];
$g_res = $db->query("SELECT * FROM genders");
if($g_res){
    while($r = $g_res->fetch_assoc()){
        $gender_names[$r['gender_sno']] = strtoupper($r['gender_name']);
    }
}

$cat_names = [];
$c_res = $db->query("SELECT * FROM categories");
if($c_res){
    while($r = $c_res->fetch_assoc()){
        $cat_names[$r['categories_sno']] = strtoupper($r['category_name']);
    }
}

// 1. Fetch Target Columns (from student_info)
$target_columns = [];
$res_cols = $db->query("SHOW COLUMNS FROM student_info");
if($res_cols){
    while($col = $res_cols->fetch_assoc()){
        $target_columns[] = $col['Field'];
    }
}

// Write Header Row (Exact student_info columns)
fputcsv($output, $target_columns);

// 2. Fetch Source Data (from uin_register_student)
$sql = "SELECT urs.*, cd.sort_no, cd.class_description 
        FROM uin_register_student urs 
        LEFT JOIN class_detail cd ON urs.course_applied_for = cd.sno 
        ORDER BY cd.sort_no ASC, urs.candidate_name ASC";

$res = $db->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $csv_row = [];
        
        foreach ($target_columns as $col) {
            $val = ''; 
            
            switch ($col) {
                case 'stu_name': $val = $row['candidate_name'] ?? ''; break;
                case 'father_name': $val = $row['fathers_name'] ?? ''; break;
                case 'mother_name': $val = $row['mothers_name'] ?? ''; break;
                case 'uin': $val = $row['uin'] ?? ''; break;
                case 'form_no': $val = $row['registration_no'] ?? ''; break;
                case 'class': 
                    // User wants Name not ID
                    $val = $row['class_description'] ?? ($row['course_applied_for'] ?? ''); 
                    break;
                
                case 'dob': $val = $row['dob'] ?? ''; break;

                case 'gender':
                    // User wants TEXT (Mail/Female).
                    // UIN might have "Male", "M", "1".
                    $raw = strtoupper(trim($row['gender'] ?? ''));
                    
                    // If it's ID, convert to Name
                    if(isset($gender_names[$raw])) {
                        $val = $gender_names[$raw]; // 1 -> MALE
                    } else {
                        // If it's already text like "MALE", keep it.
                        // If "M", convert to "MALE".
                        if($raw == 'M') $val = 'MALE';
                        else if($raw == 'F') $val = 'FEMALE';
                        else $val = $raw; // Fallback
                    }
                    break;
                    
                case 'category':
                    // User wants TEXT.
                    $raw = strtoupper(trim($row['category'] ?? ''));
                    if(isset($cat_names[$raw])) {
                         $val = $cat_names[$raw];
                    } else {
                         // Fallback (already text)
                         $val = $raw;
                    }
                    break;
                    
                case 'p_mobile': $val = $row['parents_mobile'] ?? ($row['mobile'] ?? ''); break;
                case 'email': $val = $row['email'] ?? ''; break;
                case 'aadhaar': $val = $row['aadhaar'] ?? ''; break;
                
                case 'p_house': $val = $row['p_house'] ?? ''; break;
                case 'p_district': $val = $row['p_district'] ?? ''; break;
                case 'p_state': $val = $row['p_state'] ?? ''; break;
                case 'p_pin': $val = $row['p_pin'] ?? ''; break;
                
                case 'photo_id':
                    $p = $row['photo_upload'] ?? '';
                    $val = str_replace('../', '', $p);
                    break;
                    
                case 'signature_id':
                    $s = $row['signature_upload'] ?? '';
                    $val = str_replace('../', '', $s);
                    break;
                
                default:
                    if (isset($row[$col])) $val = $row[$col];
                    break;
            }
            
            $csv_row[] = $val;
        }
        
        fputcsv($output, $csv_row);
    }
}

fclose($output);
exit;
?>
