<div class="row justify-content-center py-4">
    <div class="col-md-8">
        <div class="card card-bloodlink shadow-sm p-4">
            <div class="text-center mb-4">
                <span class="brand-icon mx-auto mb-2" style="width:48px; height:48px; font-size:1.4rem;">
                    <i class="bi bi-hospital"></i>
                </span>
                <h4 class="fw-bold mb-1">Hospital Staff Portal Registration</h4>
                <p class="text-muted small">Register as an authorized healthcare or transfusion officer</p>
            </div>

            <form action="<?= url('/register/hospital') ?>" method="POST">
                <?= csrf_field() ?>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">1. Staff User Credentials</h6>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label fw-semibold">Staff Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="e.g. dr_arman">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label fw-semibold">Official Work Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="officer@hospital.com">
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label fw-semibold">Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required placeholder="Minimum 6 characters">
                            <button class="btn btn-outline-secondary" type="button" data-password-toggle aria-controls="password" aria-label="Show password" title="Show password">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Contact Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="017xxxxxxxx">
                    </div>
                    <div class="col-md-6">
                        <label for="staff_name" class="form-label fw-semibold">Staff Member Full Name *</label>
                        <input type="text" class="form-control" id="staff_name" name="staff_name" required placeholder="e.g. Dr. Arman Hossain">
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label fw-semibold">Clinical Designation</label>
                        <input type="text" class="form-control" id="designation" name="designation" placeholder="e.g. Blood Bank Officer / Surgeon">
                    </div>
                </div>

                <h6 class="fw-bold text-primary border-bottom pb-2 mb-3 mt-4">2. Associated Hospital / Healthcare Facility</h6>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Select Healthcare Institution</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="hosp_choice" id="choice_existing" value="existing" checked onchange="toggleHospForm()">
                        <label class="form-check-label" for="choice_existing">
                            Associate with an existing approved hospital
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="hosp_choice" id="choice_new" value="new" onchange="toggleHospForm()">
                        <label class="form-check-label" for="choice_new">
                            Register a new hospital facility (requires Administrator verification)
                        </label>
                    </div>
                </div>

                <!-- Existing Hospital Select -->
                <div id="section_existing" class="mb-4">
                    <label for="hospital_id" class="form-label fw-semibold">Select Hospital *</label>
                    <select class="form-select" id="hospital_id" name="hospital_id">
                        <option value="">-- Choose Approved Hospital --</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= (int)$h['hospital_id'] ?>"><?= e($h['hospital_name']) ?> (<?= e($h['city']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- New Hospital Inputs -->
                <div id="section_new" class="mb-4 border p-3 rounded bg-light" style="display: none;">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-building-add me-1"></i> New Hospital Facility Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Hospital Name *</label>
                            <input type="text" class="form-control form-control-sm" name="new_hospital_name" placeholder="e.g. City General Hospital">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Registration / License Number *</label>
                            <input type="text" class="form-control form-control-sm" name="new_registration_number" placeholder="REG-HOSP-XXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">City *</label>
                            <input type="text" class="form-control form-control-sm" name="new_city" placeholder="e.g. Dhaka">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Facility Phone</label>
                            <input type="text" class="form-control form-control-sm" name="new_phone" placeholder="Landline / Hotline">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Full Address</label>
                            <input type="text" class="form-control form-control-sm" name="new_address" placeholder="Road, Ward, District">
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border small text-muted mb-4">
                    <i class="bi bi-shield-lock me-1"></i>
                    New hospital accounts are registered in <code>PENDING</code> status and must be validated and activated by the system administrator before blood requests and inventory reserves can be fulfilled.
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                    <i class="bi bi-hospital me-1"></i> Submit Hospital Staff Registration
                </button>
            </form>

            <div class="text-center mt-3 small text-muted">
                Already registered? <a href="<?= url('/login') ?>" class="text-primary fw-semibold">Sign In</a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleHospForm() {
    const isNew = document.getElementById('choice_new').checked;
    document.getElementById('section_existing').style.display = isNew ? 'none' : 'block';
    document.getElementById('section_new').style.display = isNew ? 'block' : 'none';
}
</script>
