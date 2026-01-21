<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';

// echo '<pre>'; print_r($_SESSION); echo '</pre>';


$stmt = $pdo->prepare("
    SELECT message, link 
    FROM notifications 
    WHERE user_id = ? AND read_status = ? 
    ORDER BY id DESC
");
$stmt->execute([$_SESSION['user_id'], 'unread']);
$notification = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/styles.css">

    <!-- JS -->
    <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
</head>

<body>
    <nav class="sb-topnav navbar bg-primary navbar-expand navbar-dark">

        <!-- Brand -->
        <a class="navbar-brand ps-3 mt-1" href="<?= BASE_URL ?>dashboard.php">
            Society Admin
        </a>

        <!-- Sidebar Toggle -->
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Notifications -->
        <!-- <ul class="navbar-nav ms-auto me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <?= count($notification) ? '<span class="badge bg-danger">' . count($notification) . '</span>' : '' ?>
                    <i class="fa-solid fa-bell"></i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end" style="max-height:400px; overflow:auto;">
                    <?php if ($notification): ?>
                        <?php foreach ($notification as $msg): ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL . $msg['link'] ?>">
                                    <?= htmlspecialchars($msg['message']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="dropdown-item text-muted">No Notification Found</li>
                    <?php endif; ?>
                </ul>
            </li>
        </ul> -->

        <!-- User Menu -->
        <ul class="navbar-nav ms-auto me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>profile.php">Profile</a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>change_password.php">Change Password</a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Logout</a>
                    </li>
                </ul>

            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">

        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark">
                <!-- Sidebar Menu -->
                <div class="sb-sidenav-menu mt-3">
                    <div class="nav flex-column">

                        <?php if ($_SESSION['user_role'] === 'admin'): ?>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>users.php">
                                <i class="fa fa-users"></i> Users
                            </a>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>flats.php">
                                <i class="fa fa-building"></i> Flats
                            </a>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>allotments.php">
                                <i class="fa fa-house-user"></i> Allotments
                            </a>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>maintanenceRate.php">
                                <i class="fa fa-indian-rupee-sign"></i> Maintenance Rate
                            </a>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>maintanenceRecords.php">
                                <i class="fa fa-file-invoice"></i> Maintanence Records
                            </a>

                        <?php else: ?>

                            <a class="nav-link d-flex align-items-center gap-2" href="<?= BASE_URL ?>view/view_userMaintanenceBill.php">
                                <i class="fa fa-receipt"></i> Maintenance Bills
                            </a>

                        <?php endif; ?>

                        <hr class="text-secondary my-2">

                        <a class="nav-link d-flex align-items-center gap-2 text-danger" href="<?= BASE_URL ?>logout.php">
                            <i class="fa fa-right-from-bracket"></i> Logout
                        </a>

                    </div>
                </div>

                <!-- Sidebar Footer -->
                <div class="sb-sidenav-footer mt-auto p-3 bg-dark text-white d-flex flex-column align-items-center justify-content-center border-top border-secondary">
                    <div class="small text-muted">Logged in as : <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
                </div>
            </nav>
        </div>


        <div id="layoutSidenav_content">
            <main>