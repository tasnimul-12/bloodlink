<div class="hero-gradient mb-5 text-white">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge bg-light text-danger fw-bold px-3 py-2 rounded-pill text-uppercase mb-3">
                <i class="bi bi-heart-pulse me-1"></i> Blood donation coordination
            </span>
            <h1 class="display-4 fw-extrabold mb-3">
                Every Second Counts.<br>Every Drop Connects.
            </h1>
            <p class="lead mb-4 opacity-90" style="max-width: 650px;">
                <strong>BloodLink</strong> helps donors, hospitals, and blood bank teams coordinate donations, urgent requests, and available blood products.
            </p>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= url('/login') ?>" class="btn btn-light btn-lg text-danger fw-bold shadow-sm px-4">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Access Portals
                </a>
                <a href="<?= url('/register/donor') ?>" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-heart-fill me-1"></i> Become a Donor
                </a>
                <a href="<?= url('/donation-events') ?>" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-calendar-heart me-1"></i> Find a Donation Event
                </a>
                <a href="<?= url('/compatibility') ?>" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-diagram-3 me-1"></i> Compatibility Matrix
                </a>
            </div>
        </div>
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="hero-stats">
                <h5 class="fw-bold mb-3 border-bottom border-light border-opacity-25 pb-2">
                    <i class="bi bi-activity me-2"></i> BloodLink at a glance
                </h5>
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="p-2 rounded bg-black bg-opacity-25">
                            <div class="fs-3 fw-bold"><?= (int)$stats['total_donors'] ?></div>
                            <div class="small opacity-75">Registered Donors</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-black bg-opacity-25">
                            <div class="fs-3 fw-bold"><?= (int)$stats['total_units'] ?></div>
                            <div class="small opacity-75">Available Units</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-black bg-opacity-25">
                            <div class="fs-3 fw-bold"><?= (int)$stats['total_hospitals'] ?></div>
                            <div class="small opacity-75">Active Hospitals</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 rounded bg-black bg-opacity-25">
                            <div class="fs-3 fw-bold"><?= (int)$stats['total_donations'] ?></div>
                            <div class="small opacity-75">Completed Donations</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BloodLink Services -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card card-bloodlink h-100 p-3">
            <div class="kpi-icon bg-danger-subtle text-danger mb-3">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <h5 class="fw-bold mb-2">Managed inventory</h5>
            <p class="text-muted small mb-0">
                Track blood products by group, component, location, and expiry date so the earliest-expiring stock is allocated first.
            </p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bloodlink h-100 p-3">
            <div class="kpi-icon bg-primary-subtle text-primary mb-3">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h5 class="fw-bold mb-2">Reliable fulfillment</h5>
            <p class="text-muted small mb-0">
                Request fulfillment updates stock and request progress together, helping prevent the same unit from being issued twice.
            </p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bloodlink h-100 p-3">
            <div class="kpi-icon bg-warning-subtle text-warning mb-3">
                <i class="bi bi-bell-fill"></i>
            </div>
            <h5 class="fw-bold mb-2">Donor matching</h5>
            <p class="text-muted small mb-0">
                Hospitals can notify eligible, compatible donors when additional donations are needed.
            </p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-bloodlink h-100 p-3">
            <div class="kpi-icon bg-success-subtle text-success mb-3">
                <i class="bi bi-journal-check"></i>
            </div>
            <h5 class="fw-bold mb-2">Clear activity records</h5>
            <p class="text-muted small mb-0">
                Donation, request, and inventory activity is recorded for authorized teams to review.
            </p>
        </div>
    </div>
</div>

<!-- Regional Inventory Overview -->
<div class="card card-bloodlink mb-5">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-box-seam me-2"></i> Available blood by group and component</h5>
            <small class="text-muted">Current available stock across participating storage locations</small>
        </div>
        <a href="<?= url('/login') ?>" class="btn btn-sm btn-outline-danger">Request Units</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Blood Group</th>
                        <th>Component</th>
                        <th>Available Units</th>
                        <th>Total Volume (mL)</th>
                        <th>Earliest Expiry</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventorySummary)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No available inventory records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventorySummary as $row): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $grpClass = 'badge-blood-A';
                                    if (str_contains($row['group_name'], 'B')) $grpClass = 'badge-blood-B';
                                    if (str_contains($row['group_name'], 'AB')) $grpClass = 'badge-blood-AB';
                                    if (str_contains($row['group_name'], 'O')) $grpClass = 'badge-blood-O';
                                    ?>
                                    <span class="badge-blood <?= $grpClass ?>"><?= e($row['group_name']) ?></span>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-dark border"><?= e($row['component_type'] ?? 'WHOLE_BLOOD') ?></span></td>
                                <td class="fw-bold fs-6"><?= (int)$row['total_units'] ?> bag(s)</td>
                                <td><?= number_format((float)$row['total_ml'], 2) ?> mL</td>
                                <td>
                                    <?php if ($row['earliest_expiry']): ?>
                                        <span class="text-danger fw-semibold"><i class="bi bi-calendar-event me-1"></i><?= e($row['earliest_expiry']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$row['total_units'] > 0): ?>
                                        <span class="badge badge-status badge-status-available">In Stock</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-status-discarded">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Service Notice -->
<div class="alert alert-light border shadow-sm p-4 d-flex align-items-center">
    <div class="fs-1 text-danger me-4"><i class="bi bi-info-circle"></i></div>
    <div>
        <h5 class="fw-bold mb-1">Service notice</h5>
        <p class="text-muted small mb-0">
            BloodLink supports coordination and inventory tracking. Clinical screening, compatibility decisions, and transfusion procedures must always be handled by qualified medical professionals.
        </p>
    </div>
</div>
