<?php
/**
 * BloodLink - Public Home and Documentation Controller
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/CompatibilityService.php';
require_once __DIR__ . '/../services/InventoryService.php';

class HomeController extends Controller {
    public function index(): void {
        $inventorySummary = InventoryService::getInventorySummary();
        $pdo = Database::getConnection();

        // High level stats for landing page
        $stats = [
            'total_donors'   => $pdo->query("SELECT COUNT(*) FROM donors")->fetchColumn(),
            'total_units'    => $pdo->query("SELECT COUNT(*) FROM blood_bags WHERE status = 'AVAILABLE' AND expiry_date >= CURRENT_DATE")->fetchColumn(),
            'total_hospitals'=> $pdo->query("SELECT COUNT(*) FROM hospitals WHERE approval_status = 'APPROVED'")->fetchColumn(),
            'total_donations'=> $pdo->query("SELECT COUNT(*) FROM donations WHERE donation_status = 'COMPLETED'")->fetchColumn()
        ];

        $compatibilityMatrix = CompatibilityService::getCompatibilityMatrix();

        $this->render('public/home', [
            'title' => 'BloodLink — Centralized Blood Bank & Emergency Coordination',
            'stats' => $stats,
            'inventorySummary' => $inventorySummary,
            'matrix' => $compatibilityMatrix
        ]);
    }

    public function compatibility(): void {
        $matrix = CompatibilityService::getCompatibilityMatrix();
        $this->render('public/compatibility', [
            'title' => 'Blood Group & Component Compatibility Guide — BloodLink',
            'matrix' => $matrix
        ]);
    }

    public function about(): void {
        $this->render('public/about', [
            'title' => 'About BloodLink — Donor & Hospital Coordination'
        ]);
    }

    public function events(): void {
        $pdo = Database::getConnection();
        $events = $pdo->query("
            SELECT event_id, title, invitation, description, event_date,
                   venue, address, city, contact_info, event_status
            FROM donation_events
            WHERE event_date >= NOW()
            ORDER BY event_date ASC
        ")->fetchAll();

        $this->render('public/events', [
            'title' => 'Blood Donation Events — BloodLink',
            'events' => $events
        ]);
    }
}
