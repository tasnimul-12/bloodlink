<?php
/**
 * Test: Admin accession records a screened donation and available inventory bag.
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/InventoryService.php';

$pdo = Database::getConnection();
$donorStmt = $pdo->prepare("
    SELECT d.donor_id, d.eligibility_status, d.next_eligible_date, u.account_status
    FROM donors d
    JOIN users u ON u.user_id = d.user_id
    WHERE u.username = 'zero_donor'
    LIMIT 1
");
$donorStmt->execute();
$donor = $donorStmt->fetch();
$adminId = (int)$pdo->query("SELECT user_id FROM users WHERE username = 'admin' LIMIT 1")->fetchColumn();
$locationId = (int)$pdo->query("
    SELECT storage_location_id
    FROM storage_locations
    WHERE storage_type = 'REFRIGERATOR' AND location_status = 'ACTIVE'
    ORDER BY storage_location_id
    LIMIT 1
")->fetchColumn();

assert($donor && $donor['account_status'] === 'ACTIVE', 'The active zero_donor test fixture must exist.');
assert($adminId > 0, 'The seeded admin account must exist.');
assert($locationId > 0, 'An active refrigerator storage location must exist.');

$donorId = (int)$donor['donor_id'];
$previousEligibility = $donor['eligibility_status'];
$previousNextEligibleDate = $donor['next_eligible_date'];
$recognitionStmt = $pdo->prepare('SELECT recognition_level_id FROM donor_recognition WHERE donor_id = :donor_id');
$recognitionStmt->execute([':donor_id' => $donorId]);
$previousRecognition = array_map('intval', $recognitionStmt->fetchAll(PDO::FETCH_COLUMN));
$invalidResult = InventoryService::accessionAdminDonation([
    'donor_id' => $donorId,
    'component_type' => 'WHOLE_BLOOD',
    'collection_date' => date('Y-m-d'),
    'quantity_ml' => 450,
    'storage_location_id' => $locationId
], $adminId);
assert($invalidResult['success'] === false, 'Accession without screening confirmation must be rejected.');

$result = InventoryService::accessionAdminDonation([
    'donor_id' => $donorId,
    'component_type' => 'WHOLE_BLOOD',
    'collection_date' => date('Y-m-d'),
    'quantity_ml' => 450,
    'storage_location_id' => $locationId,
    'screening_confirmed' => '1'
], $adminId);
assert($result['success'] === true, 'Admin accession failed: ' . ($result['message'] ?? ''));
$bagNumber = $result['bag_number'] ?? '';
assert((bool)preg_match('/^BL-WB-' . date('Ymd') . '-[A-F0-9]{12}$/', $bagNumber), 'Accession must generate a component/date-based bag number.');

$bagStmt = $pdo->prepare("
    SELECT bb.*, don.screening_status, don.donation_status
    FROM blood_bags bb
    JOIN donations don ON don.donation_id = bb.donation_id
    WHERE bb.bag_number = :bag_number
");
$bagStmt->execute([':bag_number' => $bagNumber]);
$bag = $bagStmt->fetch();
assert($bag, 'Accession should create a blood bag.');
assert($bag['status'] === 'AVAILABLE', 'Newly accessioned blood must be available.');
assert($bag['screening_status'] === 'PASSED' && $bag['donation_status'] === 'COMPLETED', 'Accession must record passed screening and a completed donation.');
assert((int)$bag['blood_group_id'] > 0, 'The bag must use the donor blood group.');
assert($bag['expiry_date'] === date('Y-m-d', strtotime('+35 days')), 'Whole blood expiry must follow its 35-day shelf life.');

$movementStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_movements WHERE blood_bag_id = :bag_id AND movement_type = 'RECEIVED'");
$movementStmt->execute([':bag_id' => $bag['blood_bag_id']]);
assert((int)$movementStmt->fetchColumn() === 1, 'Accession must record one inventory receipt movement.');

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM inventory_movements WHERE blood_bag_id = :bag_id')->execute([':bag_id' => $bag['blood_bag_id']]);
    $pdo->prepare("DELETE FROM audit_logs WHERE action = 'ADMIN_ACCESSION_DONATION' AND entity_id = :bag_id")
        ->execute([':bag_id' => (string)$bag['blood_bag_id']]);
    $pdo->prepare('DELETE FROM blood_bags WHERE blood_bag_id = :bag_id')->execute([':bag_id' => $bag['blood_bag_id']]);
    $pdo->prepare('DELETE FROM donations WHERE donation_id = :donation_id')->execute([':donation_id' => $bag['donation_id']]);

    if ($previousRecognition) {
        $placeholders = implode(',', array_fill(0, count($previousRecognition), '?'));
        $deleteRecognition = $pdo->prepare("DELETE FROM donor_recognition WHERE donor_id = ? AND recognition_level_id NOT IN ({$placeholders})");
        $deleteRecognition->execute([$donorId, ...$previousRecognition]);
    } else {
        $pdo->prepare('DELETE FROM donor_recognition WHERE donor_id = :donor_id')->execute([':donor_id' => $donorId]);
    }

    $pdo->prepare('UPDATE donors SET eligibility_status = :status, next_eligible_date = :next_date WHERE donor_id = :donor_id')
        ->execute([
            ':status' => $previousEligibility,
            ':next_date' => $previousNextEligibleDate,
            ':donor_id' => $donorId
        ]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

echo "PASS: Admin accession creates an available screened bag, applies component expiry, and records receipt movement.\n";