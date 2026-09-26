<?php
/**
 * BloodLink - User Notifications
 */

require_once __DIR__ . '/../core/Controller.php';

class NotificationsController extends Controller {
    public function index(): void {
        $user = Session::user();
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $user['user_id']]);
        $notifications = $stmt->fetchAll();

        $markStmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = :user_id AND is_read = FALSE");
        $markStmt->execute([':user_id' => $user['user_id']]);

        $this->render('donor/notifications', [
            'title' => 'My Notifications — BloodLink',
            'notifications' => $notifications
        ]);
    }
}