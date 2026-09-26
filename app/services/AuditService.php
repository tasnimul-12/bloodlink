<?php
/**
 * BloodLink - Audit Logging Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';

class AuditService {
    public static function log(
        string $action,
        string $entityName,
        string|int $entityId,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?int $userId = null
    ): void {
        try {
            $pdo = Database::getConnection();

            if ($userId === null && Session::isLoggedIn()) {
                $user = Session::user();
                $userId = $user['user_id'] ?? null;
            }

            // Sync MySQL session variable so MySQL triggers have user context
            if ($userId !== null) {
                $pdo->exec("SET @app_user_id = " . (int)$userId);
            }

            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (
                    user_id,
                    action,
                    entity_name,
                    entity_id,
                    old_value,
                    new_value,
                    ip_address,
                    created_at
                ) VALUES (
                    :user_id,
                    :action,
                    :entity_name,
                    :entity_id,
                    :old_value,
                    :new_value,
                    :ip_address,
                    NOW()
                )
            ");

            $stmt->execute([
                ':user_id'     => $userId,
                ':action'      => substr($action, 0, 50),
                ':entity_name' => substr($entityName, 0, 80),
                ':entity_id'   => (string)$entityId,
                ':old_value'   => $oldValue ? json_encode($oldValue) : null,
                ':new_value'   => $newValue ? json_encode($newValue) : null,
                ':ip_address'  => substr($ip, 0, 45)
            ]);
        } catch (Exception $e) {
            // Audit failures should not crash the main application, but log for inspection
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }
}
