<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-clock-history me-2"></i> My Complete Donation History</h5>
            <small class="text-muted">Verified biological donation records linked to your donor ID #<?= (int)$donor['donor_id'] ?></small>
        </div>
        <a href="<?= url('/donor/dashboard') ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Donation ID</th>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Volume</th>
                        <th>Screening Result</th>
                        <th>Donation Status</th>
                        <th>Notes / Center Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donations)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No historical donations recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($donations as $don): ?>
                            <tr>
                                <td><code>#DON-<?= str_pad($don['donation_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td><?= e($don['donation_date']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-dark border"><?= e($don['donation_type']) ?></span></td>
                                <td class="fw-bold"><?= number_format((float)$don['quantity_ml'], 2) ?> mL</td>
                                <td>
                                    <?php if ($don['screening_status'] === 'PASSED'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Passed</span>
                                    <?php elseif ($don['screening_status'] === 'FAILED'): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>Failed</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($don['donation_status'] === 'COMPLETED'): ?>
                                        <span class="badge badge-status badge-status-available">Completed</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-status-reserved"><?= e($don['donation_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e($don['notes'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
