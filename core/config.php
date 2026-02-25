<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
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


function requireRole(array $roles)
{
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], $roles)) {

        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Session Expired</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center vh-100">

            <div class="text-center p-5 bg-white shadow rounded">
                <h2 class="text-danger fw-bold">⚠️ Session Expired</h2>
                <p class="text-muted mt-2">
                    Your session is out, please login again !!
                </p>

                <a href="<?= BASE_URL ?>index.php" class="btn btn-primary mt-3">
                    Login
                </a>
            </div>

        </body>
        </html>
        <?php
        exit;
    }
}

