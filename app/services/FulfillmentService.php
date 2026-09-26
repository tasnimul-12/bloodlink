<?php
/**
 * BloodLink - ACID Transactional Fulfillment Engine
 * 
 * Demonstrates:
 * - BEGIN, COMMIT, ROLLBACK
 * - Row-level locking (SELECT ... FOR UPDATE)
 * - Atomic multi-table updates (blood_bags, request_items, blood_requests, fulfillments, fulfillment_items, inventory_movements, audit_logs)
 * - Concurrency race-condition protection (double-allocation prevention)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CompatibilityService.php';
require_once __DIR__ . '/AuditService.php';

class FulfillmentService {
    /**
     * Executes atomic FEFO fulfillment for a blood request
     */
    public static function processFulfillment(int $requestId, int $actorUserId, ?int $hospitalIdConstraint = null): array {
        $pdo = Database::getConnection();

        // 1. Begin ACID Transaction
        $pdo->beginTransaction();

        try {
            // 2. Validate request & lock request row to prevent concurrent fulfillment of same request
            $reqSql = "
                SELECT br.request_id, br.hospital_id, br.status, br.urgency, h.hospital_name
                FROM blood_requests br
                JOIN hospitals h ON br.hospital_id = h.hospital_id
                WHERE br.request_id = :id
                FOR UPDATE
            ";
            $reqStmt = $pdo->prepare($reqSql);
            $reqStmt->execute([':id' => $requestId]);
            $request = $reqStmt->fetch();

            if (!$request) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Blood request not found.'];
            }

            // Multi-tenancy check: hospital staff can only fulfill their own hospital's request
            if ($hospitalIdConstraint !== null && (int)$request['hospital_id'] !== $hospitalIdConstraint) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Access denied: Request does not belong to your hospital.'];
            }

            if (!in_array($request['status'], ['PENDING', 'PARTIALLY_FULFILLED', 'MATCHING'])) {
                $pdo->rollBack();
                return ['success' => false, 'message' => "Request cannot be fulfilled because its status is {$request['status']}."];
            }

            // 3. Fetch request items with lock
            $itemStmt = $pdo->prepare("
                SELECT request_item_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled
                FROM request_items
                WHERE request_id = :req_id
                FOR UPDATE
            ");
            $itemStmt->execute([':req_id' => $requestId]);
            $requestItems = $itemStmt->fetchAll();

            if (empty($requestItems)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Request contains no items to fulfill.'];
            }

            $totalBagsAllocated = 0;
            $fulfillmentItemsToInsert = [];
            $bagsToUpdate = [];

            // 4. For each request item, find and lock eligible FEFO bags
            foreach ($requestItems as $item) {
                $remainingNeeded = (float)$item['quantity_requested'] - (float)$item['quantity_fulfilled'];
                if ($remainingNeeded <= 0) {
                    continue;
                }

                // Get compatible blood groups for this component type
                $compatibleGroupIds = CompatibilityService::getCompatibleDonorGroupIds(
                    (int)$item['blood_group_id'], 
                    $item['component_type']
                );
                $inPlaceholders = implode(',', array_fill(0, count($compatibleGroupIds), '?'));

                // Lock candidate bags via FEFO (earliest expiry first, deterministic secondary order)
                $fefoSql = "
                    SELECT blood_bag_id, bag_number, blood_group_id, component_type, quantity_ml, expiry_date, storage_location_id
                    FROM blood_bags
                    WHERE blood_group_id IN ($inPlaceholders)
                      AND component_type = ?
                      AND status = 'AVAILABLE'
                      AND expiry_date >= CURRENT_DATE
                    ORDER BY expiry_date ASC, blood_bag_id ASC
                    FOR UPDATE
                ";

                $fefoParams = array_merge($compatibleGroupIds, [$item['component_type']]);
                $fefoStmt = $pdo->prepare($fefoSql);
                $fefoStmt->execute($fefoParams);
                $candidateBags = $fefoStmt->fetchAll();

                $itemFulfilledNow = 0.00;
                foreach ($candidateBags as $bag) {
                    // Exclude bag if already allocated in this transaction loop
                    if (in_array($bag['blood_bag_id'], $bagsToUpdate)) {
                        continue;
                    }

                    if ($itemFulfilledNow < $remainingNeeded) {
                        $bagQty = (float)$bag['quantity_ml'];
                        $bagsToUpdate[] = (int)$bag['blood_bag_id'];
                        $itemFulfilledNow += $bagQty;
                        $totalBagsAllocated++;

                        $fulfillmentItemsToInsert[] = [
                            'request_item_id' => $item['request_item_id'],
                            'blood_bag_id'    => (int)$bag['blood_bag_id'],
                            'quantity_issued' => $bagQty,
                            'storage_loc_id'  => (int)$bag['storage_location_id']
                        ];

                        if ($itemFulfilledNow >= $remainingNeeded) {
                            break;
                        }
                    }
                }

                // Update item's fulfilled quantity
                if ($itemFulfilledNow > 0) {
                    $newFulfilled = (float)$item['quantity_fulfilled'] + $itemFulfilledNow;
                    $updItem = $pdo->prepare("
                        UPDATE request_items 
                        SET quantity_fulfilled = :qty 
                        WHERE request_item_id = :id
                    ");
                    $updItem->execute([
                        ':qty' => $newFulfilled,
                        ':id'  => $item['request_item_id']
                    ]);
                }
            }

            if ($totalBagsAllocated === 0) {
                $pdo->rollBack();
                return [
                    'success' => false, 
                    'message' => 'No compatible, non-expired blood units are currently available in inventory. You may initiate Emergency Donor Matching.'
                ];
            }

            // 5. Create Fulfillment Header
            $fulStmt = $pdo->prepare("
                INSERT INTO fulfillments (request_id, fulfilled_by, fulfillment_status, issued_at, notes, created_at)
                VALUES (:req_id, :user_id, 'COMPLETED', NOW(), :notes, NOW())
            ");
            $fulStmt->execute([
                ':req_id'  => $requestId,
                ':user_id' => $actorUserId,
                ':notes'   => "Automated FEFO fulfillment of {$totalBagsAllocated} blood bag(s)."
            ]);
            $fulfillmentId = (int)$pdo->lastInsertId();

            // 6. Insert Fulfillment Items, mark bags as ISSUED, and log inventory movements
            $insItemStmt = $pdo->prepare("
                INSERT INTO fulfillment_items (fulfillment_id, request_item_id, blood_bag_id, quantity_issued, issued_at)
                VALUES (:ful_id, :item_id, :bag_id, :qty, NOW())
            ");

            $bagStatusStmt = $pdo->prepare("
                UPDATE blood_bags 
                SET status = 'ISSUED' 
                WHERE blood_bag_id = :bag_id
            ");

            $movStmt = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml,
                    reason, notes, performed_by, movement_date
                ) VALUES (
                    :bag_id, :from_loc, NULL, 'ISSUED', :qty, :reason, :notes, :user_id, NOW()
                )
            ");

            foreach ($fulfillmentItemsToInsert as $fItem) {
                $insItemStmt->execute([
                    ':ful_id'  => $fulfillmentId,
                    ':item_id' => $fItem['request_item_id'],
                    ':bag_id'  => $fItem['blood_bag_id'],
                    ':qty'     => $fItem['quantity_issued']
                ]);

                $bagStatusStmt->execute([':bag_id' => $fItem['blood_bag_id']]);

                $movStmt->execute([
                    ':bag_id'   => $fItem['blood_bag_id'],
                    ':from_loc' => $fItem['storage_loc_id'],
                    ':qty'      => $fItem['quantity_issued'],
                    ':reason'   => "Fulfillment of Request #{$requestId}",
                    ':notes'    => "Fulfillment #{$fulfillmentId} dispatched to {$request['hospital_name']}",
                    ':user_id'  => $actorUserId
                ]);
            }

            // 7. Check if request is completely fulfilled or partially fulfilled
            $checkItems = $pdo->prepare("
                SELECT SUM(quantity_requested) AS total_req, SUM(quantity_fulfilled) AS total_ful
                FROM request_items
                WHERE request_id = :req_id
            ");
            $checkItems->execute([':req_id' => $requestId]);
            $totals = $checkItems->fetch();

            $newReqStatus = ((float)$totals['total_ful'] >= (float)$totals['total_req'])
                ? 'FULFILLED'
                : 'PARTIALLY_FULFILLED';

            $updReq = $pdo->prepare("UPDATE blood_requests SET status = :status WHERE request_id = :id");
            $updReq->execute([':status' => $newReqStatus, ':id' => $requestId]);

            // 8. Audit Log
            AuditService::log(
                'FULFILL_REQUEST',
                'blood_requests',
                $requestId,
                ['status' => $request['status']],
                ['status' => $newReqStatus, 'fulfillment_id' => $fulfillmentId, 'bags_count' => $totalBagsAllocated],
                $actorUserId
            );

            // 9. Commit Transaction
            $pdo->commit();

            return [
                'success' => true,
                'message' => "Successfully allocated {$totalBagsAllocated} blood bag(s). Request status updated to {$newReqStatus}.",
                'status'  => $newReqStatus,
                'fulfillment_id' => $fulfillmentId,
                'bags_allocated' => $totalBagsAllocated
            ];
        } catch (Exception $e) {
            // Roll back all changes on any error or constraint failure
            $pdo->rollBack();
            error_log("Fulfillment transaction rolled back: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Fulfillment transaction failed and was rolled back: ' . $e->getMessage()
            ];
        }
    }
}
