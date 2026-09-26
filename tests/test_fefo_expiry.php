<?php
/**
 * Test: FEFO Sorting and Expiry Exclusion
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/services/InventoryService.php';

echo "--- RUNNING TEST: FEFO Sorting & Expiry Exclusion ---\n";

$available = InventoryService::getAvailableInventory();

echo "Total available units returned: " . count($available) . "\n";
assert(count($available) > 0, "Available inventory should not be empty");

$prevExpiry = '1970-01-01';
foreach ($available as $bag) {
    echo "  Bag: {$bag['bag_number']} | Expiry: {$bag['expiry_date']} | Status: {$bag['status']}\n";
    
    // Assert strictly non-expired
    assert(strtotime($bag['expiry_date']) >= strtotime(date('Y-m-d')), "Expired bag found in available inventory!");
    
    // Assert status is AVAILABLE
    assert($bag['status'] === 'AVAILABLE', "Non-available bag found in available inventory!");
    
    // Assert chronological FEFO ordering
    assert(strtotime($bag['expiry_date']) >= strtotime($prevExpiry), "FEFO order violation: bag expiry earlier than previous!");
    $prevExpiry = $bag['expiry_date'];
}

echo ">>> PASS: FEFO sorting and expiry exclusion verified successfully!\n\n";
