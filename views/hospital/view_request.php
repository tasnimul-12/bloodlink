<div class="row gy-4 mb-4">
    <!-- Header Card -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-primary">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="fs-5 fw-bold text-dark">Blood Requisition Dossier #REQ-<?= (int)$request['request_id'] ?></span>
                        <?php if ($request['urgency'] === 'CRITICAL'): ?>
                            <span class="badge bg-danger">Critical Urgency</span>
                        <?php elseif ($request['urgency'] === 'HIGH'): ?>
                            <span class="badge bg-warning text-dark">High Urgency</span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border"><?= e($request['urgency']) ?></span>
                        <?php endif; ?>

                        <?php if ($request['status'] === 'FULFILLED'): ?>
                            <span class="badge badge-status badge-status-available">Fulfilled</span>
                        <?php elseif ($request['status'] === 'PARTIALLY_FULFILLED'): ?>
                            <span class="badge badge-status badge-status-issued">Partially Fulfilled</span>
                        <?php elseif ($request['status'] === 'MATCHING'): ?>
                            <span class="badge badge-status badge-status-reserved">Matching Donors</span>
                        <?php elseif ($request['status'] === 'PENDING'): ?>
                            <span class="badge bg-secondary-subtle text-dark border">Pending Allocation</span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border"><?= e($request['status']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">
                        <strong>Facility:</strong> <?= e($request['hospital_name']) ?> • 
                        <strong>Requested By:</strong> <?= e($request['staff_name']) ?> (<?= e($request['designation'] ?? 'Staff Officer') ?>) • 
                        <strong>Schedule:</strong> Required by <?= e($request['required_date']) ?> <?= e($request['required_time'] ?? '') ?>
                    </div>
                </div>

                <!-- Workflow Action Buttons -->
                <div class="d-flex flex-wrap gap-2">
                    <?php if (in_array($request['status'], ['PENDING', 'PARTIALLY_FULFILLED', 'MATCHING'])): ?>
                        <!-- Fulfill via FEFO Transaction -->
                        <form action="<?= url('/hospital/requests/fulfill/' . $request['request_id']) ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-success fw-bold px-3 shadow-sm" onclick="return confirm('Allocate and issue available blood units using FEFO priority?')">
                                <i class="bi bi-box-seam-fill me-1"></i> Fulfill from Inventory (FEFO)
                            </button>
                        </form>

                        <!-- Trigger Emergency Donor Matching -->
                        <form action="<?= url('/hospital/requests/match/' . $request['request_id']) ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-warning text-dark fw-bold px-3 shadow-sm" onclick="return confirm('Search compatible donors and dispatch emergency notifications?')">
                                <i class="bi bi-bell-fill me-1"></i> Emergency Donor Matching
                            </button>
                        </form>

                        <!-- Cancel Request -->
                        <form action="<?= url('/hospital/requests/cancel/' . $request['request_id']) ?>" method="POST" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to cancel this request?')">
                                Cancel
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <a href="<?= url('/hospital/requests') ?>" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>

            <div class="mt-3 pt-3 border-top">
                <div class="small fw-semibold text-muted text-uppercase mb-1">Clinical Indication & Diagnosis:</div>
                <p class="mb-1 text-dark"><?= nl2br(e($request['reason'])) ?></p>
                <?php if (!empty($request['special_notes'])): ?>
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Special Notes: <?= e($request['special_notes']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Request Line Items -->
    <div class="col-lg-7">
        <div class="card card-bloodlink h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check text-primary me-2"></i> Requested Blood Components</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Group</th>
                                <th>Component</th>
                                <th>Requested</th>
                                <th>Fulfilled</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php 
                                $req = (float)$item['quantity_requested'];
                                $ful = (float)$item['quantity_fulfilled'];
                                $pct = ($req > 0) ? min(100, round(($ful / $req) * 100)) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge-blood badge-blood-<?= substr($item['group_name'], 0, 1) ?>"><?= e($item['group_name']) ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= e($item['component_type']) ?></span></td>
                                    <td class="fw-semibold"><?= number_format($req, 0) ?> mL</td>
                                    <td class="fw-bold <?= ($ful >= $req) ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($ful, 0) ?> mL
                                    </td>
                                    <td style="min-width: 140px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar <?= ($pct >= 100) ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <small class="fw-bold text-muted"><?= $pct ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Donor Matches (Anonymized) -->
    <div class="col-lg-5">
        <div class="card card-bloodlink h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-warning me-2"></i> Matched Donor Candidates</h6>
                <span class="badge bg-warning text-dark"><?= count($matches) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($matches)): ?>
                    <div class="text-center py-4 text-muted small p-3">
                        No donor matching initiated yet. If inventory is insufficient, click "Emergency Donor Matching" above.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($matches as $m): ?>
                            <div class="list-group-item p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge-blood badge-blood-<?= substr($m['group_name'], 0, 1) ?> py-0 px-2"><?= e($m['group_name']) ?></span>
                                    <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($m['city']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary">Score: <?= number_format((float)$m['match_score'], 0) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 small">
                                    <span class="text-muted">Status:</span>
                                    <?php if ($m['match_status'] === 'ACCEPTED'): ?>
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>ACCEPTED</span>
                                    <?php elseif ($m['match_status'] === 'DECLINED'): ?>
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>DECLINED</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>NOTIFIED</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fulfillment Shipments & Issued Blood Bags -->
    <?php if (!empty($fulfillments)): ?>
        <div class="col-12">
            <div class="card card-bloodlink border-success shadow-sm">
                <div class="card-header bg-success-subtle text-success-emphasis py-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-check-all me-2"></i> Completed Fulfillment Shipments & Cold-Chain Dispatch</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fulfillment ID</th>
                                    <th>Dispatched By</th>
                                    <th>Status</th>
                                    <th>Dispatched Timestamp</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($fulfillments as $f): ?>
                                    <tr>
                                        <td><code>#FUL-<?= $f['fulfillment_id'] ?></code></td>
                                        <td><i class="bi bi-person me-1"></i><?= e($f['fulfilled_by_name'] ?? 'System / Admin') ?></td>
                                        <td><span class="badge badge-status badge-status-available">COMPLETED</span></td>
                                        <td><?= e($f['created_at']) ?></td>
                                        <td class="small text-muted"><?= e($f['notes'] ?? 'Dispatched under temperature control') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
