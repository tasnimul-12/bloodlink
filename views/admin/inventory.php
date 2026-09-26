<div class="row gy-4 mb-4">
    <!-- Header -->
    <div class="col-12">
        <div class="card card-bloodlink p-4 border-start border-4 border-danger">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill mb-2">
                        <i class="bi bi-hourglass-split me-1"></i> First-Expire, First-Out (FEFO) Engine
                    </span>
                    <h3 class="fw-bold mb-1">Central Blood Bank Inventory Console</h3>
                    <div class="text-muted small">
                        Automated unit-level tracking, cold chain monitoring, inter-facility transfers, and biological decommissioning.
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= url('/admin/donations') ?>" class="btn btn-danger btn-sm px-3 py-2 fw-bold">
                        <i class="bi bi-plus-circle me-1"></i> Accession New Unit
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="col-12">
        <div class="card card-bloodlink p-3 bg-light">
            <form action="<?= url('/admin/inventory') ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search Bag # or Location..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="blood_group_id">
                        <option value="">-- All Blood Groups --</option>
                        <?php foreach ($bloodGroups as $bg): ?>
                            <option value="<?= (int)$bg['blood_group_id'] ?>" <?= (!empty($filters['blood_group_id']) && (int)$filters['blood_group_id'] === (int)$bg['blood_group_id']) ? 'selected' : '' ?>>
                                <?= e($bg['group_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" name="component_type">
                        <option value="">-- All Components --</option>
                        <option value="WHOLE_BLOOD" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'WHOLE_BLOOD') ? 'selected' : '' ?>>Whole Blood</option>
                        <option value="RBC" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'RBC') ? 'selected' : '' ?>>RBC (Red Cells)</option>
                        <option value="PLASMA" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'PLASMA') ? 'selected' : '' ?>>Plasma</option>
                        <option value="PLATELET" <?= (!empty($filters['component_type']) && $filters['component_type'] === 'PLATELET') ? 'selected' : '' ?>>Platelet</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="storage_location_id">
                        <option value="">-- All Storage Locations --</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= (int)$loc['storage_location_id'] ?>" <?= (!empty($filters['storage_location_id']) && (int)$filters['storage_location_id'] === (int)$loc['storage_location_id']) ? 'selected' : '' ?>>
                                <?= e($loc['location_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                    <a href="<?= url('/admin/inventory') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Available Inventory Table (Strict FEFO Sorting) -->
    <div class="col-12">
        <div class="card card-bloodlink shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-boxes text-danger me-2"></i> Active Stock (Sorted by Earliest Expiry — FEFO)</h5>
                    <small class="text-muted">Rows ranked automatically for immediate clinical allocation</small>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                    <?= count($availableBags) ?> Active Unit(s)
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>FEFO Rank</th>
                                <th>Bag Identifier</th>
                                <th>Blood Group</th>
                                <th>Component</th>
                                <th>Volume</th>
                                <th>Collected On</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                                <th>Storage Location</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($availableBags)): ?>
                                <tr><td colspan="10" class="text-center py-5 text-muted">No available blood units in storage matching criteria.</td></tr>
                            <?php else: ?>
                                <?php $rank = 1; foreach ($availableBags as $bag): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-dark border">#<?= $rank++ ?></span></td>
                                        <td><code><?= e($bag['bag_number']) ?></code></td>
                                        <td><span class="badge-blood badge-blood-<?= substr($bag['blood_group'], 0, 1) ?>"><?= e($bag['blood_group']) ?></span></td>
                                        <td><span class="badge bg-light text-dark border"><?= e($bag['component_type']) ?></span></td>
                                        <td class="fw-bold"><?= number_format((float)$bag['quantity_ml'], 0) ?> mL</td>
                                        <td class="small text-muted"><?= e($bag['collection_date']) ?></td>
                                        <td class="fw-bold text-dark"><?= e($bag['expiry_date']) ?></td>
                                        <td>
                                            <?php if ((int)$bag['days_to_expiry'] <= 3): ?>
                                                <span class="badge bg-danger"><i class="bi bi-fire me-1"></i><?= $bag['days_to_expiry'] ?> day(s)</span>
                                            <?php elseif ((int)$bag['days_to_expiry'] <= 7): ?>
                                                <span class="badge bg-warning text-dark"><?= $bag['days_to_expiry'] ?> day(s)</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border"><?= $bag['days_to_expiry'] ?> day(s)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><i class="bi bi-snow text-primary me-1"></i><?= e($bag['location_name']) ?></td>
                                        <td class="text-end">
                                            <!-- Transfer Action Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#transferModal"
                                                data-bag-id="<?= (int)$bag['blood_bag_id'] ?>"
                                                data-bag-number="<?= e($bag['bag_number']) ?>"
                                                data-location-id="<?= (int)$bag['storage_location_id'] ?>"
                                                data-location-name="<?= e($bag['location_name']) ?>"
                                                title="Transfer Storage Location">
                                                <i class="bi bi-arrow-left-right"></i> Transfer
                                            </button>

                                            <!-- Discard Action Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#discardModal"
                                                data-bag-id="<?= (int)$bag['blood_bag_id'] ?>"
                                                data-bag-number="<?= e($bag['bag_number']) ?>"
                                                title="Decommission Biological Unit">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <div class="modal fade" id="discardModal" tabindex="-1" aria-labelledby="discardModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/inventory/discard') ?>" method="POST" class="modal-content border-0 shadow">
                <?= csrf_field() ?>
                <input type="hidden" name="blood_bag_id" id="discardBagId">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="discardModalTitle">Decommission blood unit</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger fw-semibold mb-2">Mark blood bag <strong id="discardBagNumber"></strong> as DISCARDED?</p>
                    <p class="small text-muted mb-3">This updates the unit status, records an inventory movement, and removes the unit from FEFO allocation.</p>
                    <label class="form-label fw-semibold" for="discardReason">Clinical decommission reason <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="discardReason" name="reason" required maxlength="255" placeholder="Expiry, hemolysis, or bag breach">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger fw-semibold">Confirm Decommission</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shared Transfer Modal: keep modal markup outside the table for valid browser parsing -->
    <div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form action="<?= url('/admin/inventory/transfer') ?>" method="POST" class="modal-content border-0 shadow">
                <?= csrf_field() ?>
                <input type="hidden" name="blood_bag_id" id="transferBagId">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="text-primary small fw-semibold text-uppercase">Inventory movement</div>
                        <h5 class="modal-title fw-bold mt-1" id="transferModalTitle">Transfer blood bag</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="rounded-3 bg-light border p-3 mb-3">
                        <label for="transferCurrentLocation" class="form-label small fw-semibold text-muted mb-1">Current storage location</label>
                        <input type="text" class="form-control bg-white" id="transferCurrentLocation" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="transferDestination">Destination location <span class="text-danger">*</span></label>
                        <select class="form-select" id="transferDestination" name="to_location_id" required>
                            <option value="">Choose a destination</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= (int)$loc['storage_location_id'] ?>"><?= e($loc['location_name']) ?> (<?= e($loc['storage_type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold" for="transferReason">Reason for transfer</label>
                        <input type="text" class="form-control" id="transferReason" name="reason" maxlength="255" placeholder="Temperature regulation or routine reallocation">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-arrow-left-right me-1"></i> Execute Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('discardModal').addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            document.getElementById('discardBagId').value = trigger.dataset.bagId;
            document.getElementById('discardBagNumber').textContent = trigger.dataset.bagNumber;
            document.getElementById('discardReason').value = '';
        });

        document.getElementById('transferModal').addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const bagId = trigger.dataset.bagId;
            const currentLocationId = trigger.dataset.locationId;
            const destination = document.getElementById('transferDestination');

            document.getElementById('transferBagId').value = bagId;
            document.getElementById('transferModalTitle').textContent = 'Transfer blood bag ' + trigger.dataset.bagNumber;
            document.getElementById('transferCurrentLocation').value = trigger.dataset.locationName;
            destination.value = '';

            Array.from(destination.options).forEach(function (option) {
                option.disabled = option.value === currentLocationId;
            });
        });
    </script>

    <!-- Expired Units Log -->
    <?php if (!empty($expiredBags)): ?>
        <div class="col-12">
            <div class="card card-bloodlink border-secondary shadow-sm">
                <div class="card-header bg-secondary text-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-x-octagon me-2"></i> Expired & Decommissioned Biological Units Log</h6>
                    <span class="badge bg-light text-dark"><?= count($expiredBags) ?> Unit(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bag Identifier</th>
                                    <th>Blood Group</th>
                                    <th>Component</th>
                                    <th>Volume</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Decommission Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiredBags as $exp): ?>
                                    <tr>
                                        <td><code><?= e($exp['bag_number']) ?></code></td>
                                        <td><span class="badge-blood badge-blood-<?= substr($exp['blood_group'], 0, 1) ?> py-0 px-2"><?= e($exp['blood_group']) ?></span></td>
                                        <td><?= e($exp['component_type']) ?></td>
                                        <td><?= number_format((float)$exp['quantity_ml'], 0) ?> mL</td>
                                        <td><?= e($exp['expiry_date']) ?> (<?= $exp['days_expired'] ?> days ago)</td>
                                        <td><span class="badge badge-status badge-status-expired"><?= e($exp['status']) ?></span></td>
                                        <td>
                                            <?php if ($exp['status'] !== 'DISCARDED'): ?>
                                                <form action="<?= url('/admin/inventory/discard') ?>" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="blood_bag_id" value="<?= (int)$exp['blood_bag_id'] ?>">
                                                    <input type="hidden" name="reason" value="Unit expired past shelf life">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Decommission expired unit?')">
                                                        Decommission
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted">Safely Discarded</small>
                                            <?php endif; ?>
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
</div>
