<?php
require_once __DIR__ . '/scripts/settings.php';
$college = get_college_settings(1);
$announcements = [];
if ($result = $mysqli->query('SELECT * FROM announcements WHERE is_active = 1 ORDER BY date DESC, id DESC LIMIT 20')) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    $result->free();
}

$studentCorner = [];
if ($result = $mysqli->query('SELECT * FROM student_corner WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')) {
    while ($row = $result->fetch_assoc()) {
        $studentCorner[] = $row;
    }
    $result->free();
}
$helpdesk = [];
if ($result = $mysqli->query('SHOW TABLES LIKE "helpdesk"')) {
    if ($result->num_rows > 0) {
        $result->free();
        if ($rs = $mysqli->query('SELECT * FROM helpdesk ORDER BY sort_order ASC, id ASC')) {
            while ($row = $rs->fetch_assoc()) {
                $helpdesk[] = $row;
            }
            $rs->free();
        }
    } else {
        $result->free();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($college['college_name'] ?? 'College Website'); ?></title>
    <link rel="stylesheet" href="cdn/css/bootstrap.min.css">
<link rel="stylesheet" href="cdn/css/all.min.css">
<link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            background-image: url('<?php echo htmlspecialchars($college['background_image'] ?? 'images/hii.jpeg'); ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(1px);
            z-index: -1;
        }
        
        /* Helpdesk Card Hover Animation */
        .helpdesk-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .helpdesk-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3) !important;
            border: 2px solid #f97316 !important;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.05) 0%, rgba(251, 146, 60, 0.02) 100%) !important;
        }
        
        .helpdesk-card:hover .helpdesk-label {
            color: #f97316 !important;
            font-weight: 700;
        }
        
        .helpdesk-card:hover .helpdesk-value {
            color: #f97316 !important;
        }
        
        /* Touch/Active state for helpdesk cards */
        .helpdesk-card:active {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(249, 115, 22, 0.4) !important;
            border: 2px solid #f97316 !important;
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1) 0%, rgba(251, 146, 60, 0.05) 100%) !important;
        }
    </style>
</head>
<body>
<header class="top-bar py-2">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="top-bar-text " style="font-size: 1.0rem;padding:10px;">
            <span>रघुवीर महाविद्यालय </span>
        </div>
        <div>
            <a href="admin/" class="admin-login-link btn btn-warning">Admin Login</a>
        </div>
    </div>
</header>

<section class="institute-header py-1">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-auto">
                <?php if (!empty($college['logo']) && file_exists($college['logo'])): ?>
                    <img src="<?php echo htmlspecialchars($college['logo']); ?>" alt="Logo" class="institute-logo">
                <?php else: ?>
                    <!-- <div class="institute-logo-placeholder">
                        <i class="fa-solid fa-graduation-cap fa-3x"></i>
                    </div> -->
                <?php endif; ?>
            </div>
            <div class="col text-center">
                <h1 class="institute-name mb-2">
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($college['college_name'] ?? 'Raghuveer Mahavidyalaya'); ?></span>
                    <span class="d-inline d-md-none"><?php echo htmlspecialchars($college['short_name'] ?? $college['college_name'] ?? 'Raghuveer Mahavidyalaya'); ?></span>
                </h1>
                <p class="institute-tagline mb-1"><?php echo htmlspecialchars($college['tagline'] ?? 'Autonomous Post Graduate College'); ?>|| <?php echo htmlspecialchars($college['naac_text'] ?? 'NAAC Accredited'); ?></p>
                <?php if (!empty($college['affiliated_text'])): ?>
                    <p class="institute-affiliated mb-1"><?php echo htmlspecialchars($college['affiliated_text']); ?> || <?php echo htmlspecialchars($college['ugc_text']); ?></p>
                <?php endif; ?>
                <?php if (!empty($college['iso_text'])): ?>
                    <p class="institute-affiliated mb-1 small"><?php echo htmlspecialchars($college['iso_text']); ?> || <?php echo htmlspecialchars($college['established']); ?></p>
                <?php endif; ?>
            </div>
            <!-- <div class="col-auto">
                <div class="naac-badge">
                    <div class="naac-text"></div>
                </div>
                <?php if (!empty($college['email'])): ?>
                    <div class="contact-info mt-2">
                        <small class="text-muted d-block">
                            <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($college['email']); ?>
                        </small>
                    </div>
                <?php endif; ?>
                <?php if (!empty($college['phone'])): ?>
                    <div class="contact-info">
                        <small class="text-muted d-block">
                            <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($college['phone']); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div> -->
        </div>
    </div>
</section>

