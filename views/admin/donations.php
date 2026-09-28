<div class="row gy-4 mb-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-danger">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill mb-2">
                        <i class="bi bi-heart-pulse-fill me-1"></i> Phlebotomy & Accession Workflow
                    </span>
                    <h3 class="fw-bold mb-1">Donation Records & Clinical Screening</h3>
                    <div class="text-muted small">
                        Review donor-accepted collections confirmed by the responsible hospital staff.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Donations Table -->
    <div class="col-12">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-medical text-danger me-2"></i> All Donation Records</h5>
                <span class="badge bg-secondary-subtle text-dark border"><?= count($donations) ?> Records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Donation ID</th>
                                <th>Donor Full Name</th>
                                <th>Blood Group</th>
                                <th>Collection Date</th>
                                <th>Type</th>
                                <th>Volume (mL)</th>
                                <th>Screening</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($donations)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">No donations on record.</td></tr>
                            <?php else: ?>
                                <?php foreach ($donations as $don): ?>
                                    <tr>
                                        <td><code>#DON-<?= str_pad($don['donation_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                        <td class="fw-semibold text-dark"><?= e($don['full_name']) ?></td>
                                        <td><span class="badge-blood badge-blood-<?= substr($don['blood_group'], 0, 1) ?> py-0 px-2"><?= e($don['blood_group']) ?></span></td>
                                        <td><?= e($don['donation_date']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($don['donation_type']) ?></span></td>
                                        <td class="fw-bold"><?= number_format((float)$don['quantity_ml'], 0) ?> mL</td>
                                        <td>
                                            <?php if ($don['screening_status'] === 'PASSED'): ?>
                                                <span class="badge bg-success-subtle text-success">Passed</span>
                                            <?php elseif ($don['screening_status'] === 'FAILED'): ?>
                                                <span class="badge bg-danger-subtle text-danger">Failed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning">Pending</span>
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
    </div>
</div>
