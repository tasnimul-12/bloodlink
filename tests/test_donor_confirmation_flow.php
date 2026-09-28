<?php
/**
 * Test: donor acceptance creates a pending donation for hospital confirmation
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/MatchingService.php';
require_once __DIR__ . '/../app/services/DonationService.php';

$pdo = Database::getConnection();
$matchId = 1;
$donorId = 2;
$hospitalId = 3;
$staffUserId = 4;
$collectionDate = date('Y-m-d\TH:i');

$response = MatchingService::respondToMatch($matchId, $donorId, 'ACCEPT');
assert($response['success'] === true, 'Donor acceptance failed: ' . ($response['message'] ?? ''));

$donationStmt = $pdo->prepare('SELECT donation_id, donation_status, screening_status FROM donations WHERE donor_match_id = :match_id');
$donationStmt->execute([':match_id' => $matchId]);
$pendingDonation = $donationStmt->fetch();
assert($pendingDonation && $pendingDonation['donation_status'] === 'SCHEDULED', 'Acceptance did not create a scheduled donation.');
assert($pendingDonation['screening_status'] === 'PENDING', 'New donation should await screening.');

$siblingMatchId = 105;
$pdo->prepare("UPDATE donor_matches SET match_status = 'NOTIFIED', response_at = NULL WHERE match_id = :match_id")
    ->execute([':match_id' => $siblingMatchId]);
$secondAcceptance = MatchingService::respondToMatch($siblingMatchId, $donorId, 'ACCEPT');
assert($secondAcceptance['success'] === false, 'A donor with a scheduled donation must not accept another invitation.');

$pdo->prepare("UPDATE donor_matches SET match_status = 'ACCEPTED', response_at = NOW() WHERE match_id = :match_id")
    ->execute([':match_id' => $siblingMatchId]);
$siblingInsert = $pdo->prepare("
    INSERT INTO donations (
        donor_id, donor_match_id, donation_type, donation_date, quantity_ml,
        screening_status, donation_status, notes, created_at
    ) VALUES (
        :donor_id, :match_id, 'WHOLE_BLOOD', NOW(), 450,
        'PENDING', 'SCHEDULED', 'Legacy duplicate acceptance test fixture.', NOW()
    )
");
$siblingInsert->execute([':donor_id' => $donorId, ':match_id' => $siblingMatchId]);
$siblingDonationId = (int)$pdo->lastInsertId();

$staffNotificationStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND related_match_id = :match_id AND notification_type = 'REQUEST_UPDATE'");
$staffNotificationStmt->execute([':user_id' => $staffUserId, ':match_id' => $matchId]);
assert((int)$staffNotificationStmt->fetchColumn() > 0, 'The related hospital staff must be notified after donor acceptance.');

$cancelMatchStmt = $pdo->prepare("
    INSERT INTO donor_matches (
        request_item_id, donor_id, match_score, match_reason, match_status, notified_at, response_at, created_at
    ) VALUES (101, :donor_id, 90, 'Cancellation workflow test fixture.', 'ACCEPTED', NOW(), NOW(), NOW())
");
$cancelMatchStmt->execute([':donor_id' => $donorId]);
$cancelMatchId = (int)$pdo->lastInsertId();
$cancelDonationStmt = $pdo->prepare("
    INSERT INTO donations (
        donor_id, donor_match_id, donation_type, donation_date, quantity_ml,
        screening_status, donation_status, notes, created_at
    ) VALUES (
        :donor_id, :match_id, 'WHOLE_BLOOD', NOW(), 450,
        'PENDING', 'SCHEDULED', 'Cancellation workflow test fixture.', NOW()
    )
");
$cancelDonationStmt->execute([':donor_id' => $donorId, ':match_id' => $cancelMatchId]);
$cancelDonationId = (int)$pdo->lastInsertId();
$cancelResult = DonationService::cancelMatchedDonation($cancelDonationId, $staffUserId, 1);
assert($cancelResult['success'] === true, 'Authorized hospital staff must be able to cancel a scheduled donation.');
$cancelledDonationStmt = $pdo->prepare('SELECT donation_status FROM donations WHERE donation_id = :id');
$cancelledDonationStmt->execute([':id' => $cancelDonationId]);
assert($cancelledDonationStmt->fetchColumn() === 'CANCELLED', 'Cancelled donation must no longer be scheduled.');
$cancelledMatchStmt = $pdo->prepare('SELECT match_status FROM donor_matches WHERE match_id = :id');
$cancelledMatchStmt->execute([':id' => $cancelMatchId]);
assert($cancelledMatchStmt->fetchColumn() === 'CANCELLED', 'Cancelling a donation must close its donor match.');
$cancelNoticeStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = (SELECT user_id FROM donors WHERE donor_id = :donor_id) AND related_match_id = :match_id AND title = 'Donation cancelled'");
$cancelNoticeStmt->execute([':donor_id' => $donorId, ':match_id' => $cancelMatchId]);
assert((int)$cancelNoticeStmt->fetchColumn() > 0, 'The donor must be notified when staff cancels a scheduled donation.');

$confirmationData = [
    'donation_type' => 'WHOLE_BLOOD',
    'quantity_ml' => 450,
    'donation_date' => $collectionDate
];
$wrongHospital = DonationService::confirmMatchedDonation(
    (int)$pendingDonation['donation_id'],
    2,
    1,
    $confirmationData
);
assert($wrongHospital['success'] === false, 'Another hospital must not confirm this donation.');

$confirmation = DonationService::confirmMatchedDonation(
    (int)$pendingDonation['donation_id'],
    $staffUserId,
    $hospitalId,
    $confirmationData
);
assert($confirmation['success'] === true, 'Hospital confirmation failed: ' . ($confirmation['message'] ?? ''));

$confirmedStmt = $pdo->prepare('SELECT donation_status, screening_status FROM donations WHERE donation_id = :id');
$confirmedStmt->execute([':id' => $pendingDonation['donation_id']]);
$confirmedDonation = $confirmedStmt->fetch();
assert($confirmedDonation['donation_status'] === 'COMPLETED', 'Confirmed donation must be completed.');
assert($confirmedDonation['screening_status'] === 'PASSED', 'Confirmed donation must record passed screening.');

$siblingStatusStmt = $pdo->prepare('SELECT donation_status FROM donations WHERE donation_id = :id');
$siblingStatusStmt->execute([':id' => $siblingDonationId]);
assert($siblingStatusStmt->fetchColumn() === 'CANCELLED', 'Confirming one donation must cancel the donor\'s other scheduled donation.');
$siblingMatchStmt = $pdo->prepare('SELECT match_status FROM donor_matches WHERE match_id = :id');
$siblingMatchStmt->execute([':id' => $siblingMatchId]);
assert($siblingMatchStmt->fetchColumn() === 'CANCELLED', 'Confirming one donation must close the donor\'s other active invitations.');

$bagStmt = $pdo->prepare("SELECT COUNT(*) FROM blood_bags WHERE donation_id = :donation_id AND status IN ('AVAILABLE', 'ISSUED')");
$bagStmt->execute([':donation_id' => $pendingDonation['donation_id']]);
assert((int)$bagStmt->fetchColumn() === 1, 'Confirmed donation must be accessioned as a blood bag.');

$requestProgressStmt = $pdo->prepare("
    SELECT ri.quantity_requested, ri.quantity_fulfilled, br.status
    FROM request_items ri
    JOIN blood_requests br ON br.request_id = ri.request_id
    WHERE ri.request_id = 3
    LIMIT 1
");
$requestProgressStmt->execute();
$requestProgress = $requestProgressStmt->fetch();
assert((float)$requestProgress['quantity_fulfilled'] > 0, 'Confirmed donation should trigger FEFO request fulfillment.');
assert($requestProgress['status'] === 'FULFILLED', 'Request status should reflect successful FEFO allocation.');

$remainingMatchesStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM donor_matches dm
        JOIN request_items ri ON ri.request_item_id = dm.request_item_id
        LEFT JOIN donations don ON don.donor_match_id = dm.match_id
        WHERE ri.request_id = 3
            AND (dm.match_status IN ('NOTIFIED', 'SUGGESTED')
                     OR (dm.match_status = 'ACCEPTED' AND don.donation_status = 'SCHEDULED'))
");
$remainingMatchesStmt->execute();
assert((int)$remainingMatchesStmt->fetchColumn() === 0, 'Unneeded invitations and scheduled donations must close when the request is fulfilled.');

$nadiaDonationStmt = $pdo->prepare('SELECT donation_status FROM donations WHERE donor_match_id = 101');
$nadiaDonationStmt->execute();
assert($nadiaDonationStmt->fetchColumn() === 'CANCELLED', 'Another accepted donor must be told not to proceed after the request is fulfilled.');

$collectedStmt = $pdo->prepare("
        SELECT COALESCE(SUM(don.quantity_ml), 0)
        FROM donations don
        JOIN donor_matches dm ON dm.match_id = don.donor_match_id
        WHERE dm.request_item_id = 3
            AND don.donation_status = 'COMPLETED'
            AND don.screening_status = 'PASSED'
");
$collectedStmt->execute();
assert((float)$collectedStmt->fetchColumn() === 450.0, 'The request detail donor-confirmed volume must update after completion.');

$donorNotificationStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = 6 AND notification_type = 'ELIGIBILITY_UPDATE' AND title = 'Donation confirmed'");
$donorNotificationStmt->execute();
assert((int)$donorNotificationStmt->fetchColumn() > 0, 'The donor must be notified after hospital confirmation.');

$eligibilityStmt = $pdo->prepare('SELECT eligibility_status, next_eligible_date FROM donors WHERE donor_id = :donor_id');
$eligibilityStmt->execute([':donor_id' => $donorId]);
$eligibility = $eligibilityStmt->fetch();
assert($eligibility['eligibility_status'] === 'NOT_ELIGIBLE', 'Successful donation must update eligibility.');
assert(!empty($eligibility['next_eligible_date']), 'Successful donation must set the next eligible date.');

$duplicateConfirmation = DonationService::confirmMatchedDonation(
    (int)$pendingDonation['donation_id'],
    $staffUserId,
    $hospitalId,
    $confirmationData
);
assert($duplicateConfirmation['success'] === false, 'A completed donation must not be confirmable twice.');

printf("PASS: donor/staff notifications, pending donation, authorized confirmation, inventory accession, FEFO fulfillment, and donor eligibility update.\n");
