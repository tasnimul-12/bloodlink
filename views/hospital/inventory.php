<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-boxes text-danger me-2"></i> Permitted Regional Stock Viewer</h5>
            <small class="text-muted">Live unreserved blood units available for clinical allocation (donor PII protected)</small>
        </div>
        <a href="<?= url('/hospital/requests/create') ?>" class="btn btn-sm btn-danger fw-bold">
            <i class="bi bi-plus-circle me-1"></i> Request Units
        </a>
    </div>

    <!-- Filter Form -->
    <div class="p-3 bg-light border-bottom">
        <form action="<?= url('/hospital/inventory') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="blood_group_id">
                    <option value="">-- All Blood Groups --</option>
                    <?php foreach ($bloodGroups as $bg): ?>
                        <option value="<?= (int)$bg['blood_group_id'] ?>" <?= (!empty($filters['blood_group_id']) && (int)$filters['blood_group_id'] === (int)$bg['blood_group_id']) ? 'selected' : '' ?>>
                            <?= e($bg['group_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="component_type">
                    <option value="">-- All Components --</option>
                    <option value="WHOLE_BLOOD" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'WHOLE_BLOOD') ? 'selected' : '' ?>>Whole Blood</option>
                    <option value="RBC" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'RBC') ? 'selected' : '' ?>>RBC (Red Cells)</option>
                    <option value="PLASMA" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'PLASMA') ? 'selected' : '' ?>>Plasma</option>
                    <option value="PLATELET" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'PLATELET') ? 'selected' : '' ?>>Platelet</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel me-1"></i> Apply Filter</button>
                <a href="<?= url('/hospital/inventory') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bag Identifier</th>
                        <th>Blood Group</th>
                        <th>Component</th>
                        <th>Volume</th>
                        <th>Expiry Date (FEFO Sorted)</th>
                        <th>Days Remaining</th>
                        <th>Storage Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($availableBags)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No available blood bags matching filters.</td></tr>
                    <?php else: ?>
                        <?php foreach ($availableBags as $bag): ?>
                            <tr>
                                <td><code><?= e($bag['bag_number']) ?></code></td>
                                <td>
                                    <span class="badge-blood badge-blood-<?= substr($bag['blood_group'], 0, 1) ?>"><?= e($bag['blood_group']) ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($bag['component_type']) ?></span></td>
                                <td class="fw-semibold"><?= number_format((float)$bag['quantity_ml'], 0) ?> mL</td>
                                <td class="fw-bold text-dark"><?= e($bag['expiry_date']) ?></td>
                                <td>
                                    <?php if ((int)$bag['days_to_expiry'] <= 3): ?>
                                        <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= $bag['days_to_expiry'] ?> day(s)</span>
                                    <?php elseif ((int)$bag['days_to_expiry'] <= 7): ?>
                                        <span class="badge bg-warning text-dark"><?= $bag['days_to_expiry'] ?> day(s)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= $bag['days_to_expiry'] ?> day(s)</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="bi bi-snow me-1 text-primary"></i><?= e($bag['location_name']) ?></td>
                                <td><span class="badge badge-status badge-status-available">Available</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
