<?php
/**
 * BloodLink - Core Application Configuration
 */

// Application Constants
define('APP_NAME', 'BloodLink');
define('APP_TAGLINE', 'Blood Bank & Emergency Blood Coordination System');
define('APP_VERSION', '1.0.0');

// Environment / Base URL
define('BASE_URL', '/bloodlink'); // When served via XAMPP Apache: http://localhost/bloodlink
// Alternatively when served via PHP built-in server at root:
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
if ($scriptDir === '/' || $scriptDir === '') {
    define('APP_ROOT_URL', '');
} else {
    define('APP_ROOT_URL', rtrim($scriptDir, '/'));
}

// Session Configuration
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_NAME', 'BLOODLINK_SESSID');

// Database Credentials (Default XAMPP)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'bloodlink_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Default system settings
define('DEFAULT_MIN_DONATION_INTERVAL_DAYS', 90);
