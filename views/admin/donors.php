<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-danger me-2"></i> Registered Donors Directory</h5>
            <small class="text-muted">Master database of all registered voluntary donors</small>
        </div>
        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
            <?= count($donors) ?> Donors Listed
        </span>
    </div>

    <!-- Filter Form -->
    <div class="p-3 bg-light border-bottom">
        <form action="<?= url('/admin/donors') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" class="form-control form-control-sm" name="city" placeholder="Filter by City / Region..." value="<?= e($filters['city'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="blood_group_id">
                    <option value="">-- All Blood Groups --</option>
                    <?php foreach ($bloodGroups as $bg): ?>
                        <option value="<?= (int)$bg['blood_group_id'] ?>" <?= (!empty($filters['blood_group_id']) && (int)$filters['blood_group_id'] === (int)$bg['blood_group_id']) ? 'selected' : '' ?>>
                            <?= e($bg['group_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" name="availability">
                    <option value="">-- All Availability --</option>
                    <option value="AVAILABLE" <?= (!empty($filters['availability']) && $filters['availability'] === 'AVAILABLE') ? 'selected' : '' ?>>AVAILABLE</option>
                    <option value="UNAVAILABLE" <?= (!empty($filters['availability']) && $filters['availability'] === 'UNAVAILABLE') ? 'selected' : '' ?>>UNAVAILABLE</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= url('/admin/donors') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Donor ID</th>
                        <th>Full Name</th>
                        <th>Blood Group</th>
                        <th>City / Region</th>
                        <th>Contact Email & Phone</th>
                        <th>Completed Donations</th>
                        <th>Availability</th>
                        <th>Next Eligible Date</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donors)): ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">No donor records match the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($donors as $d): ?>
                            <tr>
                                <td><code>#D-<?= str_pad($d['donor_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                <td class="fw-bold text-dark"><?= e($d['full_name']) ?></td>
                                <td><span class="badge-blood badge-blood-<?= substr($d['group_name'], 0, 1) ?>"><?= e($d['group_name']) ?></span></td>
                                <td><i class="bi bi-geo-alt text-danger me-1"></i><?= e($d['city']) ?></td>
                                <td>
                                    <div><?= e($d['email']) ?></div>
                                    <small class="text-muted"><?= e($d['phone'] ?? '—') ?></small>
                                </td>
                                <td class="fw-bold text-center">
                                    <?php if ((int)$d['total_donations'] === 0): ?>
                                        <span class="badge bg-secondary-subtle text-muted">0 (Never Donated)</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success"><?= (int)$d['total_donations'] ?> times</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($d['availability_status'] === 'AVAILABLE'): ?>
                                        <span class="badge bg-success-subtle text-success">AVAILABLE</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">UNAVAILABLE</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($d['next_eligible_date']): ?>
                                        <span class="small <?= (strtotime($d['next_eligible_date']) <= time()) ? 'text-success fw-bold' : 'text-muted' ?>">
                                            <?= e($d['next_eligible_date']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-info-subtle text-info">Immediate</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e($d['registration_date']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
