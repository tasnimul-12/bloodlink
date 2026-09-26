<?php
/**
 * BloodLink - Emergency Donor Matching & Notification Engine
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/CompatibilityService.php';
require_once __DIR__ . '/AuditService.php';

class MatchingService {
    /**
     * Finds eligible donors and creates match records & notifications
     */
    public static function matchDonorsForRequest(int $requestId, int $actorUserId): array {
        $pdo = Database::getConnection();

        // Get request and hospital details
        $stmt = $pdo->prepare("
            SELECT br.request_id, br.hospital_id, br.status, h.hospital_name, h.city AS hospital_city
            FROM blood_requests br
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            WHERE br.request_id = :id
        ");
        $stmt->execute([':id' => $requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            return ['success' => false, 'message' => 'Request not found.'];
        }

        // Get request items
        $itemStmt = $pdo->prepare("
            SELECT request_item_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled
            FROM request_items
            WHERE request_id = :id AND quantity_fulfilled < quantity_requested
        ");
        $itemStmt->execute([':id' => $requestId]);
        $items = $itemStmt->fetchAll();

        if (empty($items)) {
            return ['success' => false, 'message' => 'All items in this request are already fulfilled.'];
        }

        $pdo->beginTransaction();
        $totalNotified = 0;

        try {
            foreach ($items as $item) {
                $reqGroupId = (int)$item['blood_group_id'];
                $compType   = $item['component_type'];
                $reqItemId  = (int)$item['request_item_id'];

                $compatibleGroups = CompatibilityService::getCompatibleDonorGroupIds($reqGroupId, $compType);
                $inList = implode(',', array_fill(0, count($compatibleGroups), '?'));

                // Query eligible donors
                $donorSql = "
                    SELECT d.donor_id, d.user_id, d.full_name, d.blood_group_id, d.city, bg.group_name
                    FROM donors d
                    JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
                    JOIN users u ON d.user_id = u.user_id
                    WHERE d.blood_group_id IN ($inList)
                      AND d.availability_status = 'AVAILABLE'
                      AND d.eligibility_status = 'ELIGIBLE'
                      AND (d.next_eligible_date IS NULL OR d.next_eligible_date <= CURRENT_DATE)
                      AND u.account_status = 'ACTIVE'
                ";
                $donorStmt = $pdo->prepare($donorSql);
                $donorStmt->execute($compatibleGroups);
                $candidates = $donorStmt->fetchAll();

                foreach ($candidates as $donor) {
                    // Check if match already exists
                    $existCheck = $pdo->prepare("
                        SELECT match_id FROM donor_matches 
                        WHERE request_item_id = :item_id AND donor_id = :donor_id
                    ");
                    $existCheck->execute([':item_id' => $reqItemId, ':donor_id' => $donor['donor_id']]);
                    if ($existCheck->fetch()) {
                        continue;
                    }

                    // Calculate score and reason
                    $sameCity = (strcasecmp($donor['city'] ?? '', $request['hospital_city'] ?? '') === 0);
                    $exactMatch = ((int)$donor['blood_group_id'] === $reqGroupId);

                    $score = 70.00;
                    if ($exactMatch) $score += 15.00;
                    if ($sameCity)   $score += 15.00;

                    $reason = sprintf(
                        "Donor (%s, %s) is %s for %s blood requested in %s.",
                        $donor['group_name'],
                        $donor['city'],
                        $exactMatch ? 'an exact match' : 'a compatible match',
                        $compType,
                        $request['hospital_city']
                    );

                    // Insert match record
                    $insMatch = $pdo->prepare("
                        INSERT INTO donor_matches (
                            request_item_id, donor_id, match_score, match_reason, match_status, notified_at, created_at
                        ) VALUES (
                            :item_id, :donor_id, :score, :reason, 'NOTIFIED', NOW(), NOW()
                        )
                    ");
                    $insMatch->execute([
                        ':item_id'  => $reqItemId,
                        ':donor_id' => $donor['donor_id'],
                        ':score'    => $score,
                        ':reason'   => $reason
                    ]);
                    $matchId = (int)$pdo->lastInsertId();

                    // Insert in-system notification for donor
                    $insNotif = $pdo->prepare("
                        INSERT INTO notifications (
                            user_id, notification_type, title, message, related_match_id, is_read, created_at
                        ) VALUES (
                            :user_id, 'URGENT_MATCH', :title, :msg, :match_id, FALSE, NOW()
                        )
                    ");
                    $insNotif->execute([
                        ':user_id'  => $donor['user_id'],
                        ':title'    => "Urgent: Compatible Blood Request at {$request['hospital_name']}",
                        ':msg'      => "{$request['hospital_name']} in {$request['hospital_city']} urgently needs {$compType} blood. Your blood group ({$donor['group_name']}) is compatible. Please respond if you are available to donate.",
                        ':match_id' => $matchId
                    ]);

                    $totalNotified++;
                }
            }

            // Update request status to MATCHING if still PENDING
            if ($request['status'] === 'PENDING') {
                $upd = $pdo->prepare("UPDATE blood_requests SET status = 'MATCHING' WHERE request_id = :id");
                $upd->execute([':id' => $requestId]);
            }

            AuditService::log('EMERGENCY_MATCH', 'blood_requests', $requestId, null, ['notified_count' => $totalNotified], $actorUserId);

            $pdo->commit();
            return [
                'success' => true,
                'message' => "Emergency donor matching completed. {$totalNotified} compatible donor(s) were notified.",
                'notified_count' => $totalNotified
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Matching failed: ' . $e->getMessage()];
        }
    }

    public static function respondToMatch(int $matchId, int $donorId, string $response): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT dm.match_id, dm.match_status, dm.request_item_id, ri.request_id, br.hospital_id, h.hospital_name, d.full_name
            FROM donor_matches dm
            JOIN request_items ri ON dm.request_item_id = ri.request_item_id
            JOIN blood_requests br ON ri.request_id = br.request_id
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            JOIN donors d ON dm.donor_id = d.donor_id
            WHERE dm.match_id = :match_id AND dm.donor_id = :donor_id
        ");
        $stmt->execute([':match_id' => $matchId, ':donor_id' => $donorId]);
        $match = $stmt->fetch();

        if (!$match) {
            return ['success' => false, 'message' => 'Matching invitation not found or access denied.'];
        }

        $newStatus = ($response === 'ACCEPT') ? 'ACCEPTED' : 'DECLINED';

        $upd = $pdo->prepare("
            UPDATE donor_matches 
            SET match_status = :status, response_at = NOW() 
            WHERE match_id = :id
        ");
        $upd->execute([':status' => $newStatus, ':id' => $matchId]);

        // Find hospital staff users to notify them of donor response
        $staffStmt = $pdo->prepare("
            SELECT hs.user_id 
            FROM hospital_staff hs
            WHERE hs.hospital_id = :hosp_id AND hs.staff_status = 'ACTIVE'
        ");
        $staffStmt->execute([':hosp_id' => $match['hospital_id']]);
        $staffUsers = $staffStmt->fetchAll();

        foreach ($staffUsers as $s) {
            $notif = $pdo->prepare("
                INSERT INTO notifications (user_id, notification_type, title, message, related_match_id, is_read, created_at)
                VALUES (:u_id, 'REQUEST_UPDATE', :title, :msg, :match_id, FALSE, NOW())
            ");
            $notif->execute([
                ':u_id'     => $s['user_id'],
                ':title'    => "Donor {$newStatus} Emergency Match (Req #{$match['request_id']})",
                ':msg'      => "A compatible donor has {$newStatus} the emergency donation request for your hospital.",
                ':match_id' => $matchId
            ]);
        }

        AuditService::log('RESPOND_MATCH', 'donor_matches', $matchId, ['status' => $match['match_status']], ['status' => $newStatus]);

        return [
            'success' => true,
            'message' => "You have {$newStatus} this emergency donation request. Thank you for your support!"
        ];
    }
}
