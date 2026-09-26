<div class="row gy-4 mb-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-danger">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill mb-2">
                <i class="bi bi-mortarboard-fill me-1"></i> Academic DBMS Technical Evaluation
            </span>
            <h3 class="fw-bold mb-1">Advanced Relational Queries & Demonstration Reports</h3>
            <p class="text-muted small mb-0">
                This page directly showcases required DBMS concepts: <code>LEFT JOIN</code> with <code>HAVING</code> filters, multi-table aggregations (<code>SUM</code>, <code>AVG</code>, <code>COUNT</code>), nested subqueries, and grouping.
            </p>
        </div>
    </div>

    <!-- Report 1: Donors with Zero Donation History (LEFT JOIN + HAVING) -->
    <div class="col-12">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-1 text-danger">
                        <i class="bi bi-person-x-fill me-2"></i> Report 1: Donors with Zero Donation History
                    </h5>
                    <small class="text-muted font-monospace">
                        SQL Concept: <code>LEFT JOIN donations ON donors.donor_id = donations.donor_id GROUP BY donors.donor_id HAVING COUNT(donations.donation_id) = 0</code>
                    </small>
                </div>
                <span class="badge bg-warning text-dark"><?= count($zeroDonors) ?> Zero-History Donor(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Donor ID</th>
                                <th>Full Name</th>
                                <th>Blood Group</th>
                                <th>City</th>
                                <th>Registration Date</th>
                                <th>Total Completed Donations</th>
                                <th>Clinical Outreach Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($zeroDonors)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">All registered donors have at least one donation on record.</td></tr>
                            <?php else: ?>
                                <?php foreach ($zeroDonors as $zd): ?>
                                    <tr>
                                        <td><code>#D-<?= str_pad($zd['donor_id'], 4, '0', STR_PAD_LEFT) ?></code></td>
                                        <td class="fw-bold text-dark"><?= e($zd['full_name']) ?></td>
                                        <td><span class="badge-blood badge-blood-<?= substr($zd['blood_group'], 0, 1) ?> py-0 px-2"><?= e($zd['blood_group']) ?></span></td>
                                        <td><?= e($zd['city']) ?></td>
                                        <td><?= e($zd['registration_date']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-dark border">0 Donations</span></td>
                                        <td><span class="badge bg-info-subtle text-info border">Candidate for First-Time Drive</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Report 2: Monthly Consumption Aggregates (GROUP BY + HAVING + AGGREGATES) -->
    <div class="col-12">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-1 text-primary">
                    <i class="bi bi-graph-up me-2"></i> Report 2: Monthly Blood Consumption & Demand Metrics
                </h5>
                <small class="text-muted font-monospace">
                    SQL Concept: <code>COUNT(fi.fulfillment_item_id)</code>, <code>SUM(fi.quantity_issued)</code>, <code>AVG(fi.quantity_issued)</code>, <code>GROUP BY issue_month, hospital_name HAVING total_volume_ml > 0</code>
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Billing / Dispatch Month</th>
                                <th>Healthcare Facility</th>
                                <th>Total Units Received</th>
                                <th>Total Volume (mL)</th>
                                <th>Average Volume / Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($consumption)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No monthly consumption records compiled yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($consumption as $c): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><i class="bi bi-calendar-month me-1"></i><?= e($c['issue_month']) ?></td>
                                        <td class="fw-semibold text-primary"><?= e($c['hospital_name']) ?></td>
                                        <td><?= (int)$c['total_units_received'] ?> bag(s)</td>
                                        <td class="fw-bold text-success"><?= number_format((float)$c['total_volume_ml'], 0) ?> mL</td>
                                        <td><?= number_format((float)$c['avg_unit_volume_ml'], 1) ?> mL</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Report 3: Correlated / Nested Subquery -->
    <div class="col-12">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-1 text-warning-emphasis">
                    <i class="bi bi-funnel-fill text-warning me-2"></i> Report 3: Requests with Unfulfilled Items (Nested Subquery)
                </h5>
                <small class="text-muted font-monospace">
                    SQL Concept: <code>WHERE br.request_id IN (SELECT ri.request_id FROM request_items ri WHERE ri.quantity_fulfilled < ri.quantity_requested)</code>
                </small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Request ID</th>
                                <th>Hospital</th>
                                <th>Type</th>
                                <th>Urgency</th>
                                <th>Status</th>
                                <th>Required Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unfulfilledRequests)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">All active requests are completely fulfilled.</td></tr>
                            <?php else: ?>
                                <?php foreach ($unfulfilledRequests as $ur): ?>
                                    <tr>
                                        <td><code>#REQ-<?= $ur['request_id'] ?></code></td>
                                        <td class="fw-bold text-dark"><?= e($ur['hospital_name']) ?></td>
                                        <td><?= e($ur['request_type']) ?></td>
                                        <td>
                                            <?php if ($ur['urgency'] === 'CRITICAL'): ?>
                                                <span class="badge bg-danger">Critical</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?= e($ur['urgency']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-dark border"><?= e($ur['status']) ?></span>
                                        </td>
                                        <td><?= e($ur['required_date']) ?></td>
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
