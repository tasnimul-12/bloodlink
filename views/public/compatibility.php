<div class="mb-4">
    <h2 class="fw-bold"><i class="bi bi-diagram-3 text-danger me-2"></i> Blood Group & Component Compatibility</h2>
    <p class="text-muted">
        Blood compatibility differs significantly depending on whether the transfused component contains red blood cells (erythrocytes) or blood plasma.
    </p>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-8">
        <div class="card card-bloodlink">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-table me-2"></i> Component Compatibility Matrix</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Recipient Group</th>
                                <th>Whole Blood & RBC Compatible Donors</th>
                                <th>Plasma Compatible Donors</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($matrix as $row): ?>
                                <tr>
                                    <td>
                                        <span class="badge-blood badge-blood-<?= substr($row['name'], 0, 1) ?>"><?= e($row['name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-primary fw-medium"><?= e($row['rbc_donors']) ?></span>
                                    </td>
                                    <td>
                                        <span class="text-success fw-medium"><?= e($row['plasma_donors']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-bloodlink mb-4 p-3 border-danger-subtle bg-danger-subtle bg-opacity-10">
            <h6 class="fw-bold text-danger"><i class="bi bi-star-fill me-1"></i> Universal Donors & Recipients</h6>
            <hr class="my-2">
            <div class="mb-3">
                <div class="fw-bold text-dark">Universal RBC Donor: O Negative (O-)</div>
                <small class="text-muted">O- red blood cells lack A, B, and Rh antigens, allowing safe emergency administration to virtually any recipient group.</small>
            </div>
            <div class="mb-3">
                <div class="fw-bold text-dark">Universal RBC Recipient: AB Positive (AB+)</div>
                <small class="text-muted">AB+ individuals have both A and B antigens and Rh factor; they can receive RBCs from all blood groups.</small>
            </div>
            <div>
                <div class="fw-bold text-dark">Universal Plasma Donor: AB Groups</div>
                <small class="text-muted">AB plasma contains neither anti-A nor anti-B antibodies, making it universally safe for all plasma recipients.</small>
            </div>
        </div>

        <div class="card card-bloodlink p-3">
            <h6 class="fw-bold text-dark"><i class="bi bi-info-circle me-1"></i> Engine Implementation</h6>
            <p class="small text-muted mb-0">
                The BloodLink algorithm in <code>app/services/CompatibilityService.php</code> calculates compatible donor IDs dynamically during inventory allocation and emergency sourcing.
            </p>
        </div>
    </div>
</div>