<main id="home" class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 col-xl-6 col-lg-6">
                <div class="announcement-section">
                    <div class="section-header">
                        <i class="fa-solid fa-bullhorn me-2"></i>
                        <h2 class="section-title-inline">Announcement / Examination</h2>
                    </div>
                    <div class="announcement-list">
                        <?php if (empty($announcements)): ?>
                            <p class="text-muted text-center py-4">No announcements available.</p>
                        <?php else: ?>
                            <?php foreach ($announcements as $notice): ?>
                                <?php 
                                // Get all values safely, allow empty values
                                $title = isset($notice['title']) ? trim((string)$notice['title']) : '';
                                $date = isset($notice['date']) ? trim((string)$notice['date']) : '';
                                $link = isset($notice['link']) ? trim((string)$notice['link']) : '';
                                $isNew = !empty($notice['is_new']) && $notice['is_new'] != '0';
                                ?>
                                <div class="announcement-item">
                                    <div class="announcement-date">
                                        <?php 
                                        if (!empty($date)) {
                                            $dateObj = DateTime::createFromFormat('Y-m-d', $date);
                                            if ($dateObj) {
                                                echo $dateObj->format('d-m-Y');
                                            } else {
                                                echo htmlspecialchars($date);
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                    <div class="announcement-content">
                                        <div class="announcement-title-row">
                                            <span class="announcement-title"><?php echo !empty($title) ? htmlspecialchars($title) : '-'; ?></span>
                                            <?php if ($isNew): ?>
                                                <span class="new-badge">NEW</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($link)): ?>
                                            <div class="announcement-link">
                                                <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="download-link">
                                                    <i class="fa-solid fa-download"></i>
                                                </a>
                                                <span class="link-text">(Link)</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

           
            <div class="col-12 col-xl-6 col-lg-6">
                <div class="student-corner-section">
                    <div class="section-header">
                        <i class="fa-solid fa-user-graduate me-2"></i>
                        <h2 class="section-title-inline">Student Corner</h2>
                    </div>
                    <div class="student-corner-grid" style="grid-template-columns: repeat(3, 1fr) !important;">
                        <?php if (empty($studentCorner)): ?>
                            <p class="text-muted text-center py-4">No items found.</p>
                        <?php else: ?>
                            <?php 
                            $colorClasses = ['card-red', 'card-green', 'card-purple', 'card-pink', 'card-teal', 'card-blue', 'card-orange', 'card-indigo'];
                            $colorIndex = 0;
                            foreach ($studentCorner as $item): 
                                $colorClass = $colorClasses[$colorIndex % count($colorClasses)];
                                $colorIndex++;
                            ?>
                                <?php 
                                $linkUrl = htmlspecialchars($item['link']);
                                $isExternal = filter_var($linkUrl, FILTER_VALIDATE_URL) || strpos($linkUrl, 'http') === 0;
                                $targetAttr = $isExternal ? 'target="_blank"' : '';
                                ?>
                                <a href="<?php echo $linkUrl; ?>" 
                                   <?php echo $targetAttr; ?>
                                   class="student-corner-card <?php echo $colorClass; ?>">
                                    <div class="card-icon-wrapper">
                                        <?php
                                        $iconClass = $item['icon'];
                                        $isFontAwesome = str_starts_with($iconClass, 'fa');
                                        ?>
                                        <?php if ($isFontAwesome): ?>
                                            <i class="<?php echo htmlspecialchars($iconClass); ?>"></i>
                                        <?php elseif (!empty($iconClass) && file_exists($iconClass)): ?>
                                            <img src="<?php echo htmlspecialchars($iconClass); ?>" alt="" class="card-icon-img">
                                        <?php else: ?>
                                            <i class="fa-solid fa-circle-question"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                    <?php 
                                    $isNewRaw = trim((string)($item['is_new'] ?? ''));
                                    if ($isNewRaw !== '' && $isNewRaw !== '0'):
                                        $badgePath = parse_url($isNewRaw, PHP_URL_PATH) ?: $isNewRaw;
                                        $badgeExt = strtolower(pathinfo($badgePath, PATHINFO_EXTENSION));
                                        $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
                                        $isImageBadge = $badgeExt && in_array($badgeExt, $imageExtensions, true);
                                        if ($isImageBadge):
                                            $badgeSrc = $isNewRaw;
                                            if (!filter_var($badgeSrc, FILTER_VALIDATE_URL)) {
                                                $badgeSrc = '/' . ltrim($badgeSrc, '/');
                                            }
                                    ?>
                                            <img src="<?php echo htmlspecialchars($badgeSrc); ?>" alt="New" class="card-new-image">
                                    <?php else: ?>
                                            
                                    <?php 
                                        endif;
                                    endif;
                                    ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($helpdesk)): ?>
        <div class="container-fluid helpdesk-container" style="margin-top: 20px;">
            <div class="helpdesk-section">
                <div class="section-header">
                    <i class="fa-solid fa-headset me-2"></i>
                    <h2 class="section-title-inline mb-0">Helpdesk</h2>
                </div>
                <div class="row g-1 g-md-3 mt-1 px-1 px-md-3 pb-1 pb-md-3">
                    <?php foreach ($helpdesk as $info): ?>
                        <div class="col-6 col-md-6 col-lg-3">
                            <div class="card h-100 shadow-sm helpdesk-card">
                                <div class="card-body text-center">
                                    <div class="helpdesk-label text-uppercase small text-muted mb-2">
                                        <?php echo htmlspecialchars($info['label']); ?>
                                    </div>
                                    <div class="helpdesk-value fw-semibold">
                                        <?php echo nl2br(htmlspecialchars(trim((string)($info['value'] ?? '')))); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<footer class="py-3 text-bg-dark">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center small">
        <span>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($college['college_name'] ?? 'College'); ?>. All rights reserved.</span>
        <span>Developed by Weknow Technologies</span>
    </div>
</footer>

<script src="cdn/js/bootstrap.bundle.min.js"></script>
<script>
    // Set announcement section height only (student corner will be auto)
    document.addEventListener('DOMContentLoaded', function() {
        function setAnnouncementHeight() {
            const announcementSection = document.querySelector('.announcement-section');
            
            if (announcementSection) {
                // Keep announcement section at its CSS defined height
                // Don't force student corner to match
            }
        }
        
        setAnnouncementHeight();
        window.addEventListener('resize', setAnnouncementHeight);
        setTimeout(setAnnouncementHeight, 100);
    });
</script>
</body>
</html>


