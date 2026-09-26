<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-bloodlink shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-sliders text-danger me-2"></i> System Configuration & Parameters</h4>
                    <p class="text-muted small mb-0">Manage global thresholds in the <code>system_settings</code> relational table</p>
                </div>
                <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
            </div>

            <form action="<?= url('/admin/settings') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="mb-4">
                    <?php foreach ($settings as $s): ?>
                        <div class="card p-3 mb-3 bg-light border">
                            <label class="form-label fw-bold text-dark mb-1">
                                <code><?= e($s['setting_key']) ?></code>
                            </label>
                            <p class="text-muted small mb-2"><?= e($s['description']) ?></p>
                            <input type="text" class="form-control" name="settings[<?= e($s['setting_key']) ?>]" value="<?= e($s['setting_value']) ?>" required>
                            <?php if ($s['updated_at']): ?>
                                <small class="text-muted mt-1 d-block">Last updated: <?= e($s['updated_at']) ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="alert alert-light border small text-muted mb-4">
                    <i class="bi bi-info-circle text-primary me-1"></i>
                    Updates to these parameters take effect immediately for donor eligibility intervals and FEFO priority sorting.
                </div>

                <button type="submit" class="btn btn-danger px-4 py-2 fw-bold">
                    <i class="bi bi-save me-1"></i> Update System Settings
                </button>
            </form>
        </div>
    </div>
</div>
