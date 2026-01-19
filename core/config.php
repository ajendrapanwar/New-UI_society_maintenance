<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


//    SESSION

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'Society');
define('DB_USER', 'root');
define('DB_PASS', '1234');

define('BASE_URL', '/society_maintenance/public/');

date_default_timezone_set('Asia/Kolkata');


//    DATABASE CONNECTION

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


//    DATABASE SETUP (RUN ONCE)

try {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {

        // Import schema
        $sql = file_get_contents(__DIR__ . '/../database.sql');
        $pdo->exec($sql);

        // Create default admin
        $adminEmail = 'admin@sms.com';

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


//    GLOBAL DATA

$flatTypes = [
    '1 BHK Flat',
    '2 BHK Flat',
    '3 BHK Flat',
    '4 BHK Flat',
    '5 BHK Flat'
];
