<div class="row gy-4 mb-4">
    <!-- Welcome Header & Status Banner -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-danger">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center">
                    <span class="badge-blood badge-blood-<?= substr($donor['blood_group'], 0, 1) ?> fs-4 me-3 px-3 py-2">
                        <?= e($donor['blood_group']) ?>
                    </span>
                    <div>
                        <h3 class="fw-bold mb-1">Hello, <?= e($donor['full_name']) ?>!</h3>
                        <div class="text-muted small">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= e($donor['city']) ?> • Registered: <?= e($donor['registration_date']) ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Availability Badge -->
                    <?php if ($donor['availability_status'] === 'AVAILABLE'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Available for Emergency Call
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-dash-circle-fill me-1"></i> Temporarily Unavailable
                        </span>
                    <?php endif; ?>

                    <!-- Eligibility Badge -->
                    <?php if ($donor['eligibility_status'] === 'ELIGIBLE' && (!$donor['next_eligible_date'] || strtotime($donor['next_eligible_date']) <= time())): ?>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-heart-pulse-fill me-1"></i> Medically Eligible to Donate
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill">
                            <i class="bi bi-hourglass-split me-1"></i> Waiting Period Active
                        </span>
                    <?php endif; ?>

                    <a href="<?= url('/donor/profile') ?>" class="btn btn-sm btn-outline-secondary">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards & Gamification Tier -->
    <div class="col-md-4">
        <div class="card card-bloodlink h-100 p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted fw-semibold small">Recognition Level</span>
                <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                    <i class="bi bi-trophy-fill me-1"></i> <?= e($currentTier['level_name'] ?? 'Novice') ?>
                </span>
            </div>
            <div class="fs-4 fw-bold text-dark mb-1">
                <?= (int)$stats['total_donations'] ?> Successful Donation(s)
            </div>
            <p class="small text-muted mb-3">
                Total Blood Donated: <strong><?= number_format((float)$stats['total_volume_ml'], 0) ?> mL</strong>
            </p>
            
            <?php if ($nextTier): ?>
                <?php 
                $needed = (int)$nextTier['minimum_donations'];
                $curr = (int)$stats['total_donations'];
                $pct = min(100, round(($curr / $needed) * 100));
                ?>
                <div class="small fw-semibold text-muted d-flex justify-content-between mb-1">
                    <span>Progress to <?= e($nextTier['level_name']) ?> Tier</span>
                    <span><?= $curr ?> / <?= $needed ?></span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-danger" style="width: <?= $pct ?>%"></div>
                </div>
                <small class="text-muted mt-1 d-block"><?= ($needed - $curr) ?> more donation(s) to unlock <?= e($nextTier['level_name']) ?></small>
            <?php else: ?>
                <div class="badge bg-success-subtle text-success border border-success-subtle w-100 py-2">
                    <i class="bi bi-award-fill me-1"></i> Highest Tier Achieved!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-bloodlink h-100 p-3">
            <span class="text-muted fw-semibold small mb-2">Next Eligible Donation Date</span>
            <div class="fs-4 fw-bold text-danger mb-1">
                <?php if ($donor['next_eligible_date']): ?>
                    <?= e($donor['next_eligible_date']) ?>
                <?php else: ?>
                    Ready to Donate
                <?php endif; ?>
            </div>
            <p class="small text-muted mb-0">
                <?php 
                if ($donor['next_eligible_date']) {
                    $diff = (int)ceil((strtotime($donor['next_eligible_date']) - time()) / 86400);
                    if ($diff > 0) {
                        echo "<i class='bi bi-clock me-1 text-warning'></i> {$diff} day(s) remaining for standard recovery interval.";
                    } else {
                        echo "<i class='bi bi-check2-circle text-success me-1'></i> You are eligible to donate today!";
                    }
                } else {
                    echo "<i class='bi bi-check2-circle text-success me-1'></i> No prior donations recorded. You can donate immediately!";
                }
                ?>
            </p>
            <div class="mt-3">
                <small class="text-muted">Standard safety interval: <strong>90 days</strong> between whole blood collections.</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-bloodlink h-100 p-3">
            <span class="text-muted fw-semibold small mb-2">Emergency Sourcing Status</span>
            <div class="fs-5 fw-bold text-dark mb-1">
                <?= count($pendingMatches) ?> Pending Match Call(s)
            </div>
            <p class="small text-muted mb-3">
                Hospitals facing acute blood shortages notify compatible donors directly via the system.
            </p>
            <a href="<?= url('/donor/notifications') ?>" class="btn btn-sm btn-outline-danger w-100">
                <i class="bi bi-bell me-1"></i> View All Notifications
            </a>
        </div>
    </div>

    <!-- Active Emergency Match Invitations -->
    <?php if (!empty($pendingMatches)): ?>
        <div class="col-12">
            <div class="card card-bloodlink border-warning shadow-sm">
                <div class="card-header bg-warning-subtle text-warning-emphasis py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Urgent: Compatible Emergency Blood Request</span>
                    <span class="badge bg-warning text-dark"><?= count($pendingMatches) ?> Active Request(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hospital</th>
                                    <th>Location</th>
                                    <th>Needed By</th>
                                    <th>Match Reason</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingMatches as $pm): ?>
                                    <tr>
                                        <td class="fw-bold text-dark"><?= e($pm['hospital_name']) ?></td>
                                        <td><i class="bi bi-geo-alt text-muted me-1"></i><?= e($pm['hospital_city']) ?></td>
                                        <td><span class="badge bg-danger-subtle text-danger"><?= e($pm['required_date']) ?></span></td>
                                        <td class="small text-muted" style="max-width: 320px;"><?= e($pm['match_reason']) ?></td>
                                        <td class="text-end">
                                            <form action="<?= url('/donor/match/respond') ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="match_id" value="<?= (int)$pm['match_id'] ?>">
                                                <button type="submit" name="action" value="ACCEPT" class="btn btn-sm btn-success fw-bold me-1">
                                                    <i class="bi bi-check-lg"></i> Accept
                                                </button>
                                                <button type="submit" name="action" value="DECLINE" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-x-lg"></i> Decline
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

    <!-- Recent Donations Log -->
    <div class="col-12">
        <div class="card card-bloodlink">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-danger"></i> Recent Donation Records</h5>
                <a href="<?= url('/donor/history') ?>" class="btn btn-sm btn-outline-secondary">Full History</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>Component</th>
                                <th>Volume (mL)</th>
                                <th>Screening</th>
                                <th>Status</th>
                                <th>Clinical Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentDonations)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-droplet fs-3 d-block mb-1 text-secondary"></i>
                                        No donation records yet. Schedule your first donation with an authorized blood bank!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentDonations as $don): ?>
                                    <tr>
                                        <td><?= e($don['donation_date']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($don['donation_type']) ?></span></td>
                                        <td class="fw-bold"><?= number_format((float)$don['quantity_ml'], 0) ?> mL</td>
                                        <td>
                                            <?php if ($don['screening_status'] === 'PASSED'): ?>
                                                <span class="badge bg-success-subtle text-success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning"><?= e($don['screening_status']) ?></span>
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
