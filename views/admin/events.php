<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <div class="text-danger small fw-bold text-uppercase">Community engagement</div>
        <h2 class="fw-bold mb-1">Donation events</h2>
        <p class="text-muted mb-0">Publish clear event details for anyone to read. No RSVP or approval is collected here.</p>
    </div>
    <a href="<?= url('/donation-events') ?>" class="btn btn-outline-danger" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i> Public page</a>
</div>

<section class="card card-bloodlink p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-calendar-plus text-danger me-2"></i>Schedule an event</h5>
    <form action="<?= url('/admin/events/create') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <label for="event_title" class="form-label fw-semibold">Event name *</label>
                <input class="form-control" id="event_title" name="title" maxlength="140" required placeholder="Community Blood Donation Day">
            </div>
            <div class="col-lg-6">
                <label for="invitation" class="form-label fw-semibold">Short invitation *</label>
                <input class="form-control" id="invitation" name="invitation" maxlength="180" required placeholder="A small act can make a life-changing difference">
            </div>
            <div class="col-12">
                <label for="description" class="form-label fw-semibold">Event details and donor information *</label>
                <textarea class="form-control" id="description" name="description" rows="4" maxlength="10000" required placeholder="What visitors should know about the event, who is hosting it, and what to expect."></textarea>
            </div>
            <div class="col-md-4">
                <label for="event_date" class="form-label fw-semibold">Date and time *</label>
                <input class="form-control" id="event_date" name="event_date" type="datetime-local" min="<?= date('Y-m-d\TH:i') ?>" required>
            </div>
            <div class="col-md-4">
                <label for="venue" class="form-label fw-semibold">Venue *</label>
                <input class="form-control" id="venue" name="venue" maxlength="180" required>
            </div>
            <div class="col-md-4">
                <label for="city" class="form-label fw-semibold">City *</label>
                <input class="form-control" id="city" name="city" maxlength="80" required>
            </div>
            <div class="col-md-8">
                <label for="address" class="form-label fw-semibold">Street address *</label>
                <input class="form-control" id="address" name="address" maxlength="255" required>
            </div>
            <div class="col-md-4">
                <label for="contact_info" class="form-label fw-semibold">Contact details</label>
                <input class="form-control" id="contact_info" name="contact_info" maxlength="160" placeholder="Phone or email">
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-danger fw-semibold" type="submit"><i class="bi bi-megaphone me-1"></i> Publish event</button>
            </div>
        </div>
    </form>
</section>

<section class="card card-bloodlink">
    <div class="card-header bg-white p-3"><h5 class="fw-bold mb-0">Scheduled and past events</h5></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Event</th><th>Date and time</th><th>Location</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php if (empty($events)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No events have been scheduled yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($event['title']) ?><div class="small text-muted">#<?= (int)$event['event_id'] ?></div></td>
                            <td><?= e(date('M j, Y · g:i A', strtotime($event['event_date']))) ?></td>
                            <td><?= e($event['venue']) ?><div class="small text-muted"><?= e($event['city']) ?></div></td>
                            <td><span class="badge <?= $event['event_status'] === 'SCHEDULED' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($event['event_status']) ?></span></td>
                            <td class="text-end">
                                <?php if ($event['event_status'] === 'SCHEDULED' && strtotime($event['event_date']) >= time()): ?>
                                    <form action="<?= url('/admin/events/cancel/' . (int)$event['event_id']) ?>" method="POST" onsubmit="return confirm('Cancel this event? It will remain visible as cancelled until its scheduled time.')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Cancel event</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>