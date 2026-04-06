<?php
ob_start();
session_start();
require_once __DIR__ . '/script/settings.php';

// Handle AJAX updates
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    if ($_POST['action'] === 'update_toggle') {
        $sno = (int)$_POST['sno'];
        $column = $_POST['column'];
        $value = (int)$_POST['value'];

        if (in_array($column, ['show_regular', 'show_back'])) {
            $stmt = $db->prepare("UPDATE class_detail SET $column = ? WHERE sno = ?");
            $stmt->bind_param("ii", $value, $sno);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => ucfirst(str_replace('show_', '', $column)) . ' status updated'];
            }
            else {
                $response = ['status' => 'error', 'message' => 'Database update failed'];
            }
            $stmt->close();
        }
    }
    elseif ($_POST['action'] === 'update_date') {
        $sno = (int)$_POST['sno'];
        $date = $_POST['date'];

        $stmt = $db->prepare("UPDATE class_detail SET result_declaration_date = ? WHERE sno = ?");
        $stmt->bind_param("si", $date, $sno);
        if ($stmt->execute()) {
            $response = ['status' => 'success', 'message' => 'Declaration date updated'];
        }
        else {
            $response = ['status' => 'error', 'message' => 'Database update failed'];
        }
        $stmt->close();
    }

    echo json_encode($response);
    exit;
}

// Fetch categories for filter
$catResult = $db->query("SELECT DISTINCT category FROM class_detail WHERE category IS NOT NULL AND category != '' ORDER BY category");
$allCategories = [];
while ($c = $catResult->fetch_assoc()) {
    $allCategories[] = $c['category'];
}

// Fetch initial data or filter via standard GET
$searchTerm = $_GET['search'] ?? '';
$courseType = $_GET['type'] ?? '';

$sql = "SELECT sno, group_name, semester, year, category, show_regular, show_back, result_declaration_date FROM class_detail WHERE 1=1";
$params = [];
$types = "";

if ($searchTerm) {
    $sql .= " AND group_name LIKE ?";
    $params[] = "%$searchTerm%";
    $types .= "s";
}

if ($courseType) {
    $sql .= " AND category = ?";
    $params[] = $courseType;
    $types .= "s";
}

$sql .= " ORDER BY group_name ASC, semester ASC";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$coursesResult = $stmt->get_result();
$courses = [];
while ($row = $coursesResult->fetch_assoc()) {
    $courses[] = $row;
}
$stmt->close();

// Call Sidebar and Header (Note: sidebar() starts the HTML/Body)
if (function_exists('sidebar'))
    sidebar($db);
if (function_exists('page_header'))
    page_header();
?>

