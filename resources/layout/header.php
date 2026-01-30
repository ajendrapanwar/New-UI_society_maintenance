<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';
requireRole(['admin', 'cashier', 'user']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/styles.css">
    <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery first -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>


    <style>
        .nav-link {
            cursor: pointer;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
        <a class="navbar-brand ps-3" href="<?= BASE_URL ?>dashboard.php">Society Maintenance</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 d-lg-none" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="navbar-nav ms-auto me-3">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php">Profile</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>change_password.php">Change Password</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>

    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidebarAccordion">

                <div class="sb-sidenav-menu mt-3">
                    <div class="nav flex-column">
                        <!---------------------------- Admin Sidebar ---------------------------->
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <!-- COLLECTION -->
                            <a class="nav-link collapsed sidebar-toggle" data-target="#collectionMenu">
                                <i class="fa fa-layer-group pe-2"></i> Collection<i class="fas fa-angle-down float-end ps-1"></i>
                            </a>
                            <div class="collapse" id="collectionMenu" data-menu="collection">
                                <nav class="nav flex-column ms-2">
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>users.php"><i class="fa fa-users pe-1"></i> Users</a>
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>flats.php"><i class="fa fa-building pe-1"></i> Flats</a>
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>allotments.php"><i class="fa fa-house-user pe-1"></i> Allotments</a>
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>maintanenceRate.php"><i class="fa fa-indian-rupee-sign pe-1"></i> Maintenance Rate</a>
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>maintanenceRecords.php"><i class="fa fa-file-invoice pe-1"></i> Maintenance Records</a>
                                    <a class="nav-link py-2" data-menu="collection" href="<?= BASE_URL ?>all_bill.php"><i class="fa fa-file-invoice pe-1"></i> Maintenance Bills</a>
                                </nav>
                            </div>

                            <!-- EXPENSE -->
                            <a class="nav-link collapsed sidebar-toggle mt-2" data-target="#expenseMenu">
                                <i class="fa fa-money-bill-wave pe-2"></i> Expense
                                <i class="fas fa-angle-down float-end ps-1"></i>
                            </a>

                            <div class="collapse" id="expenseMenu" data-menu="expense">

                                <!-- SINGLE LINK -->
                                <a class="nav-link py-2 ms-2" data-menu="expense" href="<?= BASE_URL ?>miscellaneous_work.php"><i class="fa fa-file-invoice pe-1"></i>Miscellaneous Work</a>

                                <nav class="nav flex-column ms-2">

                                    <!-- SALARY SUB-DROPDOWN -->
                                    <a class="nav-link collapsed sidebar-toggle py-2" data-target="#salaryMenu" data-parent="expenseMenu">
                                        <i class="fa fa-wallet pe-2"></i> Salary
                                        <i class="fas fa-angle-down float-end ps-1"></i>
                                    </a>

                                    <div class="collapse" id="salaryMenu">
                                        <nav class="nav flex-column ms-3">
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_security.php"><i class="fa fa-user-shield pe-1"></i> Security Salary</a>
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_sweeper.php"> <i class="fa-solid fa-broom pe-1"></i> Sweeper Salary</a>
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_garbageCollector.php"><i class="fa-solid fa-trash pe-1"></i> Garbage Collector Salary</a>
                                        </nav>
                                    </div>
                                </nav>

                                <!-- SINGLE LINK -->
                                <a class="nav-link py-2 ms-2" data-menu="expense" href="<?= BASE_URL ?>electricity_bills.php"><i class="fa fa-file-invoice pe-1"></i> Electricity Bills</a>
                            </div>


                            <!---------------------------- Cashier Sidebar ---------------------------->
                        <?php elseif ($_SESSION['user_role'] === 'cashier'): ?>
                            <!-- COLLECTION -->
                            <a class="nav-link collapsed sidebar-toggle" data-target="#collectionMenu">
                                <i class="fa fa-layer-group pe-2"></i> Collection<i class="fas fa-angle-down float-end ps-1"></i>
                            </a>
                            <div class="collapse" id="collectionMenu" data-menu="collection">
                                <nav class="nav flex-column ms-3">
                                    <a class="nav-link" data-menu="collection" href="<?= BASE_URL ?>maintanenceRecords.php"><i class="fa fa-file-invoice pe-1"></i> Maintenance Records</a>
                                    <a class="nav-link" data-menu="collection" href="<?= BASE_URL ?>all_bill.php"><i class="fa fa-file-invoice pe-1"></i> Maintenance Bills</a>
                                </nav>
                            </div>

                            <!-- EXPENSE -->
                            <a class="nav-link collapsed sidebar-toggle mt-2" data-target="#expenseMenu">
                                <i class="fa fa-money-bill-wave pe-2"></i> Expense
                                <i class="fas fa-angle-down float-end ps-1"></i>
                            </a>

                            <div class="collapse" id="expenseMenu" data-menu="expense">

                                <!-- SINGLE LINK -->
                                <a class="nav-link py-2 ms-2" data-menu="expense" href="<?= BASE_URL ?>miscellaneous_work.php"><i class="fa fa-file-invoice pe-1"></i>Miscellaneous Work</a>

                                <nav class="nav flex-column ms-2">

                                    <!-- SALARY SUB-DROPDOWN -->
                                    <a class="nav-link collapsed sidebar-toggle py-2" data-target="#salaryMenu" data-parent="expenseMenu">
                                        <i class="fa fa-wallet pe-2"></i> Salary
                                        <i class="fas fa-angle-down float-end ps-1"></i>
                                    </a>

                                    <div class="collapse" id="salaryMenu">
                                        <nav class="nav flex-column ms-3">
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_security.php"><i class="fa fa-user-shield pe-1"></i> Security Salary</a>
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_sweeper.php"> <i class="fa-solid fa-broom pe-1"></i> Sweeper Salary</a>
                                            <a class="nav-link py-2" data-menu="expense" href="<?= BASE_URL ?>salary_garbageCollector.php"><i class="fa-solid fa-trash pe-1"></i> Garbage Collector Salary</a>
                                        </nav>
                                    </div>
                                </nav>

                                <!-- SINGLE LINK -->
                                <a class="nav-link py-2 ms-2" data-menu="expense" href="<?= BASE_URL ?>electricity_bills.php"><i class="fa fa-file-invoice pe-1"></i> Electricity Bills</a>
                            </div>

                            <!---------------------------- User Sidebar ---------------------------->
                        <?php else: ?>
                            <a class="nav-link" href="<?= BASE_URL ?>view/view_userMaintanenceBill.php"><i class="fa fa-receipt pe-2"></i>My Bills
                            </a>
                        <?php endif; ?>

                        <!-------------- Logout -------------->
                        <hr class="text-secondary my-2">
                        <a class="nav-link text-danger" href="<?= BASE_URL ?>logout.php">
                            <i class="fa fa-right-from-bracket pe-2"></i>Logout
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
            <main class="p-4">
                <!-- Page Content -->

                <!-------------- JS TOGGLE -------------->
                <script>
                    document.addEventListener('DOMContentLoaded', function() {

                        const currentUrl = window.location.href;

                        document.querySelectorAll('.nav-link[href]').forEach(link => {
                            const href = link.getAttribute('href');
                            if (!href || !currentUrl.includes(href)) return;

                            // Highlight active link
                            link.classList.add('active');

                            // Open parent menu
                            const menuType = link.dataset.menu;
                            if (menuType) {
                                const parentMenu = document.getElementById(menuType + 'Menu');
                                if (parentMenu) {
                                    bootstrap.Collapse.getOrCreateInstance(parentMenu, {
                                        toggle: true
                                    });
                                }
                            }

                            // Open salary submenu if inside it
                            const salaryMenu = link.closest('#salaryMenu');
                            if (salaryMenu) {
                                bootstrap.Collapse.getOrCreateInstance(salaryMenu, {
                                    toggle: true
                                });

                                const expenseMenu = document.getElementById('expenseMenu');
                                bootstrap.Collapse.getOrCreateInstance(expenseMenu, {
                                    toggle: true
                                });
                            }
                        });

                        // Sidebar toggle click
                        document.querySelectorAll('.sidebar-toggle').forEach(toggle => {
                            toggle.addEventListener('click', function(e) {
                                e.preventDefault();
                                const target = document.querySelector(this.dataset.target);
                                if (target) {
                                    bootstrap.Collapse.getOrCreateInstance(target).toggle();
                                }
                            });
                        });

                    });
                </script>