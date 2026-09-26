<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="mb-4">
            <h2 class="fw-bold"><i class="bi bi-cpu text-danger me-2"></i> System Architecture & DBMS Concepts</h2>
            <p class="text-muted">
                BloodLink is designed as an advanced relational database management systems (DBMS) university capstone project, demonstrating normalization, referential integrity, ACID transactional locking, and automated auditing.
            </p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-bloodlink h-100 p-4">
                    <h5 class="fw-bold text-danger mb-3"><i class="bi bi-diagram-2 me-2"></i> 20-Table Relational Schema</h5>
                    <p class="small text-muted mb-2">
                        Normalized strictly to Third Normal Form (3NF) to avoid update, insertion, and deletion anomalies. Major entities include:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li><code>roles</code>, <code>users</code>, <code>blood_groups</code>, <code>donors</code></li>
                        <li><code>hospitals</code>, <code>hospital_staff</code>, <code>donations</code></li>
                        <li><code>storage_locations</code>, <code>blood_bags</code>, <code>inventory_movements</code></li>
                        <li><code>blood_requests</code>, <code>request_items</code>, <code>donor_matches</code></li>
                        <li><code>notifications</code>, <code>fulfillments</code>, <code>fulfillment_items</code></li>
                        <li><code>recognition_levels</code>, <code>donor_recognition</code>, <code>audit_logs</code>, <code>system_settings</code></li>
                    </ul>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-bloodlink h-100 p-4">
                    <h5 class="fw-bold text-primary mb-3"><i class="bi bi-shield-lock me-2"></i> ACID Concurrency & Transactions</h5>
                    <p class="small text-muted mb-2">
                        Double-allocation prevention is enforced at the database layer using MySQL InnoDB engine features:
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li><strong>Pessimistic Locking:</strong> <code>SELECT ... FOR UPDATE</code> locks blood bag rows during fulfillment.</li>
                        <li><strong>Atomicity:</strong> Multiple operations (fulfillment insertion, bag status change to ISSUED, request progress update, movement logging) succeed together or execute <code>ROLLBACK</code>.</li>
                        <li><strong>Audit Trigger:</strong> MySQL trigger <code>trg_blood_bag_status_audit</code> tracks status mutations.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Project Team -->
        <div class="card card-bloodlink p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-people-fill text-danger me-2"></i> Project Team & Submission Details</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="fw-bold text-dark">Tasnimul Hasan</div>
                        <div class="small text-muted">ID: 0112410414</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="fw-bold text-dark">Mst. Sobrun Jamil</div>
                        <div class="small text-muted">ID: 0112430536</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 border rounded bg-light">
                        <div class="fw-bold text-dark">Md Fahim Ashhab</div>
                        <div class="small text-muted">ID: 0112230975</div>
                    </div>
                </div>
            </div>
            <div class="mt-3 small text-muted">
                Course: Database Management Systems (DBMS) • Technology: HTML5, CSS3, JavaScript, PHP 8.2, MySQL 8.x
            </div>
        </div>
    </div>
</div>
