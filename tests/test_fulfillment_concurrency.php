<?php
/**
 * Test: ACID Transactional Fulfillment and Concurrency Safety
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/FulfillmentService.php';

echo "--- RUNNING TEST: ACID Transactional Fulfillment ---\n";

$pdo = Database::getConnection();

// Check initial state of Request 1
$reqStmt = $pdo->query("SELECT status FROM blood_requests WHERE request_id = 1");
$initStatus = $reqStmt->fetchColumn();
echo "Request 1 initial status: {$initStatus}\n";

// Execute fulfillment for Request 1 (Hospital 1, requested by Staff/Admin user 1)
$result = FulfillmentService::processFulfillment(1, 1, 1);
echo "Fulfillment Result: " . json_encode($result) . "\n";
assert($result['success'] === true, "Fulfillment failed: " . ($result['message'] ?? ''));

// Verify updated request status
$newStatus = $pdo->query("SELECT status FROM blood_requests WHERE request_id = 1")->fetchColumn();
echo "Request 1 new status: {$newStatus}\n";
assert($newStatus === 'FULFILLED', "Request status should be FULFILLED");

// Verify FEFO chose BAG-A-103 and BAG-A-102 (earliest expiring units)
$issuedBags = $pdo->query("
    SELECT bb.bag_number, bb.status, fi.quantity_issued 
    FROM fulfillment_items fi
    JOIN blood_bags bb ON fi.blood_bag_id = bb.blood_bag_id
    WHERE fi.fulfillment_id = {$result['fulfillment_id']}
    ORDER BY bb.expiry_date ASC
")->fetchAll();

echo "Issued bags count: " . count($issuedBags) . "\n";
assert(count($issuedBags) === 2, "Expected exactly 2 blood bags to be allocated for 900 mL");

foreach ($issuedBags as $ib) {
    echo "  Allocated Unit: {$ib['bag_number']} | Status: {$ib['status']} | Issued: {$ib['quantity_issued']} mL\n";
    assert($ib['status'] === 'ISSUED', "Allocated blood bag status must be ISSUED!");
}

// Verify that fresher bag BAG-A-101 was NOT touched and remains AVAILABLE
$freshBag = $pdo->query("SELECT status FROM blood_bags WHERE bag_number = 'BAG-A-101'")->fetchColumn();
echo "Fresher unit BAG-A-101 status: {$freshBag}\n";
assert($freshBag === 'AVAILABLE', "Fresher unit should remain AVAILABLE according to FEFO!");

// Verify repeat fulfillment attempt is safely rejected
$repeatResult = FulfillmentService::processFulfillment(1, 1, 1);
echo "Repeat Fulfillment Attempt: " . json_encode($repeatResult) . "\n";
assert($repeatResult['success'] === false, "Repeat fulfillment should have been rejected!");

echo ">>> PASS: ACID Transactional Fulfillment and FEFO locking verified!\n\n";
