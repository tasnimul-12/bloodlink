<?php
/**
 * BloodLink - PDO Database Connection Manager
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // Genuine prepared statements in MySQL
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // In production or demo, avoid dumping raw DB passwords; log and show friendly error
                error_log("Database connection failed: " . $e->getMessage());
                die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #ffccd2;background:#fff5f6;border-radius:8px;color:#900;'>" .
                    "<h2>BloodLink Database Error</h2>" .
                    "<p>Could not connect to the MySQL database <code>bloodlink_db</code>.</p>" .
                    "<p>Please ensure XAMPP MySQL is running and that <code>database/BloodLink_finalsql.sql</code> and seeds have been imported.</p>" .
                    "<small>Error detail: " . htmlspecialchars($e->getMessage()) . "</small>" .
                    "</div>");
            }
        }

        return self::$instance;
    }
}
