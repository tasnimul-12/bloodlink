<?php
/**
 * BloodLink - Matched donation confirmation workflow
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/FulfillmentService.php';

class DonationService {
    public static function cancelMatchedDonation(int $donationId, int $staffUserId, int $hospitalId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $donorIdStmt = $pdo->prepare('SELECT donor_id FROM donations WHERE donation_id = :donation_id');
            $donorIdStmt->execute([':donation_id' => $donationId]);
            $donorId = $donorIdStmt->fetchColumn();
            if (!$donorId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Matched donation record was not found.'];
            }

            $donorLock = $pdo->prepare('SELECT donor_id FROM donors WHERE donor_id = :donor_id FOR UPDATE');
            $donorLock->execute([':donor_id' => $donorId]);

            $stmt = $pdo->prepare("
                SELECT don.donation_id, don.donor_id, don.donor_match_id, don.donation_status,
                       dm.match_status, ri.request_id, br.hospital_id, br.status AS request_status,
                       d.user_id AS donor_user_id, d.full_name
                FROM donations don
                JOIN donor_matches dm ON dm.match_id = don.donor_match_id
                JOIN request_items ri ON ri.request_item_id = dm.request_item_id
                JOIN blood_requests br ON br.request_id = ri.request_id
                JOIN donors d ON d.donor_id = don.donor_id
                WHERE don.donation_id = :donation_id
                FOR UPDATE
            ");
            $stmt->execute([':donation_id' => $donationId]);
            $donation = $stmt->fetch();

            if (!$donation) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Matched donation record was not found.'];
            }
            if ((int)$donation['hospital_id'] !== $hospitalId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'You can only cancel donations for your own hospital.'];
            }
            if ($donation['donation_status'] !== 'SCHEDULED' || $donation['match_status'] !== 'ACCEPTED') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Only a scheduled donation accepted by the donor can be cancelled.'];
            }
            if (!in_array($donation['request_status'], ['PENDING', 'MATCHING', 'PARTIALLY_FULFILLED'], true)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'The associated request is closed; this donation cannot be cancelled here.'];
            }

            $cancelDonation = $pdo->prepare("
                UPDATE donations
                SET donation_status = 'CANCELLED',
                    notes = CONCAT(COALESCE(notes, ''), '\nCancelled by hospital staff before collection.')
                WHERE donation_id = :donation_id AND donation_status = 'SCHEDULED'
            ");
            $cancelDonation->execute([':donation_id' => $donationId]);

            $cancelMatch = $pdo->prepare("
                UPDATE donor_matches
                SET match_status = 'CANCELLED', response_at = NOW()
                WHERE match_id = :match_id AND match_status = 'ACCEPTED'
            ");
            $cancelMatch->execute([':match_id' => $donation['donor_match_id']]);

            $notifyDonor = $pdo->prepare("
                INSERT INTO notifications (user_id, notification_type, title, message, related_match_id, is_read, created_at)
                VALUES (:user_id, 'REQUEST_UPDATE', 'Donation cancelled', :message, :match_id, FALSE, NOW())
            ");
            $notifyDonor->execute([
                ':user_id' => $donation['donor_user_id'],
                ':message' => "Your scheduled donation for request #{$donation['request_id']} was cancelled by the hospital before collection. You may respond to other active invitations.",
                ':match_id' => $donation['donor_match_id']
            ]);

            AuditService::log(
                'CANCEL_SCHEDULED_DONATION',
                'donations',
                $donationId,
                ['donation_status' => 'SCHEDULED', 'match_status' => 'ACCEPTED'],
                ['donation_status' => 'CANCELLED', 'match_status' => 'CANCELLED'],
                $staffUserId
            );

            $pdo->commit();
            return [
                'success' => true,
                'request_id' => (int)$donation['request_id'],
                'message' => "Scheduled donation for {$donation['full_name']} was cancelled."
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Matched donation cancellation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not cancel this donation. No changes were saved.'];
        }
    }

    public static function confirmMatchedDonation(
        int $donationId,
        int $staffUserId,
        int $hospitalId,
        array $data
    ): array {
        $donationType = strtoupper(trim($data['donation_type'] ?? 'WHOLE_BLOOD'));
        $quantityMl = (float)($data['quantity_ml'] ?? 0);
        $dateInput = trim($data['donation_date'] ?? '');
        $donationTimestamp = strtotime($dateInput);

        if (!in_array($donationType, ['WHOLE_BLOOD', 'RBC', 'PLASMA', 'PLATELET'], true)) {
            return ['success' => false, 'message' => 'Select a valid collected blood component.'];
        }
        if ($quantityMl <= 0 || $quantityMl > 1000) {
            return ['success' => false, 'message' => 'Enter a collected volume between 1 and 1000 mL.'];
        }
        if ($donationTimestamp === false || $donationTimestamp > time() + 300) {
            return ['success' => false, 'message' => 'Enter a valid collection date and time that is not in the future.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $donorIdStmt = $pdo->prepare('SELECT donor_id FROM donations WHERE donation_id = :donation_id');
            $donorIdStmt->execute([':donation_id' => $donationId]);
            $donorId = $donorIdStmt->fetchColumn();
            if (!$donorId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Matched donation record was not found.'];
            }

            $donorLock = $pdo->prepare('SELECT donor_id FROM donors WHERE donor_id = :donor_id FOR UPDATE');
            $donorLock->execute([':donor_id' => $donorId]);

            $stmt = $pdo->prepare("
                SELECT don.donation_id, don.donor_id, don.donor_match_id, don.donation_status,
                      dm.match_status, ri.request_id, br.hospital_id, br.status AS request_status,
                       ri.component_type AS requested_component,
                       d.full_name, d.user_id AS donor_user_id, d.blood_group_id
                FROM donations don
                JOIN donor_matches dm ON dm.match_id = don.donor_match_id
                JOIN request_items ri ON ri.request_item_id = dm.request_item_id
                JOIN blood_requests br ON br.request_id = ri.request_id
                JOIN donors d ON d.donor_id = don.donor_id
                WHERE don.donation_id = :donation_id
                FOR UPDATE
            ");
            $stmt->execute([':donation_id' => $donationId]);
            $donation = $stmt->fetch();

            if (!$donation) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Matched donation record was not found.'];
            }
            if ((int)$donation['hospital_id'] !== $hospitalId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'You can only confirm donations for your own hospital.'];
            }
            if ($donation['donation_status'] !== 'SCHEDULED' || $donation['match_status'] !== 'ACCEPTED') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'This donation is no longer awaiting hospital confirmation.'];
            }
            if (!in_array($donation['request_status'], ['PENDING', 'MATCHING', 'PARTIALLY_FULFILLED'], true)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'The associated request is closed; this donation cannot be confirmed.'];
            }
            if ($donationType !== $donation['requested_component']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'The collected component must match the component accepted for this request.'];
            }

            $donationDate = date('Y-m-d H:i:s', $donationTimestamp);
            $updateDonation = $pdo->prepare("
                UPDATE donations
                SET donation_type = :donation_type,
                    donation_date = :donation_date,
                    quantity_ml = :quantity_ml,
                    screening_status = 'PASSED',
                    donation_status = 'COMPLETED',
                    notes = CONCAT(COALESCE(notes, ''), '\nHospital confirmed collection and successful screening.')
                WHERE donation_id = :donation_id
            ");
            $updateDonation->execute([
                ':donation_type' => $donationType,
                ':donation_date' => $donationDate,
                ':quantity_ml' => $quantityMl,
                ':donation_id' => $donationId
            ]);

            $interval = (int)$pdo->query(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'DONATION_MIN_INTERVAL_DAYS'"
            )->fetchColumn();
            if ($interval <= 0) {
                $interval = 90;
            }

            $updateDonor = $pdo->prepare("
                UPDATE donors
                SET next_eligible_date = DATE_ADD(:donation_date, INTERVAL {$interval} DAY),
                    eligibility_status = 'NOT_ELIGIBLE'
                WHERE donor_id = :donor_id
            ");
            $updateDonor->execute([
                ':donation_date' => substr($donationDate, 0, 10),
                ':donor_id' => $donation['donor_id']
            ]);

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM donations
                WHERE donor_id = :donor_id AND donation_status = 'COMPLETED'
            ");
            $countStmt->execute([':donor_id' => $donation['donor_id']]);
            $totalDonations = (int)$countStmt->fetchColumn();

            $tierStmt = $pdo->prepare("
                INSERT IGNORE INTO donor_recognition (donor_id, recognition_level_id, achieved_date, notes)
                SELECT :donor_id, recognition_level_id, CURRENT_DATE, :notes
                FROM recognition_levels
                WHERE minimum_donations <= :total_donations
            ");
            $tierStmt->execute([
                ':donor_id' => $donation['donor_id'],
                ':notes' => "Achieved upon {$totalDonations} completed donations",
                ':total_donations' => $totalDonations
            ]);

            $storageTypeByDonation = [
                'WHOLE_BLOOD' => 'REFRIGERATOR',
                'RBC' => 'REFRIGERATOR',
                'PLASMA' => 'FREEZER',
                'PLATELET' => 'PLATELET_AGITATOR'
            ];
            $shelfLifeDays = [
                'WHOLE_BLOOD' => 35,
                'RBC' => 35,
                'PLASMA' => 365,
                'PLATELET' => 5
            ];
            $storageStmt = $pdo->prepare("
                SELECT storage_location_id
                FROM storage_locations
                WHERE storage_type = :storage_type AND location_status = 'ACTIVE'
                ORDER BY storage_location_id ASC
                LIMIT 1
                FOR UPDATE
            ");
            $storageStmt->execute([':storage_type' => $storageTypeByDonation[$donationType]]);
            $storageLocationId = $storageStmt->fetchColumn();
            if (!$storageLocationId) {
                throw new RuntimeException('No active storage location exists for the collected component.');
            }

            $collectionDate = substr($donationDate, 0, 10);
            $expiryDate = date('Y-m-d', strtotime($collectionDate . ' +' . $shelfLifeDays[$donationType] . ' days'));
            $bagStmt = $pdo->prepare("
                INSERT INTO blood_bags (
                    bag_number, donation_id, blood_group_id, component_type,
                    collection_date, expiry_date, quantity_ml, status, storage_location_id
                ) VALUES (
                    :bag_number, :donation_id, :blood_group_id, :component_type,
                    :collection_date, :expiry_date, :quantity_ml, 'AVAILABLE', :storage_location_id
                )
            ");
            $bagStmt->execute([
                ':bag_number' => 'DON-' . $donationId . '-M' . $donation['donor_match_id'],
                ':donation_id' => $donationId,
                ':blood_group_id' => $donation['blood_group_id'],
                ':component_type' => $donationType,
                ':collection_date' => $collectionDate,
                ':expiry_date' => $expiryDate,
                ':quantity_ml' => $quantityMl,
                ':storage_location_id' => $storageLocationId
            ]);
            $bloodBagId = (int)$pdo->lastInsertId();

            $movementStmt = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type,
                    quantity_ml, reason, notes, performed_by, movement_date
                ) VALUES (
                    :blood_bag_id, NULL, :storage_location_id, 'RECEIVED',
                    :quantity_ml, 'Hospital-confirmed donor collection', :notes, :user_id, NOW()
                )
            ");
            $movementStmt->execute([
                ':blood_bag_id' => $bloodBagId,
                ':storage_location_id' => $storageLocationId,
                ':quantity_ml' => $quantityMl,
                ':notes' => "Accessioned from completed donation #{$donationId}.",
                ':user_id' => $staffUserId
            ]);

            $siblingMatchesStmt = $pdo->prepare("
                SELECT dm.match_id, dm.match_status, ri.request_id, br.hospital_id,
                       don.donation_id
                FROM donor_matches dm
                JOIN request_items ri ON ri.request_item_id = dm.request_item_id
                JOIN blood_requests br ON br.request_id = ri.request_id
                LEFT JOIN donations don ON don.donor_match_id = dm.match_id
                WHERE dm.donor_id = :donor_id
                  AND dm.match_id <> :current_match_id
                  AND (
                      dm.match_status IN ('NOTIFIED', 'SUGGESTED')
                      OR (dm.match_status = 'ACCEPTED'
                          AND (don.donation_id IS NULL OR don.donation_status = 'SCHEDULED'))
                  )
                FOR UPDATE
            ");
            $siblingMatchesStmt->execute([
                ':donor_id' => $donation['donor_id'],
                ':current_match_id' => $donation['donor_match_id']
            ]);
            $siblingMatches = $siblingMatchesStmt->fetchAll();

            $cancelDonation = $pdo->prepare("
                UPDATE donations
                SET donation_status = 'CANCELLED',
                    notes = CONCAT(COALESCE(notes, ''), '\nCancelled because another donation was confirmed.')
                WHERE donation_id = :donation_id AND donation_status = 'SCHEDULED'
            ");
            $cancelMatch = $pdo->prepare("
                UPDATE donor_matches
                SET match_status = 'CANCELLED', response_at = COALESCE(response_at, NOW())
                WHERE match_id = :match_id AND match_status IN ('NOTIFIED', 'SUGGESTED', 'ACCEPTED')
            ");
            $closeDonorNotice = $pdo->prepare("
                UPDATE notifications
                SET notification_type = 'REQUEST_UPDATE',
                    title = :title,
                    message = :message,
                    is_read = FALSE,
                    read_at = NULL
                WHERE user_id = :user_id
                  AND related_match_id = :match_id
                  AND notification_type = 'URGENT_MATCH'
            ");
            $addDonorNotice = $pdo->prepare("
                INSERT INTO notifications (user_id, notification_type, title, message, related_match_id, is_read, created_at)
                VALUES (:user_id, 'REQUEST_UPDATE', :title, :message, :match_id, FALSE, NOW())
            ");

            foreach ($siblingMatches as $sibling) {
                if ($sibling['donation_id']) {
                    $cancelDonation->execute([':donation_id' => $sibling['donation_id']]);
                }
                $cancelMatch->execute([':match_id' => $sibling['match_id']]);

                $noticeTitle = 'Donation invitation closed';
                $noticeMessage = "Your invitation for request #{$sibling['request_id']} was closed because another donation was confirmed.";
                $closeDonorNotice->execute([
                    ':title' => $noticeTitle,
                    ':message' => $noticeMessage,
                    ':user_id' => $donation['donor_user_id'],
                    ':match_id' => $sibling['match_id']
                ]);
                if ($closeDonorNotice->rowCount() === 0) {
                    $addDonorNotice->execute([
                        ':user_id' => $donation['donor_user_id'],
                        ':title' => $noticeTitle,
                        ':message' => $noticeMessage,
                        ':match_id' => $sibling['match_id']
                    ]);
                }
            }

            AuditService::log(
                'ACCESSION_DONATION_BAG',
                'blood_bags',
                $bloodBagId,
                null,
                ['donation_id' => $donationId, 'bag_number' => 'DON-' . $donationId . '-M' . $donation['donor_match_id']],
                $staffUserId
            );

            $notifyDonor = $pdo->prepare("
                INSERT INTO notifications (user_id, notification_type, title, message, is_read, created_at)
                VALUES (:user_id, 'ELIGIBILITY_UPDATE', 'Donation confirmed', :message, FALSE, NOW())
            ");
            $notifyDonor->execute([
                ':user_id' => $donation['donor_user_id'],
                ':message' => "Your donation was confirmed by the hospital. Your next eligible donation date is " . date('Y-m-d', strtotime($donationDate . " +{$interval} days")) . ". Thank you, {$donation['full_name']}!"
            ]);

            AuditService::log(
                'CONFIRM_DONATION',
                'donations',
                $donationId,
                ['donation_status' => 'SCHEDULED'],
                ['donation_status' => 'COMPLETED', 'screening_status' => 'PASSED', 'quantity_ml' => $quantityMl],
                $staffUserId
            );

            $pdo->commit();

            $fulfillment = null;
            if ($donation['requested_component'] === $donationType) {
                $fulfillment = FulfillmentService::processFulfillment(
                    (int)$donation['request_id'],
                    $staffUserId,
                    $hospitalId
                );
            }

            $message = "Donation for {$donation['full_name']} confirmed and added to inventory.";
            if ($fulfillment && $fulfillment['success']) {
                $message .= " Request #{$donation['request_id']} was updated to {$fulfillment['status']} using FEFO allocation.";
            } elseif ($donation['requested_component'] !== $donationType) {
                $message .= ' It was not allocated to this request because the collected component differs from the requested component.';
            } else {
                $message .= ' The request remains open; inventory fulfillment can be retried from the request page.';
            }

            return [
                'success' => true,
                'request_id' => (int)$donation['request_id'],
                'message' => $message
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Matched donation confirmation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not confirm this donation. No changes were saved.'];
        }
    }
}
