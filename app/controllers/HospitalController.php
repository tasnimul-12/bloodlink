<?php
/**
 * BloodLink - Hospital Staff Portal Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/InventoryService.php';
require_once __DIR__ . '/../services/FulfillmentService.php';
require_once __DIR__ . '/../services/MatchingService.php';
require_once __DIR__ . '/../services/DonationService.php';
require_once __DIR__ . '/../services/AuditService.php';

class HospitalController extends Controller {
    private function getHospitalStaff(int $userId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT hs.*, h.hospital_name, h.registration_number, h.approval_status, h.city AS hospital_city, h.address AS hospital_address
            FROM hospital_staff hs
            JOIN hospitals h ON hs.hospital_id = h.hospital_id
            WHERE hs.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function dashboard(): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        if (!$staff) {
            die("Hospital staff profile not associated with this user.");
        }

        $pdo = Database::getConnection();

        // Count requests by status
        $reqCountStmt = $pdo->prepare("
            SELECT 
                COUNT(*) AS total_requests,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN status = 'MATCHING' THEN 1 ELSE 0 END) AS matching_count,
                SUM(CASE WHEN status = 'PARTIALLY_FULFILLED' THEN 1 ELSE 0 END) AS partial_count,
                SUM(CASE WHEN status = 'FULFILLED' THEN 1 ELSE 0 END) AS fulfilled_count
            FROM blood_requests
            WHERE hospital_id = :hosp_id
        ");
        $reqCountStmt->execute([':hosp_id' => $staff['hospital_id']]);
        $stats = $reqCountStmt->fetch();

        // Recent requests
        $recentStmt = $pdo->prepare("
            SELECT * FROM blood_requests 
            WHERE hospital_id = :hosp_id 
            ORDER BY request_date DESC 
            LIMIT 5
        ");
        $recentStmt->execute([':hosp_id' => $staff['hospital_id']]);
        $recentRequests = $recentStmt->fetchAll();

        // Available regional stock summary (anonymized)
        $stockSummary = InventoryService::getInventorySummary();

        // Hospital Notifications
        $notifStmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $notifStmt->execute([':user_id' => $user['user_id']]);
        $notifications = $notifStmt->fetchAll();

                $pendingDonationsStmt = $pdo->prepare("
                        SELECT don.donation_id, don.donation_type, don.quantity_ml,
                                     dm.response_at, ri.request_id, d.full_name, d.city,
                                     bg.group_name AS blood_group
                        FROM donations don
                        JOIN donor_matches dm ON dm.match_id = don.donor_match_id
                        JOIN request_items ri ON ri.request_item_id = dm.request_item_id
                        JOIN blood_requests br ON br.request_id = ri.request_id
                        JOIN donors d ON d.donor_id = don.donor_id
                        JOIN blood_groups bg ON bg.blood_group_id = d.blood_group_id
                        WHERE br.hospital_id = :hospital_id
                            AND br.status IN ('PENDING', 'MATCHING', 'PARTIALLY_FULFILLED')
                            AND dm.match_status = 'ACCEPTED'
                            AND don.donation_status = 'SCHEDULED'
                        ORDER BY dm.response_at DESC
                ");
                $pendingDonationsStmt->execute([':hospital_id' => $staff['hospital_id']]);
                $pendingDonations = $pendingDonationsStmt->fetchAll();

        $this->render('hospital/dashboard', [
            'title' => 'Hospital Portal — BloodLink',
            'staff' => $staff,
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'stockSummary' => $stockSummary,
            'notifications' => $notifications,
            'pendingDonations' => $pendingDonations
        ]);
    }

    public function requests(): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        $pdo = Database::getConnection();
        $statusFilter = $this->request->input('status', '');

        $sql = "SELECT br.*,
                (SELECT GROUP_CONCAT(DISTINCT bg.group_name ORDER BY bg.blood_group_id SEPARATOR ', ')
                 FROM request_items ri
                 JOIN blood_groups bg ON bg.blood_group_id = ri.blood_group_id
                 WHERE ri.request_id = br.request_id) AS blood_types
            FROM blood_requests br
            WHERE br.hospital_id = :hosp_id";
        $params = [':hosp_id' => $staff['hospital_id']];

        if ($statusFilter && in_array($statusFilter, ['PENDING', 'MATCHING', 'PARTIALLY_FULFILLED', 'FULFILLED', 'CANCELLED', 'EXPIRED'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $statusFilter;
        }

        $sql .= " ORDER BY request_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        $this->render('hospital/requests', [
            'title' => 'Blood Requests — BloodLink',
            'staff' => $staff,
            'requests' => $requests,
            'currentFilter' => $statusFilter
        ]);
    }

    public function createRequest(): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        if ($staff['approval_status'] !== 'APPROVED') {
            Session::setFlash('warning', 'Your hospital account is awaiting administrator approval. You cannot submit blood requests at this time.');
            $this->redirect('/hospital/dashboard');
            return;
        }

        $pdo = Database::getConnection();
        $bloodGroups = $pdo->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();

        $this->render('hospital/create_request', [
            'title' => 'Create Blood Request — BloodLink',
            'staff' => $staff,
            'bloodGroups' => $bloodGroups
        ]);
    }

    public function doCreateRequest(): void {
        $this->validateCsrf();
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        if ($staff['approval_status'] !== 'APPROVED') {
            Session::setFlash('danger', 'Unauthorized request creation.');
            $this->redirect('/hospital/dashboard');
            return;
        }

        $reqType  = $this->request->input('request_type', 'EMERGENCY');
        $urgency  = $this->request->input('urgency', 'HIGH');
        $reqDate  = $this->request->input('required_date', '');
        $reqTime  = $this->request->input('required_time', null);
        $reason   = trim($this->request->input('reason', ''));
        $notes    = trim($this->request->input('special_notes', ''));

        // Items arrays
        $groupIds   = $this->request->input('blood_group_id', []);
        $components = $this->request->input('component_type', []);
        $quantities = $this->request->input('quantity_requested', []);

        if (!$reqDate || !$reason || empty($groupIds)) {
            Session::setFlash('danger', 'Please provide required dates, clinical reason, and at least one blood component line item.');
            $this->redirect('/hospital/requests/create');
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO blood_requests (
                    hospital_id, requested_by, request_type, urgency, status,
                    required_date, required_time, reason, special_notes, request_date
                ) VALUES (
                    :hosp_id, :staff_id, :req_type, :urgency, 'PENDING',
                    :req_date, :req_time, :reason, :notes, NOW()
                )
            ");
            $stmt->execute([
                ':hosp_id'  => $staff['hospital_id'],
                ':staff_id' => $staff['staff_id'],
                ':req_type' => $reqType,
                ':urgency'  => $urgency,
                ':req_date' => $reqDate,
                ':req_time' => $reqTime ?: null,
                ':reason'   => $reason,
                ':notes'    => $notes
            ]);
            $requestId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO request_items (request_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled)
                VALUES (:req_id, :group_id, :comp, :qty, 0.00)
            ");

            for ($i = 0; $i < count($groupIds); $i++) {
                $gId = (int)$groupIds[$i];
                $cType = $components[$i] ?? 'WHOLE_BLOOD';
                $qty = (float)($quantities[$i] ?? 450.00);

                if ($gId > 0 && $qty > 0) {
                    $itemStmt->execute([
                        ':req_id'   => $requestId,
                        ':group_id' => $gId,
                        ':comp'     => $cType,
                        ':qty'      => $qty
                    ]);
                }
            }

            AuditService::log('CREATE_REQUEST', 'blood_requests', $requestId, null, ['urgency' => $urgency, 'type' => $reqType], $user['user_id']);

            $pdo->commit();
            Session::setFlash('success', "Blood Request #{$requestId} successfully submitted.");
            $this->redirect('/hospital/requests/view/' . $requestId);
        } catch (Exception $e) {
            $pdo->rollBack();
            Session::setFlash('danger', 'Failed to create request: ' . $e->getMessage());
            $this->redirect('/hospital/requests/create');
        }
    }

    public function viewRequest(int $id): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        $pdo = Database::getConnection();

        // Multi-tenancy check
        $stmt = $pdo->prepare("
            SELECT br.*, h.hospital_name, hs.staff_name, hs.designation
            FROM blood_requests br
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            JOIN hospital_staff hs ON br.requested_by = hs.staff_id
            WHERE br.request_id = :id AND br.hospital_id = :hosp_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':hosp_id' => $staff['hospital_id']]);
        $request = $stmt->fetch();

        if (!$request) {
            Session::setFlash('danger', 'Request not found or access denied.');
            $this->redirect('/hospital/requests');
            return;
        }

        // Get items
        $itemStmt = $pdo->prepare("
                        SELECT ri.*, bg.group_name,
                                     COALESCE((
                                             SELECT SUM(don.quantity_ml)
                                             FROM donations don
                                             JOIN donor_matches dm ON dm.match_id = don.donor_match_id
                                             WHERE dm.request_item_id = ri.request_item_id
                                                 AND don.donation_status = 'COMPLETED'
                                                 AND don.screening_status = 'PASSED'
                                     ), 0) AS donor_collected
            FROM request_items ri
            JOIN blood_groups bg ON ri.blood_group_id = bg.blood_group_id
            WHERE ri.request_id = :id
        ");
        $itemStmt->execute([':id' => $id]);
        $items = $itemStmt->fetchAll();

         // Get donor matches and contact details for hospital staff
        $matchStmt = $pdo->prepare("
            SELECT dm.match_id, dm.match_score, dm.match_status, dm.notified_at, dm.response_at,
                   bg.group_name, d.city, d.full_name, u.phone,
                   don.donation_id, don.donation_status, don.donation_type, don.quantity_ml
            FROM donor_matches dm
            JOIN request_items ri ON dm.request_item_id = ri.request_item_id
            JOIN donors d ON dm.donor_id = d.donor_id
            JOIN users u ON d.user_id = u.user_id
            JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
            LEFT JOIN donations don ON don.donor_match_id = dm.match_id
            WHERE ri.request_id = :id
            ORDER BY dm.match_score DESC
        ");
        $matchStmt->execute([':id' => $id]);
        $matches = $matchStmt->fetchAll();

        // Get fulfillments
        $fulStmt = $pdo->prepare("
            SELECT f.*, u.username AS fulfilled_by_name
            FROM fulfillments f
            JOIN users u ON f.fulfilled_by = u.user_id
            WHERE f.request_id = :id
            ORDER BY f.created_at DESC
        ");
        $fulStmt->execute([':id' => $id]);
        $fulfillments = $fulStmt->fetchAll();

        $this->render('hospital/view_request', [
            'title' => "Blood Request #{$id} — BloodLink",
            'staff' => $staff,
            'request' => $request,
            'items' => $items,
            'matches' => $matches,
            'fulfillments' => $fulfillments
        ]);
    }

    public function fulfillRequest(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        if ($staff['approval_status'] !== 'APPROVED') {
            Session::setFlash('danger', 'Your hospital account is not approved to execute fulfillments.');
            $this->redirect('/hospital/requests/view/' . $id);
            return;
        }

        // Atomic fulfillment transaction
        $result = FulfillmentService::processFulfillment($id, $user['user_id'], (int)$staff['hospital_id']);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('warning', $result['message']);
        }

        $this->redirect('/hospital/requests/view/' . $id);
    }

    public function confirmDonation(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        if (!$staff || $staff['approval_status'] !== 'APPROVED' || $staff['staff_status'] !== 'ACTIVE') {
            Session::setFlash('danger', 'Only active staff at an approved hospital can confirm a donation.');
            $this->redirect('/hospital/dashboard');
            return;
        }

        $pdo = Database::getConnection();
        $requestStmt = $pdo->prepare("
            SELECT br.request_id
            FROM donations don
            JOIN donor_matches dm ON dm.match_id = don.donor_match_id
            JOIN request_items ri ON ri.request_item_id = dm.request_item_id
            JOIN blood_requests br ON br.request_id = ri.request_id
            WHERE don.donation_id = :donation_id AND br.hospital_id = :hospital_id
            LIMIT 1
        ");
        $requestStmt->execute([
            ':donation_id' => $id,
            ':hospital_id' => $staff['hospital_id']
        ]);
        $requestId = $requestStmt->fetchColumn();

        $result = DonationService::confirmMatchedDonation(
            $id,
            (int)$user['user_id'],
            (int)$staff['hospital_id'],
            [
                'donation_type' => $this->request->input('donation_type', ''),
                'quantity_ml' => $this->request->input('quantity_ml', 0),
                'donation_date' => $this->request->input('donation_date', '')
            ]
        );

        Session::setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        if ($result['success']) {
            $this->redirect('/hospital/requests/view/' . $result['request_id']);
            return;
        }
        $this->redirect($requestId ? '/hospital/requests/view/' . (int)$requestId : '/hospital/dashboard');
    }

    public function matchRequest(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        $result = MatchingService::matchDonorsForRequest($id, $user['user_id']);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('warning', $result['message']);
        }

        $this->redirect('/hospital/requests/view/' . $id);
    }

    public function cancelRequest(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE blood_requests 
            SET status = 'CANCELLED' 
            WHERE request_id = :id AND hospital_id = :hosp_id AND status IN ('PENDING', 'MATCHING')
        ");
        $stmt->execute([':id' => $id, ':hosp_id' => $staff['hospital_id']]);

        if ($stmt->rowCount() > 0) {
            AuditService::log('CANCEL_REQUEST', 'blood_requests', $id, ['status' => 'PENDING'], ['status' => 'CANCELLED'], $user['user_id']);
            Session::setFlash('info', "Blood Request #{$id} has been cancelled.");
        } else {
            Session::setFlash('danger', 'Unable to cancel this request (it may already be in progress or fulfilled).');
        }

        $this->redirect('/hospital/requests/view/' . $id);
    }

    public function inventory(): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        // Anonymized regional inventory list (protecting donor PII)
        $availableBags = InventoryService::getAvailableInventory($this->request->all());
        $pdo = Database::getConnection();
        $bloodGroups = $pdo->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();

        $this->render('hospital/inventory', [
            'title' => 'Permitted Regional Inventory — BloodLink',
            'staff' => $staff,
            'availableBags' => $availableBags,
            'bloodGroups' => $bloodGroups,
            'filters' => $this->request->all()
        ]);
    }

    public function fulfillments(): void {
        $user = Session::user();
        $staff = $this->getHospitalStaff($user['user_id']);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT f.*, br.request_id, br.request_type, br.urgency,
                   COUNT(fi.fulfillment_item_id) AS total_items_count,
                   SUM(fi.quantity_issued) AS total_issued_ml
            FROM fulfillments f
            JOIN blood_requests br ON f.request_id = br.request_id
            LEFT JOIN fulfillment_items fi ON f.fulfillment_id = fi.fulfillment_id
            WHERE br.hospital_id = :hosp_id
            GROUP BY f.fulfillment_id, br.request_id, br.request_type, br.urgency
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([':hosp_id' => $staff['hospital_id']]);
        $fulfillments = $stmt->fetchAll();

        $this->render('hospital/fulfillments', [
            'title' => 'Fulfillment History — BloodLink',
            'staff' => $staff,
            'fulfillments' => $fulfillments
        ]);
    }
}
