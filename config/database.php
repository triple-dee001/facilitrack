<?php
/**
 * Database Configuration
 * Digital Facility Maintenance Reporting System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'facility_maintenance');
define('DB_USER', 'root');
define('DB_PASS', '');         // Default XAMPP MySQL has no password
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'FaciliTrack');
define('APP_URL', 'http://localhost/maintenance-system');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

/**
 * Get PDO database connection
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    return $pdo;
}
?>
