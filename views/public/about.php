<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="mb-4">
            <h2 class="fw-bold"><i class="bi bi-heart-pulse text-danger me-2"></i> About BloodLink</h2>
            <p class="text-muted">
                BloodLink brings donors, hospitals, and blood bank teams together to coordinate donation needs and available blood products.
            </p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-bloodlink h-100 p-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-people me-2"></i> For donors and hospitals</h5>
                    <p class="small text-muted mb-2">
                        Donors can manage their profile, respond to compatible requests, and review donation history. Hospital teams can submit requests, coordinate donors, and follow request progress.
                    </p>
                    <a href="<?= url('/compatibility') ?>" class="btn btn-sm btn-outline-danger mt-2">Check blood compatibility</a>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-bloodlink h-100 p-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-shield-check me-2"></i> Careful stock handling</h5>
                    <p class="small text-muted mb-2">
                        Blood products are tracked by component, location, and expiry. Allocation prioritizes earlier expiry dates and records stock movements for staff review.
                    </p>
                    <a href="<?= url('/login') ?>" class="btn btn-sm btn-outline-primary mt-2">Access your portal</a>
                </div>
            </div>
        </div>

        <div class="card card-bloodlink p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clipboard2-pulse text-danger me-2"></i> A coordinated response</h5>
            <div class="row g-3">
                <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><i class="bi bi-heart-pulse text-danger me-2"></i><strong>Donors</strong><p class="small text-muted mb-0 mt-2">Receive compatible requests and manage donation responses.</p></div></div>
                <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><i class="bi bi-hospital text-primary me-2"></i><strong>Hospitals</strong><p class="small text-muted mb-0 mt-2">Coordinate urgent needs and follow fulfillment progress.</p></div></div>
                <div class="col-md-4"><div class="p-3 border rounded bg-light h-100"><i class="bi bi-box-seam text-danger me-2"></i><strong>Blood bank teams</strong><p class="small text-muted mb-0 mt-2">Track available products, expiry, and inventory movements.</p></div></div>
            </div>
        </div>
    </div>
</div>
