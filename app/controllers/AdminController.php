<?php
/**
 * BloodLink - Central Administrator Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/InventoryService.php';
require_once __DIR__ . '/../services/AuditService.php';

class AdminController extends Controller {
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

        $this->render('admin/hospitals', [
            'title' => 'Hospital Verification & Management — BloodLink',
            'hospitals' => $hospitals
        ]);
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

        $this->render('admin/inventory', [
            'title' => 'Inventory & FEFO Management — BloodLink',
            'availableBags' => $availableBags,
            'nearExpiry'    => $nearExpiry,
            'expiredBags'   => $expiredBags,
            'locations'     => $locations,
            'bloodGroups'   => $bloodGroups,
            'filters'       => $this->request->all()
        ]);
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
