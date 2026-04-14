<?php
/**
 * One-time script to fix the seeded account passwords.
 * Run once at: http://localhost/maintenance-system/fix-passwords.php
 * Then DELETE this file.
 */
require_once __DIR__ . '/config/database.php';

$db = getDBConnection();

// Generate correct hashes
$admin_hash = password_hash('admin123', PASSWORD_BCRYPT);
$manager_hash = password_hash('manager123', PASSWORD_BCRYPT);

// Update admin
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$admin_hash, 'admin@facility.com']);

// Update manager
$stmt->execute([$manager_hash, 'manager@facility.com']);

echo "<h2 style='font-family:Poppins,sans-serif; color:green;'>✅ Passwords updated successfully!</h2>";
echo "<p><strong>Admin:</strong> admin@facility.com / admin123</p>";
echo "<p><strong>Manager:</strong> manager@facility.com / manager123</p>";
echo "<br><p>⚠️ <strong>Now delete this file</strong> (fix-passwords.php) for security.</p>";
echo "<br><a href='index.php'>← Go to Login</a>";
?>
