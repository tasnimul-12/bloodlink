<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam-fill text-success me-2"></i> Received Blood Fulfillments & Dispatches</h5>
            <small class="text-muted">Cold-chain logistics and issued blood lots for <?= e($staff['hospital_name']) ?></small>
        </div>
        <a href="<?= url('/hospital/dashboard') ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fulfillment ID</th>
                        <th>Associated Request</th>
                        <th>Requisition Type</th>
                        <th>Total Units</th>
                        <th>Total Volume</th>
                        <th>Issued At</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fulfillments)): ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No completed fulfillments on record yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fulfillments as $f): ?>
                            <tr>
                                <td><code>#FUL-<?= $f['fulfillment_id'] ?></code></td>
                                <td><a href="<?= url('/hospital/requests/view/' . $f['request_id']) ?>" class="fw-bold text-decoration-none">#REQ-<?= $f['request_id'] ?></a></td>
                                <td><span class="badge bg-light text-dark border"><?= e($f['request_type']) ?></span></td>
                                <td class="fw-semibold"><?= (int)$f['total_items_count'] ?> unit(s)</td>
                                <td class="fw-bold text-success"><?= number_format((float)$f['total_issued_ml'], 0) ?> mL</td>
                                <td><?= e($f['created_at']) ?></td>
                                <td><span class="badge badge-status badge-status-available"><?= e($f['fulfillment_status']) ?></span></td>
                                <td class="text-end">
                                    <a href="<?= url('/hospital/requests/view/' . $f['request_id']) ?>" class="btn btn-sm btn-outline-secondary">
                                        View Requisition <i class="bi bi-chevron-right"></i>
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
