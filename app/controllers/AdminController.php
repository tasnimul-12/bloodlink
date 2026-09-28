<?php
/**
 * BloodLink - Central Administrator Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/InventoryService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/MatchingService.php';

class AdminController extends Controller {
    private function stringInput(string $key): string {
        $value = $this->request->input($key, '');
        return is_string($value) || is_numeric($value) ? trim((string)$value) : '';
    }

    public function dashboard(): void {
        $pdo = Database::getConnection();

        $kpis = [
            'total_donors'      => $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn(),
            'total_hospitals'   => $pdo->query("SELECT COUNT(*) FROM hospitals")->fetchColumn(),
            'pending_hospitals' => $pdo->query("SELECT COUNT(*) FROM hospitals WHERE approval_status = 'PENDING'")->fetchColumn(),
            'available_units'   => $pdo->query("SELECT COUNT(*) FROM blood_bags WHERE status = 'AVAILABLE' AND expiry_date >= CURRENT_DATE")->fetchColumn(),
            'available_volume'  => $pdo->query("SELECT COALESCE(SUM(quantity_ml), 0) FROM blood_bags WHERE status = 'AVAILABLE' AND expiry_date >= CURRENT_DATE")->fetchColumn(),
            'near_expiry_units' => $pdo->query("SELECT COUNT(*) FROM blood_bags WHERE status = 'AVAILABLE' AND expiry_date >= CURRENT_DATE AND DATEDIFF(expiry_date, CURRENT_DATE) <= 5")->fetchColumn(),
            'critical_requests' => $pdo->query("SELECT COUNT(*) FROM blood_requests WHERE urgency = 'CRITICAL' AND status IN ('PENDING', 'MATCHING')")->fetchColumn()
        ];

        $stockSummary = InventoryService::getInventorySummary();
        $nearExpiry = InventoryService::getNearExpiryInventory(5);

        // Pending hospital approvals
        $pendingHospitals = $pdo->query("
            SELECT * FROM hospitals 
            WHERE approval_status = 'PENDING' 
            ORDER BY created_at ASC
        ")->fetchAll();

        // Recent audit activity
        $recentAudit = $pdo->query("
            SELECT a.*, u.username 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.user_id 
            ORDER BY a.created_at DESC 
            LIMIT 6
        ")->fetchAll();

        $this->render('admin/dashboard', [
            'title' => 'System Administration — BloodLink',
            'kpis' => $kpis,
            'stockSummary' => $stockSummary,
            'nearExpiry' => $nearExpiry,
            'pendingHospitals' => $pendingHospitals,
            'recentAudit' => $recentAudit
        ]);
    }

    public function hospitals(): void {
        $pdo = Database::getConnection();
        $hospitals = $pdo->query("
            SELECT h.*, u.username AS approved_by_user, COUNT(hs.staff_id) AS staff_count
            FROM hospitals h
            LEFT JOIN users u ON h.approved_by = u.user_id
            LEFT JOIN hospital_staff hs ON h.hospital_id = hs.hospital_id
            GROUP BY h.hospital_id
            ORDER BY h.created_at DESC
        ")->fetchAll();

        $staffMembers = $pdo->query("
            SELECT hs.staff_id, hs.staff_name, hs.designation, hs.staff_status,
                   h.hospital_name, h.approval_status, u.username, u.email
            FROM hospital_staff hs
            JOIN hospitals h ON h.hospital_id = hs.hospital_id
            JOIN users u ON u.user_id = hs.user_id
            ORDER BY h.hospital_name, hs.staff_name
        ")->fetchAll();

        $this->render('admin/hospitals', [
            'title' => 'Hospital Verification & Management — BloodLink',
            'hospitals' => $hospitals,
            'staffMembers' => $staffMembers
        ]);
    }

    public function updateStaffStatus(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $newStatus = strtoupper($this->request->input('status', ''));

        if (!in_array($newStatus, ['ACTIVE', 'INACTIVE'], true)) {
            Session::setFlash('danger', 'Invalid staff status specified.');
            $this->redirect('/admin/hospitals');
            return;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT hs.staff_name, hs.staff_status, h.hospital_name, h.approval_status
            FROM hospital_staff hs
            JOIN hospitals h ON h.hospital_id = hs.hospital_id
            WHERE hs.staff_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $staff = $stmt->fetch();

        if (!$staff) {
            Session::setFlash('danger', 'Hospital staff member not found.');
            $this->redirect('/admin/hospitals');
            return;
        }
        if ($newStatus === 'ACTIVE' && $staff['approval_status'] !== 'APPROVED') {
            Session::setFlash('warning', 'Approve the hospital before activating its staff accounts.');
            $this->redirect('/admin/hospitals');
            return;
        }

        $update = $pdo->prepare('UPDATE hospital_staff SET staff_status = :status WHERE staff_id = :id');
        $update->execute([':status' => $newStatus, ':id' => $id]);
        AuditService::log(
            'HOSPITAL_STAFF_STATUS_UPDATE',
            'hospital_staff',
            $id,
            ['staff_status' => $staff['staff_status']],
            ['staff_status' => $newStatus],
            (int)$user['user_id']
        );

        Session::setFlash('success', "{$staff['staff_name']} at {$staff['hospital_name']} is now {$newStatus}.");
        $this->redirect('/admin/hospitals');
    }

    public function updateHospitalStatus(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $newStatus = strtoupper($this->request->input('status', ''));

        if (!in_array($newStatus, ['APPROVED', 'REJECTED', 'SUSPENDED', 'PENDING'])) {
            Session::setFlash('danger', 'Invalid status specified.');
            $this->redirect('/admin/hospitals');
            return;
        }

        $pdo = Database::getConnection();
        $oldStmt = $pdo->prepare("SELECT hospital_name, approval_status FROM hospitals WHERE hospital_id = :id");
        $oldStmt->execute([':id' => $id]);
        $old = $oldStmt->fetch();

        if (!$old) {
            Session::setFlash('danger', 'Hospital not found.');
            $this->redirect('/admin/hospitals');
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE hospitals 
            SET approval_status = :status, approved_by = :user_id, approved_at = NOW() 
            WHERE hospital_id = :id
        ");
        $stmt->execute([
            ':status'  => $newStatus,
            ':user_id' => $user['user_id'],
            ':id'      => $id
        ]);

        AuditService::log(
            'HOSPITAL_STATUS_UPDATE',
            'hospitals',
            $id,
            ['approval_status' => $old['approval_status']],
            ['approval_status' => $newStatus],
            $user['user_id']
        );

        Session::setFlash('success', "Hospital '{$old['hospital_name']}' status changed to {$newStatus}.");
        $this->redirect('/admin/hospitals');
    }

    public function inventory(): void {
        $pdo = Database::getConnection();

        $availableBags = InventoryService::getAvailableInventory($this->request->all());
        $nearExpiry    = InventoryService::getNearExpiryInventory(5);
        $expiredBags   = InventoryService::getExpiredInventory();
        $locations     = InventoryService::getStorageLocations();
        $bloodGroups   = $pdo->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();
        $donors = $pdo->query("
            SELECT d.donor_id, d.full_name, bg.group_name
            FROM donors d
            JOIN blood_groups bg ON bg.blood_group_id = d.blood_group_id
            JOIN users u ON u.user_id = d.user_id
            WHERE u.account_status = 'ACTIVE'
            ORDER BY d.full_name ASC
        ")->fetchAll();

        $this->render('admin/inventory', [
            'title' => 'Inventory & FEFO Management — BloodLink',
            'availableBags' => $availableBags,
            'nearExpiry'    => $nearExpiry,
            'expiredBags'   => $expiredBags,
            'locations'     => $locations,
            'donors'        => $donors,
            'bloodGroups'   => $bloodGroups,
            'filters'       => $this->request->all()
        ]);
    }

    public function accessionBloodBag(): void {
        $this->validateCsrf();
        $user = Session::user();
        $result = InventoryService::accessionAdminDonation($this->request->all(), (int)$user['user_id']);

        Session::setFlash($result['success'] ? 'success' : 'danger', $result['message']);
        $this->redirect('/admin/inventory');
    }

    public function requests(): void {
        $pdo = Database::getConnection();
        $hospitals = $pdo->query("
            SELECT DISTINCT h.hospital_id, h.hospital_name, h.city
            FROM hospitals h
            JOIN hospital_staff hs ON hs.hospital_id = h.hospital_id
            WHERE h.approval_status = 'APPROVED' AND hs.staff_status = 'ACTIVE'
            ORDER BY h.hospital_name ASC
        ")->fetchAll();
        $bloodGroups = $pdo->query("SELECT blood_group_id, group_name FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();
        $requests = $pdo->query("
            SELECT br.request_id, br.request_type, br.urgency, br.status, br.required_date,
                   br.request_date, h.hospital_name,
                   GROUP_CONCAT(CONCAT(bg.group_name, ' ', ri.component_type, ' ',
                       FORMAT(ri.quantity_requested, 0), ' mL') ORDER BY ri.request_item_id SEPARATOR ', ') AS requested_items,
                   (SELECT COUNT(*) FROM donor_matches dm
                    JOIN request_items match_items ON match_items.request_item_id = dm.request_item_id
                    WHERE match_items.request_id = br.request_id AND dm.match_status = 'NOTIFIED') AS pending_donor_invites
            FROM blood_requests br
            JOIN hospitals h ON h.hospital_id = br.hospital_id
            JOIN request_items ri ON ri.request_id = br.request_id
            JOIN blood_groups bg ON bg.blood_group_id = ri.blood_group_id
            GROUP BY br.request_id
            ORDER BY br.request_date DESC
            LIMIT 50
        ")->fetchAll();

        $this->render('admin/requests', [
            'title' => 'Admin Blood Requests — BloodLink',
            'hospitals' => $hospitals,
            'bloodGroups' => $bloodGroups,
            'requests' => $requests
        ]);
    }

    public function createBloodRequest(): void {
        $this->validateCsrf();
        $user = Session::user();
        $hospitalValue = $this->request->input('hospital_id');
        $groupValue = $this->request->input('blood_group_id');
        $quantityValue = $this->request->input('quantity_requested');
        $hospitalId = filter_var(is_scalar($hospitalValue) ? $hospitalValue : null, FILTER_VALIDATE_INT);
        $groupId = filter_var(is_scalar($groupValue) ? $groupValue : null, FILTER_VALIDATE_INT);
        $component = strtoupper($this->stringInput('component_type'));
        $quantity = filter_var(is_scalar($quantityValue) ? $quantityValue : null, FILTER_VALIDATE_FLOAT);
        $requestType = strtoupper($this->stringInput('request_type'));
        $urgency = strtoupper($this->stringInput('urgency'));
        $requiredDateInput = $this->stringInput('required_date');
        $requiredTime = $this->stringInput('required_time');
        $reason = $this->stringInput('reason');
        $requiredDate = DateTimeImmutable::createFromFormat('!Y-m-d', $requiredDateInput);
        $today = new DateTimeImmutable('today');
        $validTime = $requiredTime === '' || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $requiredTime) === 1;
        $valid = $hospitalId !== false
            && $groupId !== false
            && in_array($component, ['WHOLE_BLOOD', 'RBC', 'PLASMA', 'PLATELET'], true)
            && $quantity !== false && is_finite((float)$quantity) && $quantity > 0 && $quantity <= 99999.99
            && in_array($requestType, ['EMERGENCY', 'ROUTINE', 'SURGERY', 'MATERNITY', 'OTHER'], true)
            && in_array($urgency, ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'], true)
            && $requiredDate && $requiredDate->format('Y-m-d') === $requiredDateInput && $requiredDate >= $today
            && $validTime
            && $reason !== '' && strlen($reason) <= 500;

        if (!$valid) {
            Session::setFlash('danger', 'Enter a valid approved hospital, blood requirement, future date, and clinical reason.');
            $this->redirect('/admin/requests');
            return;
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $staffStmt = $pdo->prepare("
                SELECT hs.staff_id, hs.hospital_id
                FROM hospital_staff hs
                JOIN hospitals h ON h.hospital_id = hs.hospital_id
                WHERE hs.hospital_id = :hospital_id
                  AND hs.staff_status = 'ACTIVE'
                  AND h.approval_status = 'APPROVED'
                ORDER BY hs.staff_id ASC
                LIMIT 1
                FOR UPDATE
            ");
            $staffStmt->execute([':hospital_id' => $hospitalId]);
            $staff = $staffStmt->fetch();
            $groupStmt = $pdo->prepare('SELECT blood_group_id FROM blood_groups WHERE blood_group_id = :group_id');
            $groupStmt->execute([':group_id' => $groupId]);
            if (!$staff || !$groupStmt->fetchColumn()) {
                $pdo->rollBack();
                Session::setFlash('danger', 'Choose an approved hospital with active staff and a valid blood group.');
                $this->redirect('/admin/requests');
                return;
            }

            $requestStmt = $pdo->prepare("
                INSERT INTO blood_requests (
                    hospital_id, requested_by, request_type, urgency, status,
                    required_date, required_time, reason, request_date
                ) VALUES (
                    :hospital_id, :staff_id, :request_type, :urgency, 'PENDING',
                    :required_date, :required_time, :reason, NOW()
                )
            ");
            $requestStmt->execute([
                ':hospital_id' => $hospitalId,
                ':staff_id' => $staff['staff_id'],
                ':request_type' => $requestType,
                ':urgency' => $urgency,
                ':required_date' => $requiredDateInput,
                ':required_time' => $requiredTime !== '' ? $requiredTime : null,
                ':reason' => $reason
            ]);
            $requestId = (int)$pdo->lastInsertId();
            $itemStmt = $pdo->prepare("
                INSERT INTO request_items (
                    request_id, blood_group_id, component_type, quantity_requested, quantity_fulfilled
                ) VALUES (:request_id, :group_id, :component, :quantity, 0)
            ");
            $itemStmt->execute([
                ':request_id' => $requestId,
                ':group_id' => $groupId,
                ':component' => $component,
                ':quantity' => $quantity
            ]);
            AuditService::log('ADMIN_CREATE_BLOOD_REQUEST', 'blood_requests', $requestId, null, [
                'hospital_id' => $hospitalId,
                'blood_group_id' => $groupId,
                'component_type' => $component,
                'quantity_requested' => $quantity
            ], (int)$user['user_id']);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin blood request creation failed: ' . $e->getMessage());
            Session::setFlash('danger', 'The blood request could not be created. No changes were saved.');
            $this->redirect('/admin/requests');
            return;
        }

        try {
            $matching = MatchingService::matchDonorsForRequest($requestId, (int)$user['user_id']);
        } catch (Throwable $e) {
            error_log('Admin request donor matching failed: ' . $e->getMessage());
            $matching = ['success' => false, 'message' => 'Donor matching failed unexpectedly.'];
        }
        $message = "Hospital blood request #{$requestId} was created.";
        if ($matching['success']) {
            $message .= ' ' . $matching['message'];
        } else {
            $message .= ' Donor matching can be retried later: ' . $matching['message'];
        }
        Session::setFlash($matching['success'] ? 'success' : 'warning', $message);
        $this->redirect('/admin/requests');
    }

    public function matchAdminRequest(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $result = MatchingService::matchDonorsForRequest($id, (int)$user['user_id']);
        Session::setFlash($result['success'] ? 'success' : 'warning', $result['message']);
        $this->redirect('/admin/requests');
    }

    public function events(): void {
        $pdo = Database::getConnection();
        $events = $pdo->query("
            SELECT * FROM donation_events
            ORDER BY CASE WHEN event_status = 'SCHEDULED' AND event_date >= NOW() THEN 0 ELSE 1 END,
                     event_date ASC
        ")->fetchAll();
        $this->render('admin/events', [
            'title' => 'Donation Events — BloodLink',
            'events' => $events
        ]);
    }

    public function createEvent(): void {
        $this->validateCsrf();
        $user = Session::user();
        $title = $this->stringInput('title');
        $invitation = $this->stringInput('invitation');
        $description = $this->stringInput('description');
        $eventDateInput = $this->stringInput('event_date');
        $eventDate = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $eventDateInput);
        $venue = $this->stringInput('venue');
        $address = $this->stringInput('address');
        $city = $this->stringInput('city');
        $contactInfo = $this->stringInput('contact_info');
        $now = new DateTimeImmutable();

        if (!$eventDate || $eventDate->format('Y-m-d\\TH:i') !== $eventDateInput || $eventDate <= $now
            || $title === '' || strlen($title) > 140
            || $invitation === '' || strlen($invitation) > 180
            || $description === '' || strlen($description) > 10000
            || $venue === '' || strlen($venue) > 180
            || $address === '' || strlen($address) > 255
            || $city === '' || strlen($city) > 80
            || strlen($contactInfo) > 160) {
            Session::setFlash('danger', 'Enter complete event details and schedule it for a future date and time.');
            $this->redirect('/admin/events');
            return;
        }

        $pdo = Database::getConnection();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO donation_events (
                    title, invitation, description, event_date, venue, address,
                    city, contact_info, created_by
                ) VALUES (
                    :title, :invitation, :description, :event_date, :venue, :address,
                    :city, :contact_info, :created_by
                )
            ");
            $stmt->execute([
                ':title' => $title,
                ':invitation' => $invitation,
                ':description' => $description,
                ':event_date' => $eventDate->format('Y-m-d H:i:00'),
                ':venue' => $venue,
                ':address' => $address,
                ':city' => $city,
                ':contact_info' => $contactInfo !== '' ? $contactInfo : null,
                ':created_by' => $user['user_id']
            ]);
            $eventId = (int)$pdo->lastInsertId();
            AuditService::log('CREATE_DONATION_EVENT', 'donation_events', $eventId, null, [
                'title' => $title,
                'event_date' => $eventDate->format('Y-m-d H:i:s')
            ], (int)$user['user_id']);
            Session::setFlash('success', 'Donation event scheduled and published.');
        } catch (Throwable $e) {
            error_log('Donation event creation failed: ' . $e->getMessage());
            Session::setFlash('danger', 'The event could not be scheduled.');
        }
        $this->redirect('/admin/events');
    }

    public function cancelEvent(int $id): void {
        $this->validateCsrf();
        $user = Session::user();
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT title, event_status FROM donation_events WHERE event_id = :id");
        $stmt->execute([':id' => $id]);
        $event = $stmt->fetch();
        if (!$event || $event['event_status'] !== 'SCHEDULED') {
            Session::setFlash('warning', 'That event is not available to cancel.');
            $this->redirect('/admin/events');
            return;
        }
        $pdo->prepare("UPDATE donation_events SET event_status = 'CANCELLED' WHERE event_id = :id")
            ->execute([':id' => $id]);
        AuditService::log('CANCEL_DONATION_EVENT', 'donation_events', $id, ['event_status' => 'SCHEDULED'], ['event_status' => 'CANCELLED'], (int)$user['user_id']);
        Session::setFlash('success', "Event '{$event['title']}' was cancelled.");
        $this->redirect('/admin/events');
    }

    public function discardBag(): void {
        $this->validateCsrf();
        $user = Session::user();
        $bagId = (int)$this->request->input('blood_bag_id', 0);
        $reason = trim($this->request->input('reason', 'Expired biological unit decommissioned'));

        $result = InventoryService::discardUnit($bagId, $reason, $user['user_id']);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('danger', $result['message']);
        }

        $this->redirect('/admin/inventory');
    }

    public function transferBag(): void {
        $this->validateCsrf();
        $user = Session::user();
        $bagId = (int)$this->request->input('blood_bag_id', 0);
        $toLoc = (int)$this->request->input('to_location_id', 0);
        $reason = trim($this->request->input('reason', 'Inter-facility / cold chain transfer'));

        $result = InventoryService::transferUnit($bagId, $toLoc, $reason, $user['user_id']);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('danger', $result['message']);
        }

        $this->redirect('/admin/inventory');
    }

    public function donations(): void {
        $pdo = Database::getConnection();
        $donations = $pdo->query("
            SELECT don.*, d.full_name, bg.group_name AS blood_group, d.city
            FROM donations don
            JOIN donors d ON don.donor_id = d.donor_id
            JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
            ORDER BY don.donation_date DESC
        ")->fetchAll();

        $this->render('admin/donations', [
            'title' => 'Donation Management — BloodLink',
            'donations' => $donations
        ]);
    }

    public function donors(): void {
        $pdo = Database::getConnection();
        $city = $this->request->input('city', '');
        $groupId = $this->request->input('blood_group_id', '');
        $avail = $this->request->input('availability', '');

        $sql = "
            SELECT d.*, bg.group_name, u.email, u.phone,
                   COUNT(don.donation_id) AS total_donations
            FROM donors d
            JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
            JOIN users u ON d.user_id = u.user_id
            LEFT JOIN donations don ON d.donor_id = don.donor_id AND don.donation_status = 'COMPLETED'
            WHERE 1=1
        ";
        $params = [];

        if ($city) {
            $sql .= " AND d.city LIKE :city";
            $params[':city'] = '%' . $city . '%';
        }
        if ($groupId) {
            $sql .= " AND d.blood_group_id = :gid";
            $params[':gid'] = (int)$groupId;
        }
        if ($avail) {
            $sql .= " AND d.availability_status = :avail";
            $params[':avail'] = $avail;
        }

        $sql .= " GROUP BY d.donor_id ORDER BY d.full_name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $donors = $stmt->fetchAll();

        $bloodGroups = $pdo->query("SELECT * FROM blood_groups ORDER BY blood_group_id ASC")->fetchAll();

        $this->render('admin/donors', [
            'title' => 'Registered Donors — BloodLink',
            'donors' => $donors,
            'bloodGroups' => $bloodGroups,
            'filters' => $this->request->all()
        ]);
    }

    public function auditLogs(): void {
        $pdo = Database::getConnection();
        $action = $this->request->input('action', '');
        $entity = $this->request->input('entity', '');

        $sql = "
            SELECT a.*, u.username, u.email
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.user_id
            WHERE 1=1
        ";
        $params = [];

        if ($action) {
            $sql .= " AND a.action = :action";
            $params[':action'] = $action;
        }
        if ($entity) {
            $sql .= " AND a.entity_name = :entity";
            $params[':entity'] = $entity;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();

        $actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
        $entities = $pdo->query("SELECT DISTINCT entity_name FROM audit_logs ORDER BY entity_name ASC")->fetchAll(PDO::FETCH_COLUMN);

        $this->render('admin/audit_logs', [
            'title' => 'System Audit Logs — BloodLink',
            'logs' => $logs,
            'actions' => $actions,
            'entities' => $entities,
            'filters' => $this->request->all()
        ]);
    }

    public function reports(): void {
        $pdo = Database::getConnection();

        // 1. DBMS Showcase: Donors with Zero Donation History (LEFT JOIN + HAVING total_donations = 0)
        $zeroDonors = $pdo->query("
            SELECT 
                d.donor_id,
                d.full_name,
                bg.group_name AS blood_group,
                d.city,
                d.registration_date,
                COUNT(don.donation_id) AS total_donations
            FROM donors d
            JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
            LEFT JOIN donations don ON d.donor_id = don.donor_id
            GROUP BY d.donor_id, d.full_name, bg.group_name, d.city, d.registration_date
            HAVING total_donations = 0
            ORDER BY d.full_name ASC
        ")->fetchAll();

        // 2. DBMS Showcase: Monthly Blood Inventory Consumption (AGGREGATION + GROUP BY + HAVING)
        $consumption = $pdo->query("
            SELECT 
                DATE_FORMAT(f.issued_at, '%Y-%m') AS issue_month,
                h.hospital_name,
                COUNT(fi.fulfillment_item_id) AS total_units_received,
                SUM(fi.quantity_issued) AS total_volume_ml,
                AVG(fi.quantity_issued) AS avg_unit_volume_ml
            FROM fulfillments f
            JOIN blood_requests br ON f.request_id = br.request_id
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            JOIN fulfillment_items fi ON f.fulfillment_id = fi.fulfillment_id
            WHERE f.fulfillment_status = 'COMPLETED'
            GROUP BY issue_month, h.hospital_name
            HAVING total_volume_ml > 0
            ORDER BY issue_month DESC
        ")->fetchAll();

        // 3. DBMS Showcase: Correlated Subquery - Requests with Unfulfilled Items
        $unfulfilledRequests = $pdo->query("
            SELECT 
                br.request_id,
                h.hospital_name,
                br.request_type,
                br.urgency,
                br.status,
                br.required_date
            FROM blood_requests br
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            WHERE br.request_id IN (
                SELECT ri.request_id 
                FROM request_items ri 
                WHERE ri.quantity_fulfilled < ri.quantity_requested
            )
            ORDER BY br.urgency DESC, br.required_date ASC
        ")->fetchAll();

        // 4. Blood Group Aggregate Distribution
        $groupStats = $pdo->query("
            SELECT 
                bg.group_name,
                COUNT(bb.blood_bag_id) AS available_units,
                COALESCE(SUM(bb.quantity_ml), 0) AS total_volume_ml
            FROM blood_groups bg
            LEFT JOIN blood_bags bb ON bg.blood_group_id = bb.blood_group_id 
                 AND bb.status = 'AVAILABLE' 
                 AND bb.expiry_date >= CURRENT_DATE
            GROUP BY bg.group_name
            ORDER BY bg.blood_group_id ASC
        ")->fetchAll();

        $this->render('admin/reports', [
            'title' => 'DBMS Project Technical Reports — BloodLink',
            'zeroDonors' => $zeroDonors,
            'consumption' => $consumption,
            'unfulfilledRequests' => $unfulfilledRequests,
            'groupStats' => $groupStats
        ]);
    }

    public function settings(): void {
        $pdo = Database::getConnection();
        $settings = $pdo->query("SELECT * FROM system_settings ORDER BY setting_id ASC")->fetchAll();

        $this->render('admin/settings', [
            'title' => 'System Settings — BloodLink',
            'settings' => $settings
        ]);
    }

    public function updateSettings(): void {
        $this->validateCsrf();
        $user = Session::user();
        $pdo = Database::getConnection();

        $settings = $this->request->input('settings', []);

        foreach ($settings as $key => $val) {
            $stmt = $pdo->prepare("
                UPDATE system_settings 
                SET setting_value = :val, updated_by = :user_id, updated_at = NOW() 
                WHERE setting_key = :key
            ");
            $stmt->execute([
                ':val'     => trim($val),
                ':user_id' => $user['user_id'],
                ':key'     => $key
            ]);
        }

        AuditService::log('UPDATE_SETTINGS', 'system_settings', 1, null, $settings, $user['user_id']);

        Session::setFlash('success', 'System settings have been updated.');
        $this->redirect('/admin/settings');
    }
}
