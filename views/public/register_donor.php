<div class="row justify-content-center py-4">
    <div class="col-md-7">
        <div class="card card-bloodlink shadow-sm p-4">
            <div class="text-center mb-4">
                <span class="brand-icon mx-auto mb-2" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-heart-fill"></i>
                </span>
                <h4 class="fw-bold mb-1">Donor Registration</h4>
                <p class="text-muted small">Join our network of lifesaving voluntary blood donors</p>
            </div>

            <form action="<?= url('/register/donor') ?>" method="POST">
                <?= csrf_field() ?>

                <h6 class="fw-bold text-danger border-bottom pb-2 mb-3">1. Account Information</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-semibold">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="e.g. john_donor">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="john@example.com">
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required placeholder="Minimum 6 characters">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Contact Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="017xxxxxxxx">
                    </div>
                </div>

                <h6 class="fw-bold text-danger border-bottom pb-2 mb-3 mt-4">2. Medical & Personal Details</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label fw-semibold">Full Legal Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required placeholder="e.g. Rahim Ahmed">
                    </div>
                    <div class="col-md-6">
                        <label for="blood_group_id" class="form-label fw-semibold">Blood Group *</label>
                        <select class="form-select" id="blood_group_id" name="blood_group_id" required>
                            <option value="">-- Select Blood Group --</option>
                            <?php foreach ($bloodGroups as $bg): ?>
                                <option value="<?= (int)$bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?> (ABO: <?= e($bg['abo_type']) ?>, Rh: <?= e($bg['rh_factor']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label fw-semibold">Date of Birth *</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                        <small class="text-muted">Must be at least 18 years old</small>
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label fw-semibold">Gender *</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="MALE">Male</option>
                            <option value="FEMALE">Female</option>
                            <option value="OTHER">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="city" class="form-label fw-semibold">City / Region *</label>
                        <input type="text" class="form-control" id="city" name="city" required placeholder="e.g. Dhaka">
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label fw-semibold">Local Address</label>
                        <input type="text" class="form-control" id="address" name="address" placeholder="e.g. Dhanmondi, Road 7">
                    </div>
                </div>

                <div class="alert alert-light border small text-muted mb-4">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    <strong>Privacy Guarantee:</strong> Your phone number and exact address will remain strictly confidential and will never be displayed in public inventory searches.
                </div>

                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold">
                    <i class="bi bi-heart-fill me-1"></i> Register as Voluntary Donor
                </button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Already registered? <a href="<?= url('/login') ?>" class="text-danger fw-semibold">Sign In</a>
            </div>
        </div>
    </div>
</div>
