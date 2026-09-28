<section class="donation-hero mb-5">
    <div class="donation-hero-copy">
        <div class="donation-eyebrow"><i class="bi bi-heart-pulse-fill"></i> BloodLink community</div>
        <h1>Give someone<br><span>more tomorrows.</span></h1>
        <p>Blood donation is a generous choice with real purpose. When you choose to give, trained healthcare teams screen and handle each donation so it can support patient care.</p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light fw-bold px-4 py-2" href="<?= url('/register/donor') ?>"><i class="bi bi-droplet-fill me-1"></i> Become a donor</a>
            <a class="btn btn-outline-light px-4 py-2" href="#upcoming-events">See upcoming events <i class="bi bi-arrow-down ms-1"></i></a>
        </div>
    </div>
    <div class="donation-hero-art" aria-hidden="true">
        <div class="donation-mark"><i class="bi bi-droplet-fill"></i></div>
        <div class="donation-art-note">A little of your time.<br>A meaningful act of care.</div>
        <div class="donation-art-number">01 <span>/ GIVE</span></div>
    </div>
</section>

<section class="donation-values mb-5" aria-label="About blood donation">
    <div class="donation-value">
        <span class="donation-value-icon"><i class="bi bi-people-fill"></i></span>
        <div><h2>Care is shared</h2><p>Every voluntary donor helps strengthen the community's ability to care for people who need blood.</p></div>
    </div>
    <div class="donation-value">
        <span class="donation-value-icon"><i class="bi bi-shield-check"></i></span>
        <div><h2>People come first</h2><p>Donation teams explain the process, answer questions, and assess suitability before a collection.</p></div>
    </div>
    <div class="donation-value">
        <span class="donation-value-icon"><i class="bi bi-heart-fill"></i></span>
        <div><h2>Your choice matters</h2><p>Read the event details, learn about donation, and decide for yourself whether you would like to take part.</p></div>
    </div>
</section>

<section id="upcoming-events" class="public-events-section">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
        <div>
            <div class="text-danger small fw-bold text-uppercase">Meet us in your community</div>
            <h2 class="fw-bold mb-0">Donation events</h2>
        </div>
        <span class="text-muted small">Event information is public. No RSVP or approval is required on this page.</span>
    </div>

    <?php if (empty($events)): ?>
        <div class="event-empty">
            <i class="bi bi-calendar2-heart" aria-hidden="true"></i>
            <div><h3>No upcoming events just yet</h3><p class="mb-0">Please check again soon. You can still learn about becoming a donor through your local healthcare team.</p></div>
        </div>
    <?php else: ?>
        <div class="event-list">
            <?php foreach ($events as $event): ?>
                <?php $eventDate = new DateTimeImmutable($event['event_date']); ?>
                <article class="event-row <?= $event['event_status'] === 'CANCELLED' ? 'event-row-cancelled' : '' ?>">
                    <div class="event-date-block">
                        <span><?= e($eventDate->format('M')) ?></span>
                        <strong><?= e($eventDate->format('d')) ?></strong>
                        <small><?= e($eventDate->format('D')) ?></small>
                    </div>
                    <div class="event-row-main">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h3><?= e($event['title']) ?></h3>
                            <?php if ($event['event_status'] === 'CANCELLED'): ?><span class="badge text-bg-secondary">Cancelled</span><?php endif; ?>
                        </div>
                        <p class="event-invitation">“<?= e($event['invitation']) ?>”</p>
                        <p class="event-description"><?= nl2br(e($event['description'])) ?></p>
                        <div class="event-details">
                            <span><i class="bi bi-clock"></i><?= e($eventDate->format('g:i A')) ?></span>
                            <span><i class="bi bi-geo-alt-fill"></i><?= e($event['venue']) ?>, <?= e($event['city']) ?></span>
                            <span><i class="bi bi-pin-map"></i><?= e($event['address']) ?></span>
                            <?php if (!empty($event['contact_info'])): ?><span><i class="bi bi-telephone"></i><?= e($event['contact_info']) ?></span><?php endif; ?>
                        </div>
                    </div>
                    <?php if ($event['event_status'] === 'SCHEDULED'): ?>
                        <a class="event-join-link" href="<?= url('/register/donor') ?>" aria-label="Register as a donor">
                            <span>I'm interested</span><i class="bi bi-arrow-up-right"></i>
                        </a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<p class="donation-disclaimer mt-4"><i class="bi bi-info-circle me-1"></i> Donation suitability is assessed by qualified healthcare professionals. Event information does not replace medical advice or screening.</p>