<div class="row justify-content-center py-4">
    <div class="col-md-5">
        <div class="card card-bloodlink shadow-sm p-4">
            <div class="text-center mb-4">
                <span class="brand-icon mx-auto mb-2" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-droplet-fill"></i>
                </span>
                <h4 class="fw-bold mb-1">Sign In to BloodLink</h4>
                <p class="text-muted small">Enter your credentials to access your role-based portal</p>
            </div>

            <form action="<?= url('/login') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="login" class="form-label fw-semibold">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="login" name="login" required placeholder="admin or email@example.com">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="btn btn-danger w-100 py-2 fw-bold mt-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                </button>
            </form>

            <!-- Quick Demo Credentials -->
            <div class="mt-4 pt-3 border-top">
                <div class="small fw-bold text-muted text-uppercase mb-2 text-center">
                    <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo Accounts (Click to Fill)
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-1">
                    <button type="button" class="btn btn-outline-danger btn-demo-badge" onclick="fillCreds('admin', 'Admin@123')">
                        <i class="bi bi-shield-lock me-1"></i> Admin
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-demo-badge" onclick="fillCreds('square_staff', 'Hospital@123')">
                        <i class="bi bi-hospital me-1"></i> Approved Hospital
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-demo-badge" onclick="fillCreds('dmc_staff', 'Hospital@123')">
                        <i class="bi bi-hourglass-split me-1"></i> Pending Hospital
                    </button>
                    <button type="button" class="btn btn-outline-success btn-demo-badge" onclick="fillCreds('rahim_donor', 'Donor@123')">
                        <i class="bi bi-heart me-1"></i> Donor (Active)
                    </button>
                    <button type="button" class="btn btn-outline-dark btn-demo-badge" onclick="fillCreds('zero_donor', 'Donor@123')">
                        <i class="bi bi-person-x me-1"></i> Donor (0 History)
                    </button>
                </div>
            </div>

            <div class="text-center mt-4 small text-muted">
                Need an account? 
                <a href="<?= url('/register/donor') ?>" class="text-danger fw-semibold text-decoration-none">Register as Donor</a> or 
                <a href="<?= url('/register/hospital') ?>" class="text-primary fw-semibold text-decoration-none">Hospital Staff</a>
            </div>
        </div>
    </div>
</div>

<script>
function fillCreds(u, p) {
    document.getElementById('login').value = u;
    document.getElementById('password').value = p;
}
</script>
