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
    <nav class="navbar navbar-expand-lg navbar-bloodlink sticky-top" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= url('/') ?>">
                <span class="brand-icon"><i class="bi bi-droplet-fill"></i></span>
                <span class="brand-text">Blood<span>Link</span></span>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav navbar-common mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/') ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/compatibility') ?>">Compatibility</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/about') ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-medium" href="<?= url('/donation-events') ?>">Donation Events</a>
                    </li>
                </ul>

                

                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if ($currentUser): ?>

                        <!-- Notification Bell -->
                        <li class="nav-item me-lg-2">
                            <a class="nav-link position-relative px-2" href="<?= url('/notifications') ?>" title="Notifications" aria-label="Notifications">
                                <i class="bi bi-bell fs-5"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $unreadCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>

                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-grid me-1"></i> Menu
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <h6 class="dropdown-header text-uppercase small fw-bold">Signed in as <?= e($currentUser['username']) ?></h6>
                                </li>
                                <?php if ($currentRole === 'DONOR'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/donor/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i> Donor Portal</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/donor/history') ?>"><i class="bi bi-clock-history me-2"></i> My Donations</a></li>
                                <?php elseif ($currentRole === 'HOSPITAL_STAFF'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/hospital/dashboard') ?>"><i class="bi bi-hospital me-2"></i> Hospital Portal</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/hospital/requests') ?>"><i class="bi bi-card-checklist me-2"></i> Blood Requests</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/hospital/inventory') ?>"><i class="bi bi-box-seam me-2"></i> Inventory</a></li>
                                <?php elseif ($currentRole === 'ADMIN'): ?>
                                    <li><a class="dropdown-item" href="<?= url('/admin/dashboard') ?>"><i class="bi bi-shield-check me-2"></i> Admin Portal</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/inventory') ?>"><i class="bi bi-boxes me-2"></i> FEFO Inventory</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/requests') ?>"><i class="bi bi-clipboard2-pulse me-2"></i> Hospital Blood Requests</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/events') ?>"><i class="bi bi-calendar-heart me-2"></i> Donation Events</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/hospitals') ?>"><i class="bi bi-building-check me-2"></i> Hospitals</a></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin/reports') ?>"><i class="bi bi-graph-up me-2"></i> Reports</a></li>
                                <?php endif; ?>
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
                        Blood donation, request coordination, and blood bank inventory in one place.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end small">
                    <ul class="list-inline mb-2">
                        <li class="list-inline-item"><a href="<?= url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/compatibility') ?>" class="text-decoration-none text-muted">Blood Compatibility</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/about') ?>" class="text-decoration-none text-muted">About BloodLink</a></li>
                        <li class="list-inline-item">•</li>
                        <li class="list-inline-item"><a href="<?= url('/login') ?>" class="text-decoration-none text-muted">Portal Login</a></li>
                    </ul>
                    <p class="text-muted mb-0">Connecting donors and hospitals to help keep essential blood available.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-password-toggle]');
        if (!toggle) return;

        const passwordInput = document.getElementById(toggle.getAttribute('aria-controls'));
        const icon = toggle.querySelector('i');
        if (!passwordInput || !icon) return;

        const showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !showPassword);
        icon.classList.toggle('bi-eye-slash', showPassword);
        toggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        toggle.setAttribute('title', showPassword ? 'Hide password' : 'Show password');
    });
    </script>
</body>
</html>
