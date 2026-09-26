<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-bloodlink shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-3">
                <div>
                    <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-plus text-danger me-2"></i> Submit Hospital Blood Request</h4>
                    <p class="text-muted small mb-0">Create an official blood requisition order for <?= e($staff['hospital_name']) ?></p>
                </div>
                <a href="<?= url('/hospital/requests') ?>" class="btn btn-sm btn-outline-secondary">All Requests</a>
            </div>

            <form action="<?= url('/hospital/requests/create') ?>" method="POST" id="requestForm">
                <?= csrf_field() ?>

                <h6 class="fw-bold text-danger mb-3">1. Clinical Requisition Parameters</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="request_type" class="form-label fw-semibold">Request Type *</label>
                        <select class="form-select" id="request_type" name="request_type" required>
                            <option value="EMERGENCY">EMERGENCY (Critical Care / Trauma)</option>
                            <option value="SURGERY">SURGERY (Scheduled Operative Case)</option>
                            <option value="ROUTINE">ROUTINE (Standard Transfusion)</option>
                            <option value="MATERNITY">MATERNITY (Obstetric Hemorrhage)</option>
                            <option value="OTHER">OTHER (Clinical / Hematology)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="urgency" class="form-label fw-semibold">Urgency Classification *</label>
                        <select class="form-select" id="urgency" name="urgency" required>
                            <option value="CRITICAL">CRITICAL (Immediate Need / STAT)</option>
                            <option value="HIGH" selected>HIGH (Required within 6 Hours)</option>
                            <option value="MEDIUM">MEDIUM (Within 24 Hours)</option>
                            <option value="LOW">LOW (Elective / Advance Schedule)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="required_date" class="form-label fw-semibold">Required Date *</label>
                        <input type="date" class="form-control" id="required_date" name="required_date" required min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="required_time" class="form-label fw-semibold">Required By Time</label>
                        <input type="time" class="form-control" id="required_time" name="required_time">
                    </div>
                    <div class="col-12">
                        <label for="reason" class="form-label fw-semibold">Clinical Reason / Patient Indication *</label>
                        <textarea class="form-control" id="reason" name="reason" rows="2" required placeholder="e.g. Acute internal hemorrhage following motor vehicle trauma; hemoglobin 6.2 g/dL."></textarea>
                    </div>
                    <div class="col-12">
                        <label for="special_notes" class="form-label fw-semibold">Special Instructions / Crossmatch Notes</label>
                        <input type="text" class="form-control" id="special_notes" name="special_notes" placeholder="e.g. Leukoreduced units requested if available.">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-danger mb-0">2. Blood Components & Quantities (Line Items)</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="addItemRow()">
                        <i class="bi bi-plus-circle me-1"></i> Add Another Item
                    </button>
                </div>
                <p class="text-muted small mb-3">Standard whole blood bag contains approximately 450 mL. Platelets: ~200 mL. Plasma: ~250 mL.</p>

                <div id="itemsContainer">
                    <div class="row g-2 mb-2 item-row align-items-center border p-2 rounded bg-light">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Blood Group *</label>
                            <select class="form-select form-select-sm" name="blood_group_id[]" required>
                                <option value="">-- Select Group --</option>
                                <?php foreach ($bloodGroups as $bg): ?>
                                    <option value="<?= (int)$bg['blood_group_id'] ?>"><?= e($bg['group_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Component Type *</label>
                            <select class="form-select form-select-sm" name="component_type[]" required>
                                <option value="WHOLE_BLOOD">WHOLE_BLOOD (~450 mL)</option>
                                <option value="RBC">RBC (Packed Red Cells ~300 mL)</option>
                                <option value="PLASMA">PLASMA (Fresh Frozen ~250 mL)</option>
                                <option value="PLATELET">PLATELET (Agitated ~200 mL)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Required Volume (mL) *</label>
                            <input type="number" step="50" class="form-control form-control-sm" name="quantity_requested[]" required value="450">
                        </div>
                        <div class="col-md-1 text-center pt-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary disabled" title="Primary item row"><i class="bi bi-dash"></i></button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <button type="submit" class="btn btn-danger px-4 py-2 fw-bold">
                        <i class="bi bi-send-fill me-1"></i> Submit Blood Requisition
                    </button>
                    <a href="<?= url('/hospital/requests') ?>" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function addItemRow() {
    const container = document.getElementById('itemsContainer');
    const firstRow = container.querySelector('.item-row');
    const clone = firstRow.cloneNode(true);
    
    // Clear inputs and enable remove button
    const removeBtn = clone.querySelector('button');
    removeBtn.classList.remove('disabled');
    removeBtn.classList.replace('btn-outline-secondary', 'btn-outline-danger');
    removeBtn.onclick = function() { clone.remove(); };
    removeBtn.title = "Remove item";

    container.appendChild(clone);
}
</script>
