<?php
/**
 * BloodLink - Donor Portal Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/MatchingService.php';
require_once __DIR__ . '/../services/AuditService.php';

class DonorController extends Controller {
    private function getDonor(int $userId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT d.*, bg.group_name AS blood_group, u.email, u.phone, u.username
            FROM donors d
            JOIN blood_groups bg ON d.blood_group_id = bg.blood_group_id
            JOIN users u ON d.user_id = u.user_id
            WHERE d.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public function dashboard(): void {
        $user = Session::user();
        $donor = $this->getDonor($user['user_id']);

        if (!$donor) {
            die("Donor profile not linked to user account.");
        }

        $pdo = Database::getConnection();

        // 1. Total successful donations
        $donStmt = $pdo->prepare("
            SELECT COUNT(*) AS total_donations, COALESCE(SUM(quantity_ml), 0) AS total_volume_ml
            FROM donations
            WHERE donor_id = :donor_id AND donation_status = 'COMPLETED'
        ");
        $donStmt->execute([':donor_id' => $donor['donor_id']]);
        $donationStats = $donStmt->fetch();

        // 2. Highest Recognition Tier
        $recStmt = $pdo->prepare("
            SELECT rl.level_name, rl.minimum_donations, dr.achieved_date, rl.description
            FROM donor_recognition dr
            JOIN recognition_levels rl ON dr.recognition_level_id = rl.recognition_level_id
            WHERE dr.donor_id = :donor_id
            ORDER BY rl.minimum_donations DESC
            LIMIT 1
        ");
        $recStmt->execute([':donor_id' => $donor['donor_id']]);
        $currentTier = $recStmt->fetch();

        // Next tier calculation
        $nextTierStmt = $pdo->prepare("
            SELECT * FROM recognition_levels 
            WHERE minimum_donations > :current_count
            ORDER BY minimum_donations ASC 
            LIMIT 1
        ");
        $nextTierStmt->execute([':current_count' => (int)$donationStats['total_donations']]);
        $nextTier = $nextTierStmt->fetch();

        // 3. Recent donations
        $histStmt = $pdo->prepare("
            SELECT * FROM donations
            WHERE donor_id = :donor_id
            ORDER BY donation_date DESC
            LIMIT 5
        ");
        $histStmt->execute([':donor_id' => $donor['donor_id']]);
        $recentDonations = $histStmt->fetchAll();

        // 4. Pending emergency matching invitations
        $matchStmt = $pdo->prepare("
            SELECT dm.match_id, dm.match_score, dm.match_reason, dm.match_status, dm.notified_at,
                   ri.component_type, ri.quantity_requested,
                   br.request_id, br.urgency, br.required_date, h.hospital_name, h.city AS hospital_city
            FROM donor_matches dm
            JOIN request_items ri ON dm.request_item_id = ri.request_item_id
            JOIN blood_requests br ON ri.request_id = br.request_id
            JOIN hospitals h ON br.hospital_id = h.hospital_id
            WHERE dm.donor_id = :donor_id AND dm.match_status = 'NOTIFIED'
            ORDER BY dm.notified_at DESC
        ");
        $matchStmt->execute([':donor_id' => $donor['donor_id']]);
        $pendingMatches = $matchStmt->fetchAll();

        // 5. Notifications
        $notifStmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $notifStmt->execute([':user_id' => $user['user_id']]);
        $notifications = $notifStmt->fetchAll();

        $this->render('donor/dashboard', [
            'title' => 'Donor Dashboard — BloodLink',
            'donor' => $donor,
            'stats' => $donationStats,
            'currentTier' => $currentTier,
            'nextTier' => $nextTier,
            'recentDonations' => $recentDonations,
            'pendingMatches' => $pendingMatches,
            'notifications' => $notifications
        ]);
    }

    public function profile(): void {
        $user = Session::user();
        $donor = $this->getDonor($user['user_id']);

        $this->render('donor/profile', [
            'title' => 'Donor Profile — BloodLink',
            'donor' => $donor
        ]);
    }

    public function updateProfile(): void {
        $this->validateCsrf();
        $user = Session::user();
        $donor = $this->getDonor($user['user_id']);

        $city = trim($this->request->input('city', ''));
        $address = trim($this->request->input('address', ''));
        $availability = $this->request->input('availability_status', 'AVAILABLE');

        if (!in_array($availability, ['AVAILABLE', 'UNAVAILABLE'])) {
            $availability = 'AVAILABLE';
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE donors 
            SET city = :city, address = :address, availability_status = :avail
            WHERE donor_id = :id
        ");
        $stmt->execute([
            ':city'    => $city,
            ':address' => $address,
            ':avail'   => $availability,
            ':id'      => $donor['donor_id']
        ]);

        AuditService::log('UPDATE_PROFILE', 'donors', $donor['donor_id'], [
            'availability_status' => $donor['availability_status'],
            'city' => $donor['city']
        ], [
            'availability_status' => $availability,
            'city' => $city
        ], $user['user_id']);

        Session::setFlash('success', 'Your profile and availability status have been updated.');
        $this->redirect('/donor/profile');
    }

    public function history(): void {
        $user = Session::user();
        $donor = $this->getDonor($user['user_id']);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM donations 
            WHERE donor_id = :donor_id 
            ORDER BY donation_date DESC
        ");
        $stmt->execute([':donor_id' => $donor['donor_id']]);
        $donations = $stmt->fetchAll();

        $this->render('donor/history', [
            'title' => 'Donation History — BloodLink',
            'donor' => $donor,
            'donations' => $donations
        ]);
    }

    public function notifications(): void {
        $user = Session::user();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $user['user_id']]);
        $notifications = $stmt->fetchAll();

        // Mark all as read
        $markStmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = :user_id AND is_read = FALSE");
        $markStmt->execute([':user_id' => $user['user_id']]);

        $this->render('donor/notifications', [
            'title' => 'My Notifications — BloodLink',
            'notifications' => $notifications
        ]);
    }

    public function respondMatch(): void {
        $this->validateCsrf();
        $user = Session::user();
        $donor = $this->getDonor($user['user_id']);

        $matchId = (int)$this->request->input('match_id', 0);
        $action  = strtoupper($this->request->input('action', ''));

        if (!in_array($action, ['ACCEPT', 'DECLINE'])) {
            Session::setFlash('danger', 'Invalid action response.');
            $this->redirect('/donor/dashboard');
            return;
        }

        $result = MatchingService::respondToMatch($matchId, $donor['donor_id'], $action);

        if ($result['success']) {
            Session::setFlash('success', $result['message']);
        } else {
            Session::setFlash('danger', $result['message']);
        }

        $this->redirect('/donor/dashboard');
    }
}
