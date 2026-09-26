<?php
$currentUser = Session::user();
$currentRole = Session::role();
$flashes = Session::getFlashes();

// If logged in, get unread notification count
$unreadCount = 0;
if ($currentUser) {
    try {
        $pdo = Database::getConnection();
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :u_id AND is_read = FALSE");
        $unreadStmt->execute([':u_id' => $currentUser['user_id']]);
        $unreadCount = (int)$unreadStmt->fetchColumn();
    } catch (Exception $e) {
        $unreadCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'BloodLink — Blood Bank & Emergency Coordination') ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Design System -->
    <link href="<?= url('/assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-bloodlink sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= url('/') ?>">
                <span class="brand-icon"><i class="bi bi-droplet-fill"></i></span>
                <span class="brand-text">Blood<span>Link</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/') ?>"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/compatibility') ?>"><i class="bi bi-diagram-3 me-1"></i> Compatibility</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/about') ?>"><i class="bi bi-info-circle me-1"></i> About & Architecture</a>
                    </li>

                    <?php if ($currentRole === 'DONOR'): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-danger" href="<?= url('/donor/dashboard') ?>"><i class="bi bi-speedometer2 me-1"></i> Donor Portal</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/donor/history') ?>"><i class="bi bi-clock-history me-1"></i> My Donations</a>
                        </li>
                    <?php elseif ($currentRole === 'HOSPITAL_STAFF'): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-danger" href="<?= url('/hospital/dashboard') ?>"><i class="bi bi-hospital me-1"></i> Hospital Portal</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/hospital/requests') ?>"><i class="bi bi-card-checklist me-1"></i> Blood Requests</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/hospital/inventory') ?>"><i class="bi bi-box-seam me-1"></i> Stock Viewer</a>
                        </li>
                    <?php elseif ($currentRole === 'ADMIN'): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold text-danger" href="<?= url('/admin/dashboard') ?>"><i class="bi bi-shield-check me-1"></i> Admin Portal</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/admin/inventory') ?>"><i class="bi bi-boxes me-1"></i> FEFO Inventory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/admin/hospitals') ?>"><i class="bi bi-building-check me-1"></i> Hospitals</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-medium" href="<?= url('/admin/reports') ?>"><i class="bi bi-graph-up me-1"></i> DBMS Reports</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if ($currentUser): ?>
                        <!-- Notification Bell -->
                        <li class="nav-item me-lg-2">
                            <?php 
                            $notifUrl = ($currentRole === 'DONOR') ? url('/donor/notifications') : 
                                        (($currentRole === 'HOSPITAL_STAFF') ? url('/hospital/dashboard') : url('/admin/dashboard'));
                            ?>
                            <a class="nav-link position-relative px-2" href="<?= $notifUrl ?>" title="Notifications">
                                <i class="bi bi-bell fs-5"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        <!-- User Profile Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center fw-semibold" href="#" role="button" data-bs-toggle="dropdown">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle me-2">
                                    <?= e($currentRole) ?>
                                </span>
                                <span><?= e($currentUser['username']) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <h6 class="dropdown-header text-uppercase small fw-bold">Signed in as <?= e($currentUser['username']) ?></h6>
                                </li>
                                <?php if ($currentRole === 'DONOR'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/donor/profile') ?>"><i class="bi bi-person me-2"></i> My Profile</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/donor/history') ?>"><i class="bi bi-clock-history me-2"></i> Donation History</a></li>
                                <?php elseif ($currentRole === 'HOSPITAL_STAFF'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/hospital/dashboard') ?>"><i class="bi bi-hospital me-2"></i> <?= e($currentUser['hospital_name'] ?? 'My Hospital') ?></a></li>
                                    <li><a class="dropdown-item" href="<?= url('/hospital/requests/create') ?>"><i class="bi bi-plus-circle me-2"></i> New Blood Request</a></li>
                                <?php elseif ($currentRole === 'ADMIN'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/admin/donations') ?>"><i class="bi bi-heart-pulse me-2"></i> Donations</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/donors') ?>"><i class="bi bi-people me-2"></i> Donors Directory</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/audit-logs') ?>"><i class="bi bi-journal-text me-2"></i> Audit Trail</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/settings') ?>"><i class="bi bi-sliders me-2"></i> System Settings</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold" href="<?= url('/login') ?>"><i class="bi bi-box-arrow-in-right me-1"></i> Sign In</a>
                        </li>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="btn btn-danger btn-sm dropdown-toggle fw-semibold px-3" href="#" role="button" data-bs-toggle="dropdown">
                                Register
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><a class="dropdown-item" href="<?= url('/register/donor') ?>"><i class="bi bi-heart me-2 text-danger"></i> As Blood Donor</a></li>
                                <li><a class="dropdown-item" href="<?= url('/register/hospital') ?>"><i class="bi bi-hospital me-2 text-primary"></i> As Hospital Staff</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Notifications Container -->
    <div class="container mt-3">
        <?php foreach ($flashes as $type => $messages): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?= e($type) ?> alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <div><?= e($msg) ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <!-- Main View Body Content -->
    <main class="container my-4 flex-grow-1">
        <?= $content ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row gy-4 align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2">
                        <span class="brand-icon" style="width:30px; height:30px; font-size:1rem;"><i class="bi bi-droplet-fill"></i></span>
                        <span class="brand-text fs-5">Blood<span>Link</span></span>
                    </div>
                    <p class="small text-muted mb-0">
                        Academic DBMS University Project • Centralized Blood Bank & Emergency Blood Coordination System.
                    </p>
                    <div class="mt-2">
                        <span class="badge bg-secondary-subtle text-secondary border me-1">3NF Normalized Schema</span>
                        <span class="badge bg-secondary-subtle text-secondary border me-1">20 Relational Tables</span>
                        <span class="badge bg-secondary-subtle text-secondary border me-1">ACID Concurrency & FEFO</span>
                    </div>
                </div>
                <div class="col-md-6 text-center text-md-end small">
                    <ul class="list-inline mb-2">
                        <li class="list-inline-item"><a href="<?= url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/compatibility') ?>" class="text-decoration-none text-muted">Blood Compatibility</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/about') ?>" class="text-decoration-none text-muted">Architecture</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/login') ?>" class="text-decoration-none text-muted">Portal Login</a></li>
                    </ul>
                    <p class="text-muted mb-0">Designed & Built for University DBMS Project Presentation & Viva.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
