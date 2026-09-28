<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist text-primary me-2"></i> Hospital Blood Requests Directory</h5>
            <small class="text-muted">Requisition history for <?= e($staff['hospital_name']) ?></small>
        </div>
        <a href="<?= url('/hospital/requests/create') ?>" class="btn btn-sm btn-danger fw-bold">
            <i class="bi bi-plus-circle me-1"></i> New Request
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="p-3 bg-light border-bottom d-flex flex-wrap gap-1 align-items-center">
        <span class="small fw-bold text-muted me-2">Filter Status:</span>
        <a href="<?= url('/hospital/requests') ?>" class="btn btn-sm <?= empty($currentFilter) ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
        <a href="<?= url('/hospital/requests?status=PENDING') ?>" class="btn btn-sm <?= $currentFilter === 'PENDING' ? 'btn-dark' : 'btn-outline-secondary' ?>">Pending</a>
        <a href="<?= url('/hospital/requests?status=MATCHING') ?>" class="btn btn-sm <?= $currentFilter === 'MATCHING' ? 'btn-dark' : 'btn-outline-secondary' ?>">Matching</a>
        <a href="<?= url('/hospital/requests?status=PARTIALLY_FULFILLED') ?>" class="btn btn-sm <?= $currentFilter === 'PARTIALLY_FULFILLED' ? 'btn-dark' : 'btn-outline-secondary' ?>">Partial</a>
        <a href="<?= url('/hospital/requests?status=FULFILLED') ?>" class="btn btn-sm <?= $currentFilter === 'FULFILLED' ? 'btn-dark' : 'btn-outline-secondary' ?>">Fulfilled</a>
        <a href="<?= url('/hospital/requests?status=CANCELLED') ?>" class="btn btn-sm <?= $currentFilter === 'CANCELLED' ? 'btn-dark' : 'btn-outline-secondary' ?>">Cancelled</a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Req ID</th>
                        <th>Type</th>
                        <th>Blood Type</th>
                        <th>Urgency</th>
                        <th>Required Schedule</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No blood requests found matching criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td><code>#REQ-<?= $r['request_id'] ?></code></td>
                                <td class="fw-semibold text-dark"><?= e($r['request_type']) ?></td>
                                <td class="fw-semibold"><?= e($r['blood_types'] ?? '—') ?></td>
                                <td>
                                    <?php if ($r['urgency'] === 'CRITICAL'): ?>
                                        <span class="badge bg-danger">Critical</span>
                                    <?php elseif ($r['urgency'] === 'HIGH'): ?>
                                        <span class="badge bg-warning text-dark">High</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= e($r['urgency']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($r['required_date']) ?></div>
                                    <small class="text-muted"><?= e($r['required_time'] ?? 'Anytime') ?></small>
                                </td>
                                <td class="small text-muted" style="max-width: 250px;"><?= e(substr($r['reason'], 0, 50)) ?>...</td>
                                <td>
                                    <?php if ($r['status'] === 'FULFILLED'): ?>
                                        <span class="badge badge-status badge-status-available">Fulfilled</span>
                                    <?php elseif ($r['status'] === 'PARTIALLY_FULFILLED'): ?>
                                        <span class="badge badge-status badge-status-issued">Partially Fulfilled</span>
                                    <?php elseif ($r['status'] === 'MATCHING'): ?>
                                        <span class="badge badge-status badge-status-reserved">Matching Donors</span>
                                    <?php elseif ($r['status'] === 'PENDING'): ?>
                                        <span class="badge bg-secondary-subtle text-dark border">Pending</span>
                                    <?php elseif ($r['status'] === 'CANCELLED'): ?>
                                        <span class="badge bg-light text-muted border">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger"><?= e($r['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e(substr($r['request_date'], 0, 16)) ?></td>
                                <td class="text-end">
                                    <a href="<?= url('/hospital/requests/view/' . $r['request_id']) ?>" class="btn btn-sm btn-outline-primary">
                                        View Dossier <i class="bi bi-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
