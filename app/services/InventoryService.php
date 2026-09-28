<?php
/**
 * BloodLink - Inventory Management & FEFO Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuditService.php';

class InventoryService {
    /**
     * Get available blood bags sorted strictly by FEFO (Earliest Expiry First)
     */
    public static function getAvailableInventory(array $filters = []): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                bb.blood_bag_id,
                bb.bag_number,
                bb.blood_group_id,
                bg.group_name AS blood_group,
                bb.component_type,
                bb.quantity_ml,
                bb.collection_date,
                bb.expiry_date,
                DATEDIFF(bb.expiry_date, CURRENT_DATE) AS days_to_expiry,
                bb.status,
                bb.storage_location_id,
                sl.location_code,
                sl.location_name
            FROM blood_bags bb
            JOIN blood_groups bg ON bb.blood_group_id = bg.blood_group_id
            JOIN storage_locations sl ON bb.storage_location_id = sl.storage_location_id
            WHERE bb.status = 'AVAILABLE'
              AND bb.expiry_date >= CURRENT_DATE
        ";

        $params = [];

        if (!empty($filters['blood_group_id'])) {
            $sql .= " AND bb.blood_group_id = :blood_group_id";
            $params[':blood_group_id'] = (int)$filters['blood_group_id'];
        }

        if (!empty($filters['component_type'])) {
            $sql .= " AND bb.component_type = :component_type";
            $params[':component_type'] = $filters['component_type'];
        }

        if (!empty($filters['storage_location_id'])) {
            $sql .= " AND bb.storage_location_id = :storage_location_id";
            $params[':storage_location_id'] = (int)$filters['storage_location_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (bb.bag_number LIKE :search OR sl.location_name LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // FEFO Ordering: Earliest expiring units first, then deterministic bag ID
        $sql .= " ORDER BY bb.expiry_date ASC, bb.blood_bag_id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Units expiring within threshold days (e.g. <= 7 days)
     */
    public static function getNearExpiryInventory(int $days = 7): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                bb.blood_bag_id,
                bb.bag_number,
                bg.group_name AS blood_group,
                bb.component_type,
                bb.quantity_ml,
                bb.expiry_date,
                DATEDIFF(bb.expiry_date, CURRENT_DATE) AS days_remaining,
                sl.location_name
            FROM blood_bags bb
            JOIN blood_groups bg ON bb.blood_group_id = bg.blood_group_id
            JOIN storage_locations sl ON bb.storage_location_id = sl.storage_location_id
            WHERE bb.status = 'AVAILABLE'
              AND bb.expiry_date >= CURRENT_DATE
              AND DATEDIFF(bb.expiry_date, CURRENT_DATE) <= :days
            ORDER BY bb.expiry_date ASC, bb.blood_bag_id ASC
        ");
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }

    /**
     * Units that have passed expiry date or are flagged EXPIRED
     */
    public static function getExpiredInventory(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT 
                bb.blood_bag_id,
                bb.bag_number,
                bg.group_name AS blood_group,
                bb.component_type,
                bb.quantity_ml,
                bb.expiry_date,
                DATEDIFF(CURRENT_DATE, bb.expiry_date) AS days_expired,
                bb.status,
                sl.location_name
            FROM blood_bags bb
            JOIN blood_groups bg ON bb.blood_group_id = bg.blood_group_id
            JOIN storage_locations sl ON bb.storage_location_id = sl.storage_location_id
            WHERE bb.expiry_date < CURRENT_DATE
               OR bb.status = 'EXPIRED'
            ORDER BY bb.expiry_date DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * DBMS Aggregation: Total units and volume grouped by Blood Group and Component
     */
    public static function getInventorySummary(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT 
                bg.blood_group_id,
                bg.group_name,
                bb.component_type,
                COUNT(bb.blood_bag_id) AS total_units,
                COALESCE(SUM(bb.quantity_ml), 0) AS total_ml,
                MIN(bb.expiry_date) AS earliest_expiry
            FROM blood_groups bg
            LEFT JOIN blood_bags bb ON bg.blood_group_id = bb.blood_group_id 
                 AND bb.status = 'AVAILABLE' 
                 AND bb.expiry_date >= CURRENT_DATE
            GROUP BY bg.blood_group_id, bg.group_name, bb.component_type
            ORDER BY bg.blood_group_id ASC
        ");
        return $stmt->fetchAll();
    }

    public static function discardUnit(int $bloodBagId, string $reason, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM blood_bags WHERE blood_bag_id = :id FOR UPDATE");
            $stmt->execute([':id' => $bloodBagId]);
            $bag = $stmt->fetch();

            if (!$bag) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Blood bag not found.'];
            }

            if ($bag['status'] === 'DISCARDED' || $bag['status'] === 'ISSUED') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Bag is already ' . $bag['status'] . '.'];
            }

            $oldStatus = $bag['status'];
            $upd = $pdo->prepare("UPDATE blood_bags SET status = 'DISCARDED' WHERE blood_bag_id = :id");
            $upd->execute([':id' => $bloodBagId]);

            // Insert inventory movement
            $mov = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml,
                    reason, notes, performed_by, movement_date
                ) VALUES (
                    :bag_id, :from_loc, NULL, 'DISCARDED', :qty, :reason, 'Unit decommissioned by administrator', :user_id, NOW()
                )
            ");
            $mov->execute([
                ':bag_id'   => $bloodBagId,
                ':from_loc' => $bag['storage_location_id'],
                ':qty'      => $bag['quantity_ml'],
                ':reason'   => $reason ?: 'Standard biological waste protocol',
                ':user_id'  => $userId
            ]);

            AuditService::log('DISCARD_BAG', 'blood_bags', $bloodBagId, ['status' => $oldStatus], ['status' => 'DISCARDED', 'reason' => $reason], $userId);

            $pdo->commit();
            return ['success' => true, 'message' => "Blood bag {$bag['bag_number']} marked as DISCARDED."];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to discard unit: ' . $e->getMessage()];
        }
    }

    public static function transferUnit(int $bloodBagId, int $toLocationId, string $reason, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT * FROM blood_bags WHERE blood_bag_id = :id FOR UPDATE");
            $stmt->execute([':id' => $bloodBagId]);
            $bag = $stmt->fetch();

            if (!$bag) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Blood bag not found.'];
            }

            if ($bag['status'] !== 'AVAILABLE') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Only AVAILABLE blood bags can be transferred.'];
            }

            if ((int)$bag['storage_location_id'] === $toLocationId) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Destination location is the same as current location.'];
            }

            $fromLocationId = (int)$bag['storage_location_id'];

            $upd = $pdo->prepare("UPDATE blood_bags SET storage_location_id = :to_loc WHERE blood_bag_id = :id");
            $upd->execute([':to_loc' => $toLocationId, ':id' => $bloodBagId]);

            $mov = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml,
                    reason, notes, performed_by, movement_date
                ) VALUES (
                    :bag_id, :from_loc, :to_loc, 'TRANSFERRED', :qty, :reason, 'Inter-facility transfer', :user_id, NOW()
                )
            ");
            $mov->execute([
                ':bag_id'   => $bloodBagId,
                ':from_loc' => $fromLocationId,
                ':to_loc'   => $toLocationId,
                ':qty'      => $bag['quantity_ml'],
                ':reason'   => $reason ?: 'Storage optimization / reallocation',
                ':user_id'  => $userId
            ]);

            AuditService::log(
                'TRANSFER_BAG', 
                'blood_bags', 
                $bloodBagId, 
                ['storage_location_id' => $fromLocationId], 
                ['storage_location_id' => $toLocationId, 'reason' => $reason], 
                $userId
            );

            $pdo->commit();
            return ['success' => true, 'message' => "Blood bag {$bag['bag_number']} successfully transferred."];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Transfer failed: ' . $e->getMessage()];
        }
    }

    public static function createBloodBag(array $data, int $userId): array {
        $pdo = Database::getConnection();

        $bagNumber    = trim($data['bag_number'] ?? '');
        $donationId   = (int)($data['donation_id'] ?? 0);
        $groupId      = (int)($data['blood_group_id'] ?? 0);
        $component    = trim($data['component_type'] ?? 'WHOLE_BLOOD');
        $collectDate  = trim($data['collection_date'] ?? '');
        $expiryDate   = trim($data['expiry_date'] ?? '');
        $quantityMl   = (float)($data['quantity_ml'] ?? 450.00);
        $locationId   = (int)($data['storage_location_id'] ?? 1);

        if (!$bagNumber || !$donationId || !$groupId || !$collectDate || !$expiryDate || $quantityMl <= 0) {
            return ['success' => false, 'message' => 'Please provide complete valid bag parameters.'];
        }

        if (strtotime($expiryDate) <= strtotime($collectDate)) {
            return ['success' => false, 'message' => 'Expiry date must be strictly after collection date.'];
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blood_bags (
                    bag_number, donation_id, blood_group_id, component_type,
                    collection_date, expiry_date, quantity_ml, status, storage_location_id
                ) VALUES (
                    :bag_num, :don_id, :group_id, :comp,
                    :coll_date, :exp_date, :qty, 'AVAILABLE', :loc_id
                )
            ");
            $stmt->execute([
                ':bag_num'   => $bagNumber,
                ':don_id'    => $donationId,
                ':group_id'  => $groupId,
                ':comp'      => $component,
                ':coll_date' => $collectDate,
                ':exp_date'  => $expiryDate,
                ':qty'       => $quantityMl,
                ':loc_id'    => $locationId
            ]);
            $bagId = (int)$pdo->lastInsertId();

            // Record initial movement
            $mov = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type, quantity_ml,
                    reason, notes, performed_by, movement_date
                ) VALUES (
                    :bag_id, NULL, :loc_id, 'RECEIVED', :qty, 'Initial accession', 'Passed laboratory screening', :user_id, NOW()
                )
            ");
            $mov->execute([
                ':bag_id'  => $bagId,
                ':loc_id'  => $locationId,
                ':qty'     => $quantityMl,
                ':user_id' => $userId
            ]);

            AuditService::log('CREATE_BAG', 'blood_bags', $bagId, null, ['bag_number' => $bagNumber, 'quantity_ml' => $quantityMl], $userId);

            $pdo->commit();
            return ['success' => true, 'message' => "Blood bag {$bagNumber} registered and placed into active FEFO inventory."];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Failed to create blood bag: ' . $e->getMessage()];
        }
    }

    public static function accessionAdminDonation(array $data, int $userId): array {
        $stringValue = static fn(string $key): string => is_string($data[$key] ?? null) ? trim($data[$key]) : '';
        $donorId = filter_var(is_scalar($data['donor_id'] ?? null) ? $data['donor_id'] : null, FILTER_VALIDATE_INT);
        $component = strtoupper($stringValue('component_type'));
        $collectionDateInput = $stringValue('collection_date');
        $quantityMl = filter_var(is_scalar($data['quantity_ml'] ?? null) ? $data['quantity_ml'] : null, FILTER_VALIDATE_FLOAT);
        $locationId = filter_var(is_scalar($data['storage_location_id'] ?? null) ? $data['storage_location_id'] : null, FILTER_VALIDATE_INT);
        $components = [
            'WHOLE_BLOOD' => ['storage' => 'REFRIGERATOR', 'days' => 35],
            'RBC' => ['storage' => 'REFRIGERATOR', 'days' => 35],
            'PLASMA' => ['storage' => 'FREEZER', 'days' => 365],
            'PLATELET' => ['storage' => 'PLATELET_AGITATOR', 'days' => 5]
        ];
        $collectionDate = DateTimeImmutable::createFromFormat('!Y-m-d', $collectionDateInput);
        $today = new DateTimeImmutable('today');

        if ($donorId === false
            || !isset($components[$component])
            || !$collectionDate
            || $collectionDate->format('Y-m-d') !== $collectionDateInput
            || $collectionDate > $today
            || $quantityMl === false
            || !is_finite((float)$quantityMl)
            || $quantityMl <= 0
            || $quantityMl > 99999.99
            || $locationId === false) {
            return ['success' => false, 'message' => 'Enter a valid donor, component, collection date, volume, and storage location.'];
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $donorStmt = $pdo->prepare("
                SELECT d.donor_id, d.blood_group_id, d.next_eligible_date, u.account_status,
                       (SELECT MAX(prior.donation_date)
                    FROM donations prior
                    WHERE prior.donor_id = d.donor_id AND prior.donation_status = 'COMPLETED') AS last_donation_date
                FROM donors d
                JOIN users u ON u.user_id = d.user_id
                WHERE d.donor_id = :donor_id
                FOR UPDATE
            ");
            $donorStmt->execute([':donor_id' => $donorId]);
            $donor = $donorStmt->fetch();
            if (!$donor || $donor['account_status'] !== 'ACTIVE') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Select an active donor account.'];
            }

            $interval = (int)$pdo->query(
                "SELECT setting_value FROM system_settings WHERE setting_key = 'DONATION_MIN_INTERVAL_DAYS'"
            )->fetchColumn();
            if ($interval <= 0) {
                $interval = 90;
            }
            $lastDonationDate = $donor['last_donation_date'] ? new DateTimeImmutable($donor['last_donation_date']) : null;
            $earliestEligibleDate = $lastDonationDate
                ? $lastDonationDate->modify("+{$interval} days")->format('Y-m-d')
                : null;
            if (($earliestEligibleDate && $collectionDate->format('Y-m-d') < $earliestEligibleDate)
                || ($donor['next_eligible_date'] && $collectionDate->format('Y-m-d') < $donor['next_eligible_date'])) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'The donor was not yet eligible on that collection date.'];
            }

            if (($data['screening_confirmed'] ?? null) !== '1') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Confirm that the collection is complete and screening has passed.'];
            }

            $storageStmt = $pdo->prepare("
                                SELECT storage_location_id, capacity_units
                FROM storage_locations
                WHERE storage_location_id = :location_id
                  AND storage_type = :storage_type
                  AND location_status = 'ACTIVE'
                FOR UPDATE
            ");
            $storageStmt->execute([
                ':location_id' => $locationId,
                ':storage_type' => $components[$component]['storage']
            ]);
            $storage = $storageStmt->fetch();
            if (!$storage) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Choose an active storage location suitable for the selected component.'];
            }

            $capacityStmt = $pdo->prepare("
                                SELECT blood_bag_id
                FROM blood_bags
                WHERE storage_location_id = :location_id
                  AND status IN ('AVAILABLE', 'RESERVED', 'EXPIRED')
                                ORDER BY blood_bag_id ASC
                                FOR UPDATE
            ");
            $capacityStmt->execute([':location_id' => $locationId]);
                        $storedBagIds = $capacityStmt->fetchAll(PDO::FETCH_COLUMN);
                        if (count($storedBagIds) >= (int)$storage['capacity_units']) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'The selected storage location has reached its unit capacity.'];
            }

            $expiryDate = $collectionDate->modify('+' . $components[$component]['days'] . ' days')->format('Y-m-d');
            if ($expiryDate < $today->format('Y-m-d')) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'This collection has passed its component expiry date and cannot enter available inventory.'];
            }
            $componentCode = [
                'WHOLE_BLOOD' => 'WB',
                'RBC' => 'RBC',
                'PLASMA' => 'PLS',
                'PLATELET' => 'PLT'
            ][$component];
            $bagNumber = sprintf(
                'BL-%s-%s-%s',
                $componentCode,
                $collectionDate->format('Ymd'),
                strtoupper(bin2hex(random_bytes(6)))
            );
            $donationStmt = $pdo->prepare("
                INSERT INTO donations (
                    donor_id, donor_match_id, donation_type, donation_date, quantity_ml,
                    screening_status, donation_status, notes
                ) VALUES (
                    :donor_id, NULL, :component, :donation_date, :quantity_ml,
                    'PASSED', 'COMPLETED', 'Admin-recorded collection; screening confirmed passed.'
                )
            ");
            $donationStmt->execute([
                ':donor_id' => $donorId,
                ':component' => $component,
                ':donation_date' => $collectionDate->format('Y-m-d') . ' 12:00:00',
                ':quantity_ml' => $quantityMl
            ]);
            $donationId = (int)$pdo->lastInsertId();

            $bagStmt = $pdo->prepare("
                INSERT INTO blood_bags (
                    bag_number, donation_id, blood_group_id, component_type,
                    collection_date, expiry_date, quantity_ml, status, storage_location_id
                ) VALUES (
                    :bag_number, :donation_id, :blood_group_id, :component,
                    :collection_date, :expiry_date, :quantity_ml, 'AVAILABLE', :location_id
                )
            ");
            $bagStmt->execute([
                ':bag_number' => $bagNumber,
                ':donation_id' => $donationId,
                ':blood_group_id' => $donor['blood_group_id'],
                ':component' => $component,
                ':collection_date' => $collectionDate->format('Y-m-d'),
                ':expiry_date' => $expiryDate,
                ':quantity_ml' => $quantityMl,
                ':location_id' => $locationId
            ]);
            $bagId = (int)$pdo->lastInsertId();

            $movementStmt = $pdo->prepare("
                INSERT INTO inventory_movements (
                    blood_bag_id, from_location_id, to_location_id, movement_type,
                    quantity_ml, reason, notes, performed_by
                ) VALUES (
                    :bag_id, NULL, :location_id, 'RECEIVED', :quantity_ml,
                    'Admin accession after passed screening', :notes, :user_id
                )
            ");
            $movementStmt->execute([
                ':bag_id' => $bagId,
                ':location_id' => $locationId,
                ':quantity_ml' => $quantityMl,
                ':notes' => "Accessioned as blood bag {$bagNumber}.",
                ':user_id' => $userId
            ]);

            $eligibilityStmt = $pdo->prepare("
                UPDATE donors
                SET next_eligible_date = DATE_ADD(:collection_date, INTERVAL {$interval} DAY),
                    eligibility_status = 'NOT_ELIGIBLE'
                WHERE donor_id = :donor_id
            ");
            $eligibilityStmt->execute([
                ':collection_date' => $collectionDate->format('Y-m-d'),
                ':donor_id' => $donorId
            ]);

            $countStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM donations
                WHERE donor_id = :donor_id AND donation_status = 'COMPLETED'
            ");
            $countStmt->execute([':donor_id' => $donorId]);
            $totalDonations = (int)$countStmt->fetchColumn();
            $recognitionStmt = $pdo->prepare("
                INSERT IGNORE INTO donor_recognition (donor_id, recognition_level_id, achieved_date, notes)
                SELECT :donor_id, recognition_level_id, CURRENT_DATE, :notes
                FROM recognition_levels
                WHERE minimum_donations <= :total_donations
            ");
            $recognitionStmt->execute([
                ':donor_id' => $donorId,
                ':notes' => "Achieved upon {$totalDonations} completed donations",
                ':total_donations' => $totalDonations
            ]);

            AuditService::log('ADMIN_ACCESSION_DONATION', 'blood_bags', $bagId, null, [
                'bag_number' => $bagNumber,
                'donation_id' => $donationId,
                'donor_id' => $donorId,
                'component_type' => $component,
                'quantity_ml' => $quantityMl
            ], $userId);

            $pdo->commit();
            return [
                'success' => true,
                'bag_number' => $bagNumber,
                'message' => "Blood bag {$bagNumber} was accessioned into available inventory."
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin blood bag accession failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Could not accession this bag. Check for a duplicate bag number and valid inventory records.'];
        }
    }

    public static function getStorageLocations(): array {
        $pdo = Database::getConnection();
        return $pdo->query("SELECT * FROM storage_locations ORDER BY storage_location_id ASC")->fetchAll();
    }
}
