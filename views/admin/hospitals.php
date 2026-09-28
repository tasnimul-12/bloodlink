<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-building-check text-primary me-2"></i> Healthcare Facility Management</h5>
            <small class="text-muted">Review, approve, suspend, or reject hospital accounts</small>
        </div>
        <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Hospital Name</th>
                        <th>Reg Number</th>
                        <th>City & Address</th>
                        <th>Contact Person</th>
                        <th>Staff Count</th>
                        <th>Approval Status</th>
                        <th>Approved By</th>
                        <th class="text-end">Verification Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hospitals as $h): ?>
                        <tr>
                            <td><code>#H-<?= $h['hospital_id'] ?></code></td>
                            <td class="fw-bold text-dark"><?= e($h['hospital_name']) ?></td>
                            <td><code><?= e($h['registration_number']) ?></code></td>
                            <td>
                                <div><i class="bi bi-geo-alt text-danger me-1"></i><?= e($h['city']) ?></div>
                                <small class="text-muted"><?= e($h['address']) ?></small>
                            </td>
                            <td>
                                <div><?= e($h['contact_person']) ?></div>
                                <small class="text-muted"><?= e($h['email']) ?> • <?= e($h['phone']) ?></small>
                            </td>
                            <td class="fw-semibold text-center"><?= (int)$h['staff_count'] ?></td>
                            <td>
                                <?php if ($h['approval_status'] === 'APPROVED'): ?>
                                    <span class="badge badge-status badge-status-available">Approved</span>
                                <?php elseif ($h['approval_status'] === 'PENDING'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php elseif ($h['approval_status'] === 'SUSPENDED'): ?>
                                    <span class="badge badge-status badge-status-reserved">Suspended</span>
                                <?php else: ?>
                                    <span class="badge badge-status badge-status-discarded"><?= e($h['approval_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?= e($h['approved_by_user'] ?? '—') ?>
                            </td>
                            <td class="text-end">
                                <form action="<?= url('/admin/hospitals/status/' . $h['hospital_id']) ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <?php if ($h['approval_status'] !== 'APPROVED'): ?>
                                        <button type="submit" name="status" value="APPROVED" class="btn btn-sm btn-success me-1" title="Approve Facility">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($h['approval_status'] !== 'SUSPENDED'): ?>
                                        <button type="submit" name="status" value="SUSPENDED" class="btn btn-sm btn-warning text-dark me-1" title="Suspend Facility">
                                            <i class="bi bi-pause-fill"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($h['approval_status'] !== 'REJECTED'): ?>
                                        <button type="submit" name="status" value="REJECTED" class="btn btn-sm btn-outline-danger" title="Reject Facility">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-bloodlink shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge text-primary me-2"></i> Hospital Staff Access</h5>
        <small class="text-muted">Activate accounts only after verifying the staff member and approving their hospital.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Staff Member</th>
                        <th>Account</th>
                        <th>Hospital</th>
                        <th>Hospital Status</th>
                        <th>Staff Access</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staffMembers as $member): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($member['staff_name']) ?></div>
                                <small class="text-muted"><?= e($member['designation'] ?? 'Staff Officer') ?></small>
                            </td>
                            <td>
                                <div><?= e($member['username']) ?></div>
                                <small class="text-muted"><?= e($member['email']) ?></small>
                            </td>
                            <td><?= e($member['hospital_name']) ?></td>
                            <td><?= e($member['approval_status']) ?></td>
                            <td><?= e($member['staff_status']) ?></td>
                            <td class="text-end">
                                <?php if ($member['staff_status'] === 'ACTIVE'): ?>
                                    <form action="<?= url('/admin/hospital-staff/status/' . (int)$member['staff_id']) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" name="status" value="INACTIVE" class="btn btn-sm btn-outline-danger">Deactivate</button>
                                    </form>
                                <?php elseif ($member['approval_status'] === 'APPROVED'): ?>
                                    <form action="<?= url('/admin/hospital-staff/status/' . (int)$member['staff_id']) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" name="status" value="ACTIVE" class="btn btn-sm btn-success">Activate</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small text-muted">Approve hospital first</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($staffMembers)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No hospital staff accounts found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
