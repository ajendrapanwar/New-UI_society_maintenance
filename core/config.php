<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =====================================================
   SESSION SETTINGS (NO AUTO LOGOUT - 7 DAYS)
===================================================== */

$sessionLifetime = 60 * 60 * 24 * 7; // 7 days

ini_set('session.gc_maxlifetime', $sessionLifetime);

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'secure'   => false, // ⚠ Change to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =====================================================
   BASIC CONFIG
===================================================== */

define('DB_HOST', 'localhost');
define('DB_NAME', 'Society');
define('DB_USER', 'root');
define('DB_PASS', '1234');

define('BASE_URL', '/society_maintenance/public/');

date_default_timezone_set('Asia/Kolkata');


/* =====================================================
   DATABASE CONNECTION
===================================================== */

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}


/* =====================================================
   DEFAULT ADMIN CREATION (RUN ONCE)
===================================================== */

try {

    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {

        $adminEmail = 'admin@society.com';

        $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkAdmin->execute([$adminEmail]);

        if ($checkAdmin->rowCount() === 0) {

            $pdo->prepare(
                "INSERT INTO users (name, email, password, role)
                 VALUES (?, ?, ?, 'admin')"
            )->execute([
                'Admin',
                $adminEmail,
                password_hash('password', PASSWORD_DEFAULT)
            ]);
        }
    }
} catch (PDOException $e) {
    die("Setup error: " . $e->getMessage());
}


/* =====================================================
   ROLE PROTECTION FUNCTION
===================================================== */

function requireRole(array $roles)
{
    // Not logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "index.php");
        exit;
    }

    // Logged in but wrong role
    if (!in_array($_SESSION['user_role'], $roles)) {

        if ($_SESSION['user_role'] === 'security_guard') {
            header("Location: " . BASE_URL . "security_guard.php");
        } else {
            header("Location: " . BASE_URL . "dashboard.php");
        }

        exit;
    }
}