<link rel="stylesheet" href="../cdn/css/bootstrap.min.css">
<style>
    /* 🔥 FORCING HEADER COLORS Broken by Bootstrap 🔥 */
    header.relative {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
        color: #fff !important;
    }
    header.relative a, header.relative span {
        color: #fff !important;
    }
    .erp-header {
        background-color: #1f3e8f !important;
    }
    .erp-header p, .erp-welcome p {
        color: white !important;
    }

    /* Fix: Remove extra underlines from links (sidebar & content) */
    a {
        text-decoration: none !important;
    }

    /* Page Content Styles */
    :root {
        --primary-blue: #1e3a8a;
        --accent-blue: #3b82f6;
        --header-blue: #306ad3;
        --bg-light: #f4f7f6;
        --border-color: #dee2e6;
        --accent-red: #dc3545;
    }

    .card-box {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        border: 1px solid #eee;
    }

    .card-heading {
        position: relative;
        font-size: 18px;
        font-weight: 700;
        color: var(--header-blue);
        margin-bottom: 25px;
        padding-left: 18px;
        display: flex;
        align-items: center;
    }

    .card-heading::before {
        content: '';
        position: absolute;
        left: 0;
        top: 4px;
        width: 4px;
        height: 20px;
        background: var(--header-blue);
        border-radius: 4px;
    }

    .table thead th {
        background-color: var(--header-blue) !important;
        color: white !important;
        border: none !important;
        font-weight: 600;
        padding: 12px 15px;
        font-size: 0.9rem;
    }

    .table tbody td {
        padding: 12px 15px;
        vertical-align: middle;
        font-size: 0.85rem;
        border-bottom: 1px solid #f0f0f0;
    }

    /* Switch Styling */
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .switch input { opacity: 0; width: 0; height: 0; }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 20px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px; width: 14px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider { background-color: var(--header-blue); }
    input:checked + .slider:before { transform: translateX(20px); }

    .date-input {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 0.8rem;
        width: 130px;
    }

    .btn-save-date {
        background-color: var(--header-blue);
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        transition: 0.3s;
        font-size: 0.8rem;
    }

    .btn-save-date:hover {
        background-color: var(--primary-blue);
        color: white;
    }

    .form-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        font-size: 0.85rem;
    }

    .form-control-sm, .form-select-sm {
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 0.85rem;
    }

    .btn-search {
        background-color: var(--header-blue);
        color: white;
        font-weight: 600;
        border-radius: 8px;
        padding: 8px 25px;
        font-size: 0.85rem;
    }

    .badge-sem {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
</style>

<main class="p-4" x-data="{ }">
    <?php
// Fetch all course names for dropdown (alphabetical)
$courseList = [];

if (!empty($courseType)) {

    $stmtCourse = $db->prepare("SELECT DISTINCT group_name FROM class_detail WHERE category = ? ORDER BY group_name ASC");
    $stmtCourse->bind_param("s", $courseType);
    $stmtCourse->execute();
    $resultCourses = $stmtCourse->get_result();
}
else {
    $resultCourses = $db->query("SELECT DISTINCT group_name FROM class_detail ORDER BY group_name ASC");
}

while ($row = $resultCourses->fetch_assoc()) {
    $courseList[] = $row['group_name'];
}
?>
    <!-- Filter Section -->
    <div class="card-box">
        
        <div class="card-heading">Filter Courses</div>
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Course Name</label>
               <select name="search" class="form-select form-select-sm">
    <option value="">All Courses</option>
    
    <?php foreach ($courseList as $course): ?>
        <option value="<?php echo htmlspecialchars($course); ?>" 
            <?php echo($searchTerm == $course) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($course); ?>
        </option>
    <?php
endforeach; ?>
</select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Exam Category</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $courseType == $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php
endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-search btn-sm w-100">Search</button>
            </div>
            <div class="col-md-2">
                <a href="admin_result_access.php" class="btn btn-light border btn-sm w-100">Reset</a>
            </div>
        </form>
    </div>

    <!-- Course List Section -->
    <div class="card-box">
        <div class="card-heading">Result Control</div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="5%">S.No</th>
                        <th>Course Name</th>
                        <th class="text-center">Semester</th>
                        <th class="text-center">Regular Paper</th>
                        <th class="text-center">Back Paper</th>
                        <th>Declaration Date</th>
                        <th class="text-center">Declaration Date Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No courses found matching your criteria.</td>
                        </tr>
                    <?php
else: ?>
                        <?php
    $i = 1;
    foreach ($courses as $course):
?>
                            <tr id="row-<?php echo $course['sno']; ?>">
                                <td class="text-muted"><?php echo $i++; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($course['group_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($course['category']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-sem"><?php echo htmlspecialchars($course['semester'] ?: $course['year']); ?></span>
                                </td>
                                <td class="text-center">
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" 
                                               data-sno="<?php echo $course['sno']; ?>" 
                                               data-column="show_regular"
                                               <?php echo $course['show_regular'] == 1 ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td class="text-center">
                                    <label class="switch">
                                        <input type="checkbox" class="status-toggle" 
                                               data-sno="<?php echo $course['sno']; ?>" 
                                               data-column="show_back"
                                               <?php echo $course['show_back'] == 1 ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <input type="date" class="date-input" 
                                           id="date-<?php echo $course['sno']; ?>"
                                           value="<?php echo $course['result_declaration_date']; ?>">
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-save-date" data-sno="<?php echo $course['sno']; ?>" title="Save Date">
                                        <i class="fas fa-save me-1"></i> Save
                                    </button>
                                </td>
                            </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Toasts for feedback -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastMessage"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        let toast;
        const toastEl = document.getElementById('liveToast');
        if (typeof bootstrap !== 'undefined') {
            toast = new bootstrap.Toast(toastEl);
        }

        function showToast(message, isError = false) {
            $('#toastMessage').text(message);
            $('#liveToast').removeClass('bg-success bg-danger');
            $('#liveToast').addClass(isError ? 'bg-danger' : 'bg-success');
            if (toast) toast.show();
            else alert(message); 
        }

        $('.status-toggle').on('change', function() {
            const sno = $(this).data('sno');
            const column = $(this).data('column');
            const value = $(this).is(':checked') ? 1 : 0;

            $.ajax({
                url: 'admin_result_access.php',
                method: 'POST',
                data: { action: 'update_toggle', sno: sno, column: column, value: value },
                success: function(response) {
                    if (response.status === 'success') showToast(response.message);
                    else showToast(response.message, true);
                },
                error: function() { showToast('Communication error', true); }
            });
        });

        $('.btn-save-date').on('click', function() {
            const sno = $(this).data('sno');
            const date = $('#date-' + sno).val();

            $.ajax({
                url: 'admin_result_access.php',
                method: 'POST',
                data: { action: 'update_date', sno: sno, date: date },
                success: function(response) {
                    if (response.status === 'success') showToast(response.message);
                    else showToast(response.message, true);
                },
                error: function() { showToast('Communication error', true); }
            });
        });
    });
</script>

<?php
page_footer();
?>
</body>
</html>
