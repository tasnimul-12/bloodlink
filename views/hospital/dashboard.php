<div class="row gy-4 mb-4">
    <!-- Hospital Header & Approval Banner -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-primary">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill mb-2">
                        <i class="bi bi-hospital me-1"></i> Authorized Healthcare Facility
                    </span>
                    <h3 class="fw-bold mb-1"><?= e($staff['hospital_name']) ?></h3>
                    <div class="text-muted small">
                        <i class="bi bi-person-circle me-1 text-primary"></i> <?= e($staff['staff_name']) ?> (<?= e($staff['designation'] ?? 'Staff Officer') ?>) • 
                        <i class="bi bi-geo-alt me-1 text-danger"></i> <?= e($staff['hospital_city']) ?> • Reg: <?= e($staff['registration_number']) ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php if ($staff['approval_status'] === 'APPROVED'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Hospital Account Verified & Active
                        </span>
                        <a href="<?= url('/hospital/requests/create') ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold">
                            <i class="bi bi-plus-circle me-1"></i> New Blood Request
                        </a>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                            <i class="bi bi-hourglass-split me-1"></i> Status: <?= e($staff['approval_status']) ?> (Awaiting Admin Review)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($staff['approval_status'] !== 'APPROVED'): ?>
        <div class="col-12">
            <div class="alert alert-warning border shadow-sm d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold mb-1">Administrative Verification Required</h6>
                    <p class="small mb-0 text-muted">
                        Your hospital profile is currently pending review by a system administrator. During this period, you can view inventory availability, but submitting blood fulfillment orders or emergency requests is disabled.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($pendingDonations)): ?>
        <div class="col-12">
            <section class="card card-bloodlink border-warning shadow-sm" aria-labelledby="pending-donations-heading">
                <div class="card-header bg-warning-subtle py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-warning-emphasis" id="pending-donations-heading">
                        <i class="bi bi-clipboard2-pulse me-2" aria-hidden="true"></i>Donor Responses Awaiting Confirmation
                    </h5>
                    <span class="badge bg-warning text-dark"><?= count($pendingDonations) ?> Awaiting</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Donor</th>
                                <th scope="col">Blood Group</th>
                                <th scope="col">Request</th>
                                <th scope="col">Accepted At</th>
                                <th scope="col" class="text-end">Next Step</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingDonations as $pendingDonation): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($pendingDonation['full_name']) ?></td>
                                    <td><span class="badge-blood badge-blood-<?= substr($pendingDonation['blood_group'], 0, 1) ?>"><?= e($pendingDonation['blood_group']) ?></span></td>
                                    <td><a href="<?= url('/hospital/requests/view/' . $pendingDonation['request_id']) ?>">#REQ-<?= (int)$pendingDonation['request_id'] ?></a></td>
                                    <td><?= e($pendingDonation['response_at']) ?></td>
                                    <td class="text-end"><a href="<?= url('/hospital/requests/view/' . $pendingDonation['request_id']) ?>" class="btn btn-sm btn-warning fw-semibold">Review response</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <!-- KPI Request Metrics -->
    <div class="col-md-3 col-6">
        <div class="card card-bloodlink p-3">
            <span class="text-muted small fw-semibold">Total Requests</span>
            <div class="fs-3 fw-bold text-dark mt-1"><?= (int)$stats['total_requests'] ?></div>
            <small class="text-muted">Lifetime facility demand</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-bloodlink p-3 border-warning-subtle">
            <span class="text-warning-emphasis small fw-semibold">Pending Allocation</span>
            <div class="fs-3 fw-bold text-warning mt-1"><?= (int)$stats['pending_count'] ?></div>
            <small class="text-muted">Awaiting fulfillment / match</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-bloodlink p-3 border-info-subtle">
            <span class="text-info-emphasis small fw-semibold">Partially Fulfilled</span>
            <div class="fs-3 fw-bold text-info mt-1"><?= (int)$stats['partial_count'] ?></div>
            <small class="text-muted">Partial bags dispatched</small>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card card-bloodlink p-3 border-success-subtle">
            <span class="text-success small fw-semibold">Completed Orders</span>
            <div class="fs-3 fw-bold text-success mt-1"><?= (int)$stats['fulfilled_count'] ?></div>
            <small class="text-muted">100% fulfilled shipments</small>
        </div>
    </div>

    <!-- Recent Facility Requests -->
    <div class="col-lg-8">
        <div class="card card-bloodlink">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Blood Requests</h5>
                <a href="<?= url('/hospital/requests') ?>" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Req ID</th>
                                <th>Type & Urgency</th>
                                <th>Required Date</th>
                                <th>Status</th>
                                <th class="text-end">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentRequests)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No requests submitted yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentRequests as $r): ?>
                                    <tr>
                                        <td><code>#REQ-<?= $r['request_id'] ?></code></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= e($r['request_type']) ?></span>
                                            <?php if ($r['urgency'] === 'CRITICAL'): ?>
                                                <span class="badge bg-danger ms-1">Critical</span>
                                            <?php elseif ($r['urgency'] === 'HIGH'): ?>
                                                <span class="badge bg-warning text-dark ms-1">High</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= e($r['urgency']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($r['required_date']) ?> <?= e($r['required_time'] ?? '') ?></td>
                                        <td>
                                            <?php if ($r['status'] === 'FULFILLED'): ?>
                                                <span class="badge badge-status badge-status-available">Fulfilled</span>
                                            <?php elseif ($r['status'] === 'PARTIALLY_FULFILLED'): ?>
                                                <span class="badge badge-status badge-status-issued">Partial</span>
                                            <?php elseif ($r['status'] === 'MATCHING'): ?>
                                                <span class="badge badge-status badge-status-reserved">Matching</span>
                                            <?php elseif ($r['status'] === 'PENDING'): ?>
                                                <span class="badge bg-secondary-subtle text-dark border">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border"><?= e($r['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('/hospital/requests/view/' . $r['request_id']) ?>" class="btn btn-sm btn-outline-primary">
                                                Manage <i class="bi bi-chevron-right"></i>
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
    </div>

    <!-- Permitted Regional Stock Overview -->
    <div class="col-lg-4">
        <div class="card card-bloodlink h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-2 text-danger"></i> Central Stock Summary</h6>
                <a href="<?= url('/hospital/inventory') ?>" class="btn btn-sm btn-outline-secondary">Full Stock</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Group</th>
                                <th>Component</th>
                                <th>Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($stockSummary, 0, 8) as $s): ?>
                                <tr>
                                    <td><span class="badge-blood badge-blood-<?= substr($s['group_name'], 0, 1) ?> py-0 px-2"><?= e($s['group_name']) ?></span></td>
                                    <td class="small text-muted"><?= e($s['component_type'] ?? 'WHOLE_BLOOD') ?></td>
                                    <td class="fw-bold"><?= (int)$s['total_units'] ?> units</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
