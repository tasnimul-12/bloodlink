<?php
$dashboardUrl = match (Session::role()) {
    'HOSPITAL_STAFF' => url('/hospital/dashboard'),
    'ADMIN' => url('/admin/dashboard'),
    default => url('/donor/dashboard'),
};
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-bell text-danger me-2"></i> In-System Notification Center</h5>
                <a href="<?= $dashboardUrl ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary"></i>
                        You have no notifications at this time.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $n): ?>
                            <div class="list-group-item p-3 <?= !$n['is_read'] ? 'bg-danger-subtle bg-opacity-10 border-start border-3 border-danger' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <?php if ($n['notification_type'] === 'URGENT_MATCH'): ?>
                                            <i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>
                                        <?php else: ?>
                                            <i class="bi bi-info-circle-fill text-primary me-1"></i>
                                        <?php endif; ?>
                                        <?= e($n['title']) ?>
                                    </h6>
                                    <small class="text-muted"><?= e($n['created_at']) ?></small>
                                </div>
                                <p class="text-muted small mb-0"><?= e($n['message']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
