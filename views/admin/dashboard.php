<div class="row gy-4 mb-4">
    <!-- Admin Header -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-danger">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill mb-2">
                        <i class="bi bi-shield-lock me-1"></i> System Administration Console
                    </span>
                    <h3 class="fw-bold mb-1">Central Blood Bank Operations Command</h3>
                    <div class="text-muted small">
                        Real-time telemetry, FEFO inventory allocation, and administrative controls.
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= url('/admin/donations') ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold">
                        <i class="bi bi-journal-medical me-1"></i> Donation Records
                    </a>
                    <a href="<?= url('/admin/reports') ?>" class="btn btn-outline-dark btn-sm px-3 py-2 fw-bold">
                        <i class="bi bi-file-earmark-bar-graph me-1"></i> DBMS Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Summary Row -->
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3">
            <span class="text-muted small fw-semibold">Total Donors</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= (int)$kpis['total_donors'] ?></div>
            <small class="text-muted">Registered profiles</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3">
            <span class="text-muted small fw-semibold">Total Facilities</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= (int)$kpis['total_hospitals'] ?></div>
            <small class="text-muted">Partner institutions</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3 <?= ($kpis['pending_hospitals'] > 0) ? 'border-warning bg-warning-subtle bg-opacity-10' : '' ?>">
            <span class="text-warning-emphasis small fw-semibold">Pending Verif.</span>
            <div class="fs-4 fw-bold text-warning mt-1"><?= (int)$kpis['pending_hospitals'] ?></div>
            <small class="text-muted">Awaiting review</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3">
            <span class="text-muted small fw-semibold">Available Bags</span>
            <div class="fs-4 fw-bold text-success mt-1"><?= (int)$kpis['available_units'] ?></div>
            <small class="text-muted"><?= number_format((float)$kpis['available_volume'], 0) ?> mL stored</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3 <?= ($kpis['near_expiry_units'] > 0) ? 'border-danger bg-danger-subtle bg-opacity-10' : '' ?>">
            <span class="text-danger small fw-semibold">Near Expiry (&le;5d)</span>
            <div class="fs-4 fw-bold text-danger mt-1"><?= (int)$kpis['near_expiry_units'] ?></div>
            <small class="text-muted">FEFO priorities</small>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card card-bloodlink p-3 <?= ($kpis['critical_requests'] > 0) ? 'border-danger' : '' ?>">
            <span class="text-danger small fw-semibold">Critical Reqs</span>
            <div class="fs-4 fw-bold text-danger mt-1"><?= (int)$kpis['critical_requests'] ?></div>
            <small class="text-muted">STAT hospital orders</small>
        </div>
    </div>

    <!-- Pending Hospital Approvals Alert -->
    <?php if (!empty($pendingHospitals)): ?>
        <div class="col-12">
            <div class="card card-bloodlink border-warning shadow-sm">
                <div class="card-header bg-warning-subtle text-warning-emphasis py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-building-exclamation me-2"></i> Pending Healthcare Facility Verification Requests</span>
                    <span class="badge bg-warning text-dark"><?= count($pendingHospitals) ?> Pending</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hospital Name</th>
                                    <th>Reg Number</th>
                                    <th>City</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>
                                    <th class="text-end">Verification Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingHospitals as $ph): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= e($ph['hospital_name']) ?></td>
                                        <td><code><?= e($ph['registration_number']) ?></code></td>
                                        <td><?= e($ph['city']) ?></td>
                                        <td><?= e($ph['contact_person']) ?></td>
                                        <td><?= e($ph['email']) ?></td>
                                        <td class="text-end">
                                            <form action="<?= url('/admin/hospitals/status/' . $ph['hospital_id']) ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" name="status" value="APPROVED" class="btn btn-sm btn-success fw-bold me-1">
                                                    <i class="bi bi-check-lg"></i> Approve
                                                </button>
                                                <button type="submit" name="status" value="REJECTED" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-x-lg"></i> Reject
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Near Expiry Warning Table -->
    <?php if (!empty($nearExpiry)): ?>
        <div class="col-lg-6">
            <div class="card card-bloodlink border-danger h-100">
                <div class="card-header bg-danger-subtle text-danger-emphasis py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i> Near-Expiry Units (FEFO Priority)</h6>
                    <span class="badge bg-danger"><?= count($nearExpiry) ?> Unit(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bag ID</th>
                                    <th>Group</th>
                                    <th>Component</th>
                                    <th>Days Left</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nearExpiry as $ne): ?>
                                    <tr>
                                        <td><code><?= e($ne['bag_number']) ?></code></td>
                                        <td><span class="badge-blood badge-blood-<?= substr($ne['blood_group'], 0, 1) ?> py-0 px-2"><?= e($ne['blood_group']) ?></span></td>
                                        <td class="small"><?= e($ne['component_type']) ?></td>
                                        <td class="text-danger fw-bold"><i class="bi bi-clock me-1"></i><?= $ne['days_remaining'] ?> day(s)</td>
                                        <td class="small text-muted"><?= e($ne['location_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Stock Summary Table -->
    <div class="<?= empty($nearExpiry) ? 'col-12' : 'col-lg-6' ?>">
        <div class="card card-bloodlink h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-boxes text-danger me-2"></i> Active Stock by Group & Component</h6>
                <a href="<?= url('/admin/inventory') ?>" class="btn btn-sm btn-outline-secondary">Inventory Console</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Group</th>
                                <th>Component</th>
                                <th>Available</th>
                                <th>Total mL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockSummary as $s): ?>
                                <tr>
                                    <td><span class="badge-blood badge-blood-<?= substr($s['group_name'], 0, 1) ?> py-0 px-2"><?= e($s['group_name']) ?></span></td>
                                    <td class="small text-muted"><?= e($s['component_type'] ?? 'WHOLE_BLOOD') ?></td>
                                    <td class="fw-bold"><?= (int)$s['total_units'] ?> units</td>
                                    <td><?= number_format((float)$s['total_ml'], 0) ?> mL</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="col-12">
        <div class="card card-bloodlink">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-secondary me-2"></i> Recent System Audit Activity</h5>
                <a href="<?= url('/admin/audit-logs') ?>" class="btn btn-sm btn-outline-secondary">Full Audit Trail</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor User</th>
                                <th>Action</th>
                                <th>Entity Affected</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAudit as $a): ?>
                                <tr>
                                    <td><?= e($a['created_at']) ?></td>
                                    <td class="fw-semibold text-dark"><i class="bi bi-person me-1"></i><?= e($a['username'] ?? 'SYSTEM / TRIGGER') ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($a['action']) ?></span></td>
                                    <td><code><?= e($a['entity_name']) ?> #<?= e($a['entity_id']) ?></code></td>
                                    <td class="small text-muted"><?= e($a['ip_address'] ?? '127.0.0.1') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
