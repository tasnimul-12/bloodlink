<div class="card card-bloodlink shadow-sm">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-secondary me-2"></i> System Audit & Compliance Trail</h5>
            <small class="text-muted">Immutable log of mutations, fulfillments, transfers, and security events</small>
        </div>
        <span class="badge bg-secondary-subtle text-dark border px-3 py-2">
            Showing <?= count($logs) ?> Recent Audit Records
        </span>
    </div>

    <!-- Filter Bar -->
    <div class="p-3 bg-light border-bottom">
        <form action="<?= url('/admin/audit-logs') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-md-5">
                <select class="form-select form-select-sm" name="action">
                    <option value="">-- All Audited Actions --</option>
                    <?php foreach ($actions as $act): ?>
                        <option value="<?= e($act) ?>" <?= (!empty($filters['action']) && $filters['action'] === $act) ? 'selected' : '' ?>>
                            <?= e($act) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-sm" name="entity">
                    <option value="">-- All Entities --</option>
                    <?php foreach ($entities as $ent): ?>
                        <option value="<?= e($ent) ?>" <?= (!empty($filters['entity']) && $filters['entity'] === $ent) ? 'selected' : '' ?>>
                            <?= e($ent) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter Logs</button>
                <a href="<?= url('/admin/audit-logs') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead class="table-light font-sans-serif">
                    <tr>
                        <th>Log ID</th>
                        <th>Timestamp</th>
                        <th>User Actor</th>
                        <th>Action</th>
                        <th>Target Entity</th>
                        <th>IP Address</th>
                        <th>State Payload (JSON)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No audit logs match criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>#AUD-<?= $log['audit_id'] ?></td>
                                <td><?= e($log['created_at']) ?></td>
                                <td class="fw-bold text-dark">
                                    <?= e($log['username'] ?? 'SYSTEM_TRIGGER') ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($log['action']) ?></span>
                                </td>
                                <td>
                                    <code><?= e($log['entity_name']) ?>:<?= e($log['entity_id']) ?></code>
                                </td>
                                <td class="text-muted"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                                <td>
                                    <?php if ($log['old_value'] || $log['new_value']): ?>
                                        <button type="button" class="btn btn-xs btn-outline-dark py-0 px-2" data-bs-toggle="modal" data-bs-target="#auditModal<?= $log['audit_id'] ?>">
                                            <i class="bi bi-code-slash me-1"></i> View Diff
                                        </button>

                                        <!-- Diff Modal -->
                                        <div class="modal fade" id="auditModal<?= $log['audit_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content font-sans-serif">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title fw-bold">Audit Event #<?= $log['audit_id'] ?> — <?= e($log['action']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <h6 class="fw-bold text-muted small text-uppercase">Previous State (Old Value)</h6>
                                                                <pre class="bg-light p-3 rounded small border mb-0" style="max-height: 250px; overflow-y:auto;"><?= e(json_encode(json_decode($log['old_value'] ?? 'null'), JSON_PRETTY_PRINT)) ?></pre>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6 class="fw-bold text-muted small text-uppercase">Mutated State (New Value)</h6>
                                                                <pre class="bg-light p-3 rounded small border mb-0" style="max-height: 250px; overflow-y:auto;"><?= e(json_encode(json_decode($log['new_value'] ?? 'null'), JSON_PRETTY_PRINT)) ?></pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
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
