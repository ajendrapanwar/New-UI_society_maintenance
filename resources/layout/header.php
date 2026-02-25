<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';
include __DIR__ . '/flash.php';


requireRole(['admin', 'cashier', 'user']);

$name = $_SESSION['user_name'] ?? 'Admin';
$initials = strtoupper(substr($name, 0, 1));

// Fetch notifications
$now = date('Y-m-d H:i:s');

$notifStmt = $pdo->prepare("
        SELECT * 
        FROM notifications 
        WHERE (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY id DESC
    ");
$notifStmt->execute([$now, $now]);

$latestNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($latestNotifications);

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="../../assets/css/styles.css">

    <style>
        /* Hover Effect */
        .user-dropdown .dropdown-item {
            border-radius: 5px;
        }

        .user-dropdown .dropdown-item:hover {
            background: #e0e0e0;
        }
    </style>

</head>

<body>

    <!-- Header -->
    <header class="top-nav">
        <div id="mobile-toggle" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars-staggered"></i>
        </div>

        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown">
                <button class="btn d-flex align-items-center gap-3 border-0 bg-transparent p-0"
                    type="button"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">

                    <?php if ($_SESSION['user_role'] === 'admin'): ?>

                        <div class="text-end d-none d-sm-block">
                            <p class="m-0 small text-muted">Admin Panel</p>
                            <p class="m-0 fw-bold">Administrator</p>
                        </div>
                        <div class="avatar-circle"><?= $initials ?></div>
                    <?php elseif ($_SESSION['user_role'] === 'cashier'): ?>

                        <div class="text-end d-none d-sm-block">
                            <p class="m-0 fw-bold fs-5"><?= htmlspecialchars($name) ?></p>
                        </div>
                        <div class="avatar-circle"><?= $initials ?></div>
                    <?php else: ?>

                        <div class="text-end d-none d-sm-block">
                            <p class="m-0 fw-bold fs-5"><?= htmlspecialchars($name) ?></p>
                        </div>
                        <div class="avatar-circle"><?= $initials ?></div>
                    <?php endif; ?>

                </button>

                <ul class="dropdown-menu dropdown-menu-end user-dropdown shadow border-0 p-0 mt-2">
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2" href="<?= BASE_URL ?>profile.php">
                            <div class="menu-icon bg-primary-soft me-3">
                                <i class="fas fa-user text-primary"></i>
                            </div>
                            <span>My Profile</span>
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2" href="<?= BASE_URL ?>change_password.php">
                            <div class="menu-icon bg-warning-soft me-3">
                                <i class="fas fa-key text-warning"></i>
                            </div>
                            <span>Change Password</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>



    <!-- SideBar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>