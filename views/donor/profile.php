<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-bloodlink p-4 shadow-sm">
            <h4 class="fw-bold mb-1"><i class="bi bi-person-badge text-danger me-2"></i> Donor Profile & Settings</h4>
            <p class="text-muted small mb-4">Manage your contact location and emergency alert preferences</p>

            <form action="<?= url('/donor/profile') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small">Full Legal Name</label>
                    <input type="text" class="form-control bg-light" value="<?= e($donor['full_name']) ?>" disabled>
                    <small class="text-muted">Legal names are locked to maintain medical screening records.</small>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">Blood Group</label>
                        <input type="text" class="form-control bg-light fw-bold text-danger" value="<?= e($donor['blood_group']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">Date of Birth</label>
                        <input type="text" class="form-control bg-light" value="<?= e($donor['date_of_birth']) ?>" disabled>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="city" class="form-label fw-semibold">City / Region *</label>
                        <input type="text" class="form-control" id="city" name="city" required value="<?= e($donor['city']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label fw-semibold">Local Address</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?= e($donor['address']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="availability_status" class="form-label fw-semibold">Emergency Donor Availability</label>
                    <select class="form-select" id="availability_status" name="availability_status">
                        <option value="AVAILABLE" <?= $donor['availability_status'] === 'AVAILABLE' ? 'selected' : '' ?>>
                            AVAILABLE (Accept in-system emergency alerts when blood is critically needed)
                        </option>
                        <option value="UNAVAILABLE" <?= $donor['availability_status'] === 'UNAVAILABLE' ? 'selected' : '' ?>>
                            UNAVAILABLE (Do not notify for emergency sourcing calls)
                        </option>
                    </select>
                </div>

                <button type="submit" class="btn btn-danger px-4 py-2 fw-bold">
                    <i class="bi bi-save me-1"></i> Save Changes
                </button>
                <a href="<?= url('/donor/dashboard') ?>" class="btn btn-outline-secondary px-4 py-2">Back to Dashboard</a>
            </form>
        </div>
    </div>
</div>
