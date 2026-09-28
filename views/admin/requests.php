<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="text-danger small fw-bold text-uppercase">Hospital coordination</div>
        <h2 class="fw-bold mb-1">Create a hospital blood request</h2>
        <p class="text-muted mb-0">Requests are attached to an approved hospital and eligible donors are notified automatically.</p>
    </div>
    <a href="<?= url('/admin/inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-seam me-1"></i> Inventory</a>
</div>

<section class="card card-bloodlink p-4 mb-4">
    <form action="<?= url('/admin/requests/create') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold" for="hospital_id">Approved hospital *</label>
                <select class="form-select" id="hospital_id" name="hospital_id" required>
                    <option value="">Select hospital</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= (int)$hospital['hospital_id'] ?>"><?= e($hospital['hospital_name']) ?> · <?= e($hospital['city']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($hospitals)): ?><small class="text-danger">No approved hospitals with active staff are available.</small><?php endif; ?>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="request_type">Request type *</label>
                <select class="form-select" id="request_type" name="request_type" required>
                    <option value="EMERGENCY">Emergency</option>
                    <option value="ROUTINE">Routine</option>
                    <option value="SURGERY">Surgery</option>
                    <option value="MATERNITY">Maternity</option>
                    <option value="OTHER">Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" for="urgency">Urgency *</label>
                <select class="form-select" id="urgency" name="urgency" required>
                    <option value="CRITICAL">Critical</option>
                    <option value="HIGH" selected>High</option>
                    <option value="MEDIUM">Medium</option>
                    <option value="LOW">Low</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold" for="blood_group_id">Blood group *</label>
                <select class="form-select" id="blood_group_id" name="blood_group_id" required>
                    <option value="">Select group</option>
                    <?php foreach ($bloodGroups ?? [] as $group): ?>
                        <option value="<?= (int)$group['blood_group_id'] ?>"><?= e($group['group_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold" for="component_type">Component *</label>
                <select class="form-select" id="component_type" name="component_type" required>
                    <option value="WHOLE_BLOOD">Whole blood</option>
                    <option value="RBC">RBC</option>
                    <option value="PLASMA">Plasma</option>
                    <option value="PLATELET">Platelets</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold" for="quantity_requested">Volume (mL) *</label>
                <input class="form-control" id="quantity_requested" name="quantity_requested" type="number" min="1" max="99999.99" step="50" value="450" required>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold" for="required_date">Required date *</label>
                <input class="form-control" id="required_date" name="required_date" type="date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label fw-semibold" for="required_time">Required time</label>
                <input class="form-control" id="required_time" name="required_time" type="time">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold" for="reason">Clinical reason *</label>
                <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="500" required placeholder="Clinical indication provided by the hospital"></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-danger fw-semibold" type="submit" <?= empty($hospitals) ? 'disabled' : '' ?>><i class="bi bi-send me-1"></i> Create request and notify donors</button>
            </div>
        </div>
    </form>
</section>

<section class="card card-bloodlink">
    <div class="card-header bg-white p-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">Recent hospital requests</h5>
            <small class="text-muted">Latest 50 requests across approved facilities</small>
        </div>
        <span class="badge text-bg-light border"><?= count($requests) ?> shown</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Hospital</th><th>Requirement</th><th>Urgency</th><th>Status</th><th>Donor invites</th><th>Required</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No blood requests have been created yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($request['hospital_name']) ?><div class="small text-muted">#<?= (int)$request['request_id'] ?></div></td>
                            <td><?= e($request['requested_items']) ?></td>
                            <td><span class="badge <?= $request['urgency'] === 'CRITICAL' ? 'text-bg-danger' : 'text-bg-light border text-dark' ?>"><?= e($request['urgency']) ?></span></td>
                            <td><?= e(str_replace('_', ' ', $request['status'])) ?></td>
                            <td><?= (int)$request['pending_donor_invites'] ?></td>
                            <td><?= e($request['required_date']) ?></td>
                            <td class="text-end">
                                <?php if (in_array($request['status'], ['PENDING', 'MATCHING', 'PARTIALLY_FULFILLED'], true)): ?>
                                    <form action="<?= url('/admin/requests/match/' . (int)$request['request_id']) ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Notify additional eligible donors"><i class="bi bi-arrow-repeat me-1"></i> Match donors</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>