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
                        Log completed collections, screening outcomes, and automatic eligibility interval resets.
                    </div>
                </div>
                <button type="button" class="btn btn-danger btn-sm px-3 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#recordDonationModal">
                    <i class="bi bi-plus-circle me-1"></i> Record New Donation
                </button>
            </div>
        </div>
    </div>

    <!-- Record Donation Modal -->
    <div class="modal fade" id="recordDonationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="<?= url('/admin/donations/record') ?>" method="POST" class="modal-content">
                <?= csrf_field() ?>
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-heart-pulse me-2"></i> Record New Blood Donation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Select Registered Donor *</label>
                            <select class="form-select" name="donor_id" required>
                                <option value="">-- Choose Donor --</option>
                                <?php foreach ($donors as $d): ?>
                                    <option value="<?= (int)$d['donor_id'] ?>">
                                        <?= e($d['full_name']) ?> (<?= e($d['group_name']) ?>, <?= e($d['city']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Donation Type *</label>
                            <select class="form-select" name="donation_type" required>
                                <option value="WHOLE_BLOOD">WHOLE_BLOOD (Standard ~450 mL)</option>
                                <option value="PLASMA">PLASMA (Apheresis ~250 mL)</option>
                                <option value="PLATELET">PLATELET (Apheresis ~200 mL)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date & Time of Collection *</label>
                            <input type="datetime-local" class="form-control" name="donation_date" required value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Quantity Collected (mL) *</label>
                            <input type="number" step="10" class="form-control" name="quantity_ml" required value="450">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Infectious Disease Screening *</label>
                            <select class="form-select" name="screening_status" required>
                                <option value="PASSED" selected>PASSED (HIV, HBV, HCV, Syphilis Non-Reactive)</option>
                                <option value="PENDING">PENDING (Laboratory Testing in Progress)</option>
                                <option value="FAILED">FAILED (Reactive / Discard Candidate)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Donation Status *</label>
                            <select class="form-select" name="donation_status" required>
                                <option value="COMPLETED" selected>COMPLETED (Collection Successful)</option>
                                <option value="SCHEDULED">SCHEDULED</option>
                                <option value="CANCELLED">CANCELLED</option>
                                <option value="REJECTED">REJECTED</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Clinical / Phlebotomy Notes</label>
                            <input type="text" class="form-control" name="notes" placeholder="e.g. Uncomplicated donation, donor tolerated procedure well.">
                        </div>
                    </div>

                    <div class="alert alert-light border small text-muted mb-0">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        <strong>DBMS Automation:</strong> When a donation is marked <code>COMPLETED</code> and <code>PASSED</code>, the system automatically advances the donor's <code>next_eligible_date</code> by the configured safety interval (90 days) and evaluates recognition tier upgrades.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">Record & Accession</button>
                </div>
            </form>
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
